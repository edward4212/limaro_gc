<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{OportunidadMejoraModel, AccionCorrectivaModel, ProcesoModel};

class OportunidadMejoraController extends Controller
{
    private OportunidadMejoraModel $model;

    public function __construct() { $this->model = new OportunidadMejoraModel(); }

    /** GET /mejora */
    public function index(): void
    {
        $filtros = [
            'estado'     => Request::get('estado', ''),
            'id_proceso' => Request::get('id_proceso', ''),
            'solo_mias'  => Request::get('solo_mias', ''),
            'id_empleado_actual' => Auth::empleadoId(),
        ];
        $this->view('sgc/mejora/index', [
            'pageTitle' => 'Oportunidades de Mejora',
            'items'     => $this->model->listar($filtros),
            'filtros'   => $filtros,
            'procesos'  => (new ProcesoModel())->activos(),
            'resumenEstado' => $this->model->resumenPorEstado(),
        ]);
    }

    /** GET /mejora/crear — abierto a cualquier empleado autenticado */
    public function crear(): void
    {
        $this->view('sgc/mejora/form', [
            'pageTitle' => 'Proponer Oportunidad de Mejora',
            'item'      => null,
            'codigo'    => $this->model->siguienteCodigo(),
            'procesos'  => (new ProcesoModel())->activos(),
        ]);
    }

    /** POST /mejora/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['titulo', 'descripcion', 'beneficio_esperado', 'id_proceso']);

        if (empty($data['titulo']) || empty($data['descripcion'])) {
            Session::flash('error', 'Título y descripción son obligatorios.');
            $this->redirect('/mejora/crear');
            return;
        }

        $data['codigo']               = $this->model->siguienteCodigo();
        $data['id_empleado_propone']  = Auth::empleadoId();
        $data['id_usuario_registro']  = Auth::id();
        $data['estado']               = 'PROPUESTA';
        $data['id_proceso']           = !empty($data['id_proceso']) ? (int)$data['id_proceso'] : null;

        $id = $this->model->insert($data);
        registrarAuditoria('mejora', 'PROPONER', 'oportunidad_mejora', $id, null, $data);

        $this->redirectSuccess('/mejora', "Propuesta {$data['codigo']} registrada. Quedará pendiente de evaluación.");
    }

    /** GET /mejora/ver/{id} */
    public function ver(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $this->view('sgc/mejora/ver', [
            'pageTitle' => 'Oportunidad ' . $item['codigo'],
            'item'      => $item,
        ]);
    }

    /** GET /mejora/{id}/evaluar — restringido a Admin/Coordinador Calidad */
    public function evaluarForm(int $id): void
    {
        if (!Auth::hasRole([1, 2])) {
            Session::flash('error', 'Solo Administrador o Coordinador de Calidad pueden evaluar propuestas.');
            $this->redirect('/mejora');
            return;
        }
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'PROPUESTA') {
            Session::flash('error', 'Esta propuesta ya fue evaluada.');
            $this->redirect("/mejora/ver/{$id}");
            return;
        }

        $this->view('sgc/mejora/evaluar', [
            'pageTitle' => 'Evaluar Propuesta — ' . $item['codigo'],
            'item'      => $item,
        ]);
    }

    /** POST /mejora/{id}/evaluar */
    public function evaluarGuardar(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1, 2])) {
            Session::flash('error', 'Solo Administrador o Coordinador de Calidad pueden evaluar propuestas.');
            $this->redirect('/mejora');
            return;
        }
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'PROPUESTA') {
            Session::flash('error', 'Esta propuesta ya fue evaluada.');
            $this->redirect("/mejora/ver/{$id}");
            return;
        }

        $decision = Request::post('decision', '');
        if (!in_array($decision, ['APROBADA', 'RECHAZADA'], true)) {
            Session::flash('error', 'Debe seleccionar Aprobar o Rechazar.');
            $this->redirect("/mejora/{$id}/evaluar");
            return;
        }

        $data = [
            'estado'                 => $decision,
            'comentario_evaluacion'  => Request::post('comentario_evaluacion', ''),
            'id_usuario_evalua'      => Auth::id(),
            'fecha_evaluacion'       => date('Y-m-d'),
        ];
        $this->model->update($id, $data);
        registrarAuditoria('mejora', 'EVALUAR', 'oportunidad_mejora', $id, $item, $data);

        $this->redirectSuccess('/mejora', "Propuesta {$item['codigo']} marcada como {$decision}.");
    }

    /** POST /mejora/{id}/generar-ac — genera AC para implementación formal, solo si APROBADA */
    public function generarAC(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1, 2])) {
            Session::flash('error', 'Solo Administrador o Coordinador de Calidad pueden generar la Acción Correctiva.');
            $this->redirect('/mejora');
            return;
        }
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'APROBADA') {
            Session::flash('error', 'Solo se puede generar Acción Correctiva para propuestas APROBADAS.');
            $this->redirect("/mejora/ver/{$id}");
            return;
        }
        if (!empty($item['id_accion_correctiva'])) {
            Session::flash('warning', 'Esta propuesta ya tiene una Acción Correctiva vinculada.');
            $this->redirect("/mejora/ver/{$id}");
            return;
        }

        $acModel = new AccionCorrectivaModel();
        try {
            $idAC = $acModel->insert([
                'codigo'                => $acModel->siguienteCodigo(),
                'origen'                => 'MEJORA',
                'id_oportunidad_mejora' => $id,
                'descripcion_nc'        => 'Oportunidad de mejora: ' . $item['titulo'],
                'accion_inmediata'      => $item['descripcion'],
                'id_responsable'        => $item['id_empleado_propone'],
                'estado'                => 'ABIERTA',
                'id_usuario'            => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            error_log('[OportunidadMejoraController::generarAC] ' . $e->getMessage());
            Session::flash('error', 'No se pudo crear la Acción Correctiva.');
            $this->redirect("/mejora/ver/{$id}");
            return;
        }

        $this->model->update($id, ['id_accion_correctiva' => $idAC, 'estado' => 'EN_IMPLEMENTACION']);
        registrarAuditoria('mejora', 'GENERAR_AC', 'oportunidad_mejora', $id, null, ['id_accion_correctiva' => $idAC]);

        $this->redirectSuccess("/mejora/ver/{$id}", 'Acción Correctiva creada y vinculada para implementación.');
    }
}
