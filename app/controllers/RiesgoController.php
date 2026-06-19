<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{RiesgoModel, ProcesoModel, EmpleadoModel, AccionCorrectivaModel};

class RiesgoController extends Controller
{
    private RiesgoModel $model;

    public function __construct() { $this->model = new RiesgoModel(); }

    /** GET /riesgos */
    public function index(): void
    {
        $filtros = [
            'id_proceso'              => Request::get('id_proceso', ''),
            'estado'                  => Request::get('estado', ''),
            'nivel_riesgo_inherente'  => Request::get('nivel', ''),
        ];
        $this->view('sgc/riesgos/index', [
            'pageTitle' => 'Gestión de Riesgos por Proceso',
            'items'     => $this->model->listar($filtros),
            'procesos'  => (new ProcesoModel())->activos(),
            'filtros'   => $filtros,
            'resumenNivel'  => $this->model->resumenPorNivel(),
            'resumenEstado' => $this->model->resumenPorEstado(),
        ]);
    }

    /** GET /riesgos/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('sgc/riesgos/form', [
            'pageTitle' => 'Identificar Riesgo',
            'item'      => null,
            'codigo'    => $this->model->siguienteCodigo(),
            'procesos'  => (new ProcesoModel())->activos(),
            'empleados' => (new EmpleadoModel())->activos(),
        ]);
    }

    /** POST /riesgos/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only([
            'codigo','id_proceso','descripcion','causa','consecuencia',
            'probabilidad_inherente','impacto_inherente',
            'tratamiento','id_responsable','fecha_tratamiento_planificada',
        ]);

        if (empty($data['descripcion']) || empty($data['id_proceso'])) {
            Session::flash('error', 'El proceso y la descripción del riesgo son obligatorios.');
            $this->redirect('/riesgos/crear');
            return;
        }

        $data['id_proceso']     = (int) $data['id_proceso'];
        $data['id_responsable'] = !empty($data['id_responsable']) ? (int) $data['id_responsable'] : null;
        if (empty($data['fecha_tratamiento_planificada'])) $data['fecha_tratamiento_planificada'] = null;

        $prob = in_array($data['probabilidad_inherente'] ?? '', ['ALTO','MEDIO','BAJO'], true)
            ? $data['probabilidad_inherente'] : 'MEDIO';
        $imp  = in_array($data['impacto_inherente'] ?? '', ['ALTO','MEDIO','BAJO'], true)
            ? $data['impacto_inherente'] : 'MEDIO';
        $data['probabilidad_inherente']  = $prob;
        $data['impacto_inherente']       = $imp;
        $data['nivel_riesgo_inherente']  = $this->model->derivarNivel($prob, $imp);
        $data['id_usuario_registro']     = Auth::id();
        $data['estado']                  = 'IDENTIFICADO';

        $id = $this->model->insert($data);
        registrarAuditoria('riesgos', 'CREAR', 'riesgo', $id, null, $data);

        $this->redirectSuccess('/riesgos', "Riesgo {$data['codigo']} identificado correctamente.");
    }

    /** GET /riesgos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $cerrado = $item['estado'] === 'CERRADO';
        if ($cerrado && !Auth::hasRole([1, 2])) {
            Session::flash('error', 'Este riesgo está CERRADO y no puede modificarse.');
            $this->redirect("/riesgos/ver/{$id}");
            return;
        }

        $this->view('sgc/riesgos/form', [
            'pageTitle' => 'Editar Riesgo — ' . $item['codigo'],
            'item'      => $item,
            'codigo'    => $item['codigo'],
            'procesos'  => (new ProcesoModel())->activos(),
            'empleados' => (new EmpleadoModel())->activos(),
            'soloReapertura' => $cerrado, // true solo llega aquí si es Admin/Coordinador
        ]);
    }

    /** POST /riesgos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->detalle($id);
        if (!$antes) $this->abort(404);

        // CERRADO es un estado terminal: bloqueo total salvo reapertura por
        // Admin/Coordinador Calidad, y esa reapertura solo cambia el estado
        // (sin tocar descripción/causa/evaluación en el mismo envío), para
        // mantener trazable qué cambió antes y después del cierre.
        if ($antes['estado'] === 'CERRADO') {
            if (!Auth::hasRole([1, 2])) {
                Session::flash('error', 'Este riesgo está CERRADO y no puede modificarse.');
                $this->redirect("/riesgos/ver/{$id}");
                return;
            }
            $nuevoEstado = Request::post('estado', '');
            if (!in_array($nuevoEstado, ['IDENTIFICADO', 'EN_TRATAMIENTO'], true)) {
                Session::flash('error', 'Para reabrir un riesgo CERRADO, seleccione IDENTIFICADO o EN TRATAMIENTO como nuevo estado.');
                $this->redirect("/riesgos/ver/{$id}");
                return;
            }
            $this->model->update($id, ['estado' => $nuevoEstado]);
            registrarAuditoria('riesgos', 'REABRIR', 'riesgo', $id,
                ['estado' => 'CERRADO'], ['estado' => $nuevoEstado]);
            $this->redirectSuccess("/riesgos/ver/{$id}", "Riesgo reabierto como {$nuevoEstado}. Ya puede editar el resto de la información.");
            return;
        }

        $data = Request::only([
            'id_proceso','descripcion','causa','consecuencia',
            'probabilidad_inherente','impacto_inherente',
            'tratamiento','id_responsable','fecha_tratamiento_planificada','fecha_tratamiento_real',
            'probabilidad_residual','impacto_residual','estado',
        ]);

        $data['id_proceso']     = !empty($data['id_proceso']) ? (int) $data['id_proceso'] : $antes['id_proceso'];
        $data['id_responsable'] = !empty($data['id_responsable']) ? (int) $data['id_responsable'] : null;
        if (empty($data['fecha_tratamiento_planificada'])) $data['fecha_tratamiento_planificada'] = null;
        if (empty($data['fecha_tratamiento_real']))        $data['fecha_tratamiento_real']        = null;

        $prob = in_array($data['probabilidad_inherente'] ?? '', ['ALTO','MEDIO','BAJO'], true)
            ? $data['probabilidad_inherente'] : $antes['probabilidad_inherente'];
        $imp  = in_array($data['impacto_inherente'] ?? '', ['ALTO','MEDIO','BAJO'], true)
            ? $data['impacto_inherente'] : $antes['impacto_inherente'];
        $data['probabilidad_inherente'] = $prob;
        $data['impacto_inherente']      = $imp;
        $data['nivel_riesgo_inherente'] = $this->model->derivarNivel($prob, $imp);

        // Riesgo residual: solo se evalúa una vez que hay tratamiento aplicado.
        // Si ambos campos vienen vacíos, se deja NULL (todavía no se ha evaluado el residual).
        $probRes = $data['probabilidad_residual'] ?? '';
        $impRes  = $data['impacto_residual'] ?? '';
        if (in_array($probRes, ['ALTO','MEDIO','BAJO'], true) && in_array($impRes, ['ALTO','MEDIO','BAJO'], true)) {
            $data['probabilidad_residual'] = $probRes;
            $data['impacto_residual']      = $impRes;
            $data['nivel_riesgo_residual'] = $this->model->derivarNivel($probRes, $impRes);
        } else {
            $data['probabilidad_residual'] = null;
            $data['impacto_residual']      = null;
            $data['nivel_riesgo_residual'] = null;
        }

        $estadosValidos = ['IDENTIFICADO','EN_TRATAMIENTO','CONTROLADO','CERRADO'];
        $data['estado'] = in_array($data['estado'] ?? '', $estadosValidos, true)
            ? $data['estado'] : $antes['estado'];

        $this->model->update($id, $data);
        registrarAuditoria('riesgos', 'EDITAR', 'riesgo', $id, $antes, $data);

        $this->redirectSuccess('/riesgos', 'Riesgo actualizado.');
    }

    /** GET /riesgos/ver/{id} */
    public function ver(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $acVinculada = (new AccionCorrectivaModel())->porRiesgo($id);

        $this->view('sgc/riesgos/ver', [
            'pageTitle' => 'Riesgo ' . $item['codigo'],
            'item'      => $item,
            'acVinculada' => $acVinculada,
        ]);
    }

    /** POST /riesgos/{id}/generar-ac — crea una Acción Correctiva para tratar este riesgo */
    public function generarAC(int $id): void
    {
        Csrf::verify();
        $r = $this->model->detalle($id);
        if (!$r) $this->abort(404);

        $yaExiste = (new AccionCorrectivaModel())->porRiesgo($id);
        if ($yaExiste) {
            Session::flash('error', 'Este riesgo ya tiene una Acción Correctiva vinculada.');
            $this->redirect("/riesgos/ver/{$id}");
            return;
        }

        $acModel = new AccionCorrectivaModel();
        try {
            $idAC = $acModel->insert([
                'codigo'           => $acModel->siguienteCodigo(),
                'origen'           => 'PROCESO',
                'id_riesgo'        => $id,
                'id_proceso'       => $r['id_proceso'],
                'descripcion_nc'   => 'Riesgo: ' . $r['descripcion'],
                'causa_raiz'       => $r['causa'] ?? '',
                'accion_inmediata' => $r['tratamiento'] ?? '',
                'id_responsable'   => $r['id_responsable'] ?? null,
                'estado'           => 'ABIERTA',
                'id_usuario'       => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            error_log('[RiesgoController::generarAC] ' . $e->getMessage());
            Session::flash('error', 'No se pudo crear la Acción Correctiva para este riesgo.');
            $this->redirect("/riesgos/ver/{$id}");
            return;
        }

        $this->model->update($id, ['estado' => 'EN_TRATAMIENTO']);
        registrarAuditoria('riesgos', 'GENERAR_AC', 'riesgo', $id, ['estado' => $r['estado']], ['estado' => 'EN_TRATAMIENTO', 'id_ac' => $idAC]);

        $this->redirectSuccess("/riesgos/ver/{$id}", 'Acción Correctiva generada para tratar este riesgo.');
    }
}
