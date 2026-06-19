<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session, Database};
use App\Models\{HallazgoModel, AccionCorrectivaModel, AuditoriaInternaModel, EmpleadoModel, UsuarioModel, AuditoriaProgramaModel};

class HallazgoController extends Controller
{
    private HallazgoModel        $model;
    private AccionCorrectivaModel $acModel;

    public function __construct()
    {
        $this->model   = new HallazgoModel();
        $this->acModel = new AccionCorrectivaModel();
    }

    /** CA-1: Listado con filtros */
    public function index(): void
    {
        $estado = Request::get('estado', '');
        $tipo   = Request::get('tipo',   '');

        $this->view('sgc/hallazgos/index', [
            'pageTitle'  => 'Gestión de Hallazgos',
            'hallazgos'  => $this->model->listar($estado ?: null, $tipo ?: null),
            'resumen'    => $this->model->resumen(),
            'filtroEst'  => $estado,
            'filtroTipo' => $tipo,
        ]);
    }

    /** Detalle / formulario de cierre */
    public function ver(int $id): void
    {
        $h = $this->model->detalle($id);
        if (!$h) $this->abort(404);

        // Acciones correctivas disponibles para vincular
        $acs = $this->acModel->listar();

        $this->view('sgc/hallazgos/ver', [
            'pageTitle' => 'Hallazgo #' . $id,
            'hallazgo'  => $h,
            'acs'       => $acs,
        ]);
    }

    /** CA-2+3: Actualizar estado y vincular AC */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->detalle($id);
        if (!$antes) $this->abort(404);

        $data = Request::only([
            'estado','accion_correctiva','responsable',
            'fecha_cierre','evidencia','id_accion_correctiva'
        ]);

        // Fecha cierre automática al cerrar
        if (($data['estado'] ?? '') === 'CERRADO' && empty($data['fecha_cierre'])) {
            $data['fecha_cierre'] = date('Y-m-d');
        }

        $this->model->actualizarHallazgo($id, $data);

        // CA-4: al cerrar hallazgo con AC vinculada → actualizar estado AC
        $idAC = (int)($data['id_accion_correctiva'] ?? $antes['id_accion_correctiva'] ?? 0);
        if (($data['estado'] ?? '') === 'CERRADO' && $idAC) {
            try {
                $this->acModel->update($idAC, [
                    'estado'       => 'VERIFICACION',
                    'fecha_cierre' => date('Y-m-d'),
                ]);
                Session::flash('info',
                    "Acción Correctiva #{$idAC} actualizada a estado <strong>VERIFICACION</strong>.");
            } catch (\Throwable $e) {
                error_log('[HallazgoController] update AC: ' . $e->getMessage());
            }
        }

        // Actualizar id_accion_correctiva en auditoria_hallazgo si se vinculó
        if ($idAC && !$antes['id_accion_correctiva']) {
            $this->model->update($id, ['id_accion_correctiva' => $idAC]);
            // Marcar la AC con el id_hallazgo
            try {
            $this->acModel->update($idAC, ['id_hallazgo' => $id]);
        } catch (\Throwable $e) {
            error_log('[HallazgoController] vincular AC: ' . $e->getMessage());
        }
        }

        registrarAuditoria('hallazgos','ACTUALIZAR','auditoria_hallazgo',$id,$antes,$data);
        $this->redirectSuccess('/hallazgos', 'Hallazgo actualizado.');
    }

    /** Eliminar / marcar CERRADO desde listado rápido */
    public function cerrar(int $id): void
    {
        Csrf::verify();
        $this->model->update($id, ['estado' => 'CERRADO', 'fecha_cierre' => date('Y-m-d')]);
        $this->redirectSuccess('/hallazgos', 'Hallazgo cerrado.');
    }

    /** CA-1/CA-2: Formulario de nuevo hallazgo */
    public function crear(): void
    {
        Session::clearOldInput();
        // Programas con plan APROBADO/EN_CURSO/FINALIZADO + su informe vinculado
        $programas = (new \App\Models\AuditoriaProgramaModel())->conPlanAprobadoParaHallazgo();

        $this->view('sgc/hallazgos/form', [
            'pageTitle' => 'Nuevo Hallazgo',
            'item'      => null,
            'programas' => $programas,
            'usuarios'  => (new EmpleadoModel())->activos(),
            'procesos'  => [],  // se cargan via AJAX al seleccionar programa
        ]);
    }

    /** AJAX: procesos del plan vinculado al programa */
    public function procesosPorPrograma(int $idPrograma): void
    {
        $pg = (new AuditoriaProgramaModel())->detalle($idPrograma);
        if (!$pg || empty($pg['id_plan'])) { $this->json([]); return; }
        $procesos = (new \App\Models\AuditoriaPlanModel())->procesosDePlan((int)$pg['id_plan']);
        $this->json($procesos);
    }

    /** CA-3: Guardar nuevo hallazgo */
    public function guardar(): void
    {
        Csrf::verify();

        $data = Request::only([
            'id_programa','tipo','clausula_iso','proceso_auditado',
            'descripcion','evidencia','criterios','causa','riesgos','recomendacion',
            'responsable','id_responsable','fecha_cierre',
        ]);
        $errors = $this->validate($data, [
            'id_programa'  => 'required|integer',
            'tipo'         => 'required',
            'descripcion'  => 'required',
        ]);
        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/hallazgos/crear');
            return;
        }

        $data['estado']          = 'ABIERTO';
        $data['fecha_registro']  = date('Y-m-d H:i:s');
        $id = $this->model->insert($data);
        if (!$id) {
            Session::flash('error', 'Error al registrar el hallazgo.');
            $this->redirect('/hallazgos/crear');
            return;
        }
        registrarAuditoria('hallazgos','CREAR','auditoria_hallazgo',$id,null,$data);
        $this->redirectSuccess('/hallazgos', 'Hallazgo registrado correctamente.');
    }

}