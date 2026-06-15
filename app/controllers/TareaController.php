<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\TareaModel;
use App\Models\SolicitudModel;
use App\Models\VersionamientoModel;
use App\Models\ArchivoModel;
use App\Services\TareaService;
use App\Services\NotificacionTareaService;

/**
 * TareaController
 *
 * Responsabilidad: validar HTTP input, llamar a TareaService, redirigir.
 * Toda la lógica de negocio (flujo, asignaciones, versionamiento, correos)
 * vive en TareaService y NotificacionTareaService.
 */
class TareaController extends Controller
{
    private TareaModel    $model;
    private SolicitudModel $solModel;
    private ArchivoModel  $archivoModel;
    private TareaService  $svc;

    public function __construct()
    {
        $this->model        = new TareaModel();
        $this->solModel     = new SolicitudModel();
        $this->archivoModel = new ArchivoModel();
        $this->svc          = new TareaService(
            $this->model,
            $this->solModel,
            new VersionamientoModel(),
            $this->archivoModel,
            new NotificacionTareaService()
        );
    }

    // -----------------------------------------------------------------------
    // Asignadas / Iniciar
    // -----------------------------------------------------------------------

    /** GET /tareas/asignadas */
    public function asignadas(): void
    {
        $idEmpleado = Auth::empleadoId() ?? 0;
        $asignadas  = $this->solModel->asignadasAEmpleado($idEmpleado);
        $this->view('tareas/asignadas', [
            'pageTitle' => 'Solicitudes Asignadas',
            'asignadas' => $asignadas,
            'resumen'   => $this->model->resumenEstados($idEmpleado),
        ]);
    }

    /** POST /tareas/iniciar/{idSolicitud} */
    public function iniciar(int $idSolicitud): void
    {
        Csrf::verify();

        $idTarea = $this->svc->iniciar($idSolicitud);

        $this->redirectSuccess('/tareas/elaborar', 'Tarea iniciada. Proceda a elaborar el documento.');
    }

    // -----------------------------------------------------------------------
    // Elaborar
    // -----------------------------------------------------------------------

    /** GET /tareas/elaborar */
    public function elaborar(): void
    {
        $idEmpleado = Auth::empleadoId() ?? 0;
        $creadas    = $this->model->porEstadoYRol($idEmpleado, 'CREADO',   'elaborador');
        $devueltas  = $this->model->porEstadoYRol($idEmpleado, 'DEVUELTO', 'elaborador');
        $tareas     = array_merge($creadas, $devueltas);

        $this->view('tareas/lista', [
            'pageTitle' => 'Mis Tareas — Elaborar',
            'tareas'    => $tareas,
            'tipo'      => 'elaborar',
            'urlAccion' => '/tareas/elaborar',
            'resumen'   => $this->model->resumenEstados($idEmpleado),
        ]);
    }

    /** GET /tareas/elaborar/{id} */
    public function elaborarVer(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        $idSolicitud    = (int)($tarea['id_solicitud'] ?? 0);
        $archivosAnexos = $idSolicitud
            ? $this->archivoModel->deEntidad('SOLICITUD', $idSolicitud)
            : [];

        $empModel  = new \App\Models\EmpleadoModel();
        $revisores = $empModel->revisores();

        $solicitud      = $this->solModel->find($idSolicitud);
        $esTipoCreacion = ($solicitud['tipo_solicitud'] ?? '') === 'CREACION';

        $macroprocesos = $procesos = $subprocesos = $docExistente = [];
        if ($esTipoCreacion) {
            $macroprocesos = (new \App\Models\MacroprocesoModel())->activos();
            $procesos      = (new \App\Models\ProcesoModel())->activos();
            $subprocesos   = (new \App\Models\SubprocesoModel())->activos();
            if (!empty($solicitud['codigo_documento'])) {
                $docExistente = (new \App\Models\DocumentoModel())
                    ->buscarPorCodigo($solicitud['codigo_documento']) ?? [];
            }
        }

        $comentarios = $this->solModel->comentariosDeSolicitud($idSolicitud);

        $this->view('tareas/elaborar', [
            'pageTitle'      => 'Elaborar Tarea #' . $id,
            'tarea'          => $tarea,
            'comentarios'    => $comentarios ?? [],
            'archivosAnexos' => $archivosAnexos,
            'revisores'      => $revisores,
            'solicitud'      => $solicitud,
            'esTipoCreacion' => $esTipoCreacion,
            'macroprocesos'  => $macroprocesos,
            'procesos'       => $procesos,
            'subprocesos'    => $subprocesos,
            'docExistente'   => $docExistente,
        ]);
    }

    /** POST /tareas/elaborar/{id} */
    public function elaborarGuardar(int $id): void
    {
        Csrf::verify();
        $comentario = trim((string) Request::post('comentario', ''));
        $accion     = Request::post('accion', 'guardar');
        $idRevisor  = (int) Request::post('id_empleado_revisor', 0);

        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        // Subir archivo (Base64 o $_FILES)
        $this->subirArchivoTarea($id);

        if ($accion !== 'enviar') {
            $this->redirectSuccess("/tareas/elaborar/$id", 'Borrador guardado.');
            return;
        }

        if (!$idRevisor) {
            Session::flash('error', 'Debe seleccionar un revisor para enviar a revisión.');
            $this->redirect("/tareas/elaborar/$id");
            return;
        }

        // Documento v0 para solicitudes de CREACION
        $solicitud = $this->solModel->find((int)($tarea['id_solicitud'] ?? 0));
        if (($solicitud['tipo_solicitud'] ?? '') === 'CREACION') {
            try {
                $this->svc->crearOActualizarDocumentoV0($solicitud, [
                    'nombre_documento' => Request::post('nombre_documento', ''),
                    'id_proceso'       => Request::post('id_proceso', 0),
                    'id_subproceso'    => Request::post('id_subproceso', 0),
                ], $id);
            } catch (\RuntimeException $e) {
                Session::flash('error', $e->getMessage());
                $this->redirect("/tareas/elaborar/$id");
                return;
            }
        }

        try {
            $this->svc->enviarARevision($id, $tarea, $idRevisor, $comentario);
        } catch (\Throwable $e) {
            error_log('[TareaController::elaborarGuardar] ' . $e->getMessage());
            Session::flash('error', 'Error al enviar a revisión: ' . $e->getMessage());
            $this->redirect("/tareas/elaborar/$id");
            return;
        }

        $revisor    = (new \App\Models\EmpleadoModel())->find($idRevisor);
        $nombreRev  = $revisor['nombre_completo'] ?? "Empleado #{$idRevisor}";
        $this->redirectSuccess('/tareas/elaborar', "Documento enviado a revisión de <strong>{$nombreRev}</strong>.");
    }

    // -----------------------------------------------------------------------
    // Revisar
    // -----------------------------------------------------------------------

    /** GET /tareas/revisar */
    public function revisar(): void
    {
        $idEmp  = Auth::empleadoId() ?? 0;
        $tareas = $this->model->porEstadoYRol($idEmp, 'REVISION', 'revisor');
        $this->view('tareas/lista', [
            'pageTitle' => 'Mis Tareas — Revisar',
            'tareas'    => $tareas,
            'tipo'      => 'revisar',
            'urlAccion' => '/tareas/revisar',
            'resumen'   => $this->model->resumenEstados($idEmp),
        ]);
    }

    /** GET /tareas/revisar/{id} */
    public function revisarVer(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        $idSolicitud    = (int)($tarea['id_solicitud'] ?? 0);
        $archivosAnexos = $idSolicitud
            ? $this->archivoModel->deEntidad('SOLICITUD', $idSolicitud)
            : [];
        $archivosDoc    = $this->archivoModel->deEntidad('TAREA', $id);
        $aprobadores    = (new \App\Models\EmpleadoModel())->aprobadores();
        $comentarios    = $this->solModel->comentariosDeSolicitud($idSolicitud);

        $this->view('tareas/revisar', [
            'pageTitle'      => 'Revisar Tarea #' . $id,
            'tarea'          => $tarea,
            'comentarios'    => $comentarios,
            'archivosAnexos' => $archivosAnexos,
            'archivosDoc'    => $archivosDoc,
            'aprobadores'    => $aprobadores,
        ]);
    }

    /** POST /tareas/revisar/{id} */
    public function revisarGuardar(int $id): void
    {
        Csrf::verify();
        $accion      = Request::post('accion', 'aprobar');
        $comentario  = trim((string) Request::post('comentario', ''));
        $idAprobador = (int) Request::post('id_empleado_aprobador', 0);

        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        if ($accion === 'aprobar') {
            if (!$idAprobador) {
                Session::flash('error', 'Debe seleccionar un aprobador.');
                $this->redirect("/tareas/revisar/$id");
                return;
            }
            try {
                $this->svc->enviarAAprobacion($id, $tarea, $idAprobador, $comentario);
            } catch (\Throwable $e) {
                error_log('[TareaController::revisarGuardar] ' . $e->getMessage());
                Session::flash('error', 'Error al enviar a aprobación: ' . $e->getMessage());
                $this->redirect("/tareas/revisar/$id");
                return;
            }
            $aprobador  = (new \App\Models\EmpleadoModel())->find($idAprobador);
            $nombreApro = $aprobador['nombre_completo'] ?? "Empleado #{$idAprobador}";
            $this->redirectSuccess('/tareas/revisar', "Tarea enviada a aprobación de <strong>{$nombreApro}</strong>.");

        } else {
            try {
                $this->svc->devolverAlElaborador($id, $tarea, $comentario);
            } catch (\Throwable $e) {
                error_log('[TareaController::revisarGuardar] ' . $e->getMessage());
                Session::flash('error', 'Error al devolver la tarea: ' . $e->getMessage());
                $this->redirect("/tareas/revisar/$id");
                return;
            }
            $elaborador = null;
            foreach ($tarea['asignaciones'] ?? [] as $a) {
                if (strtolower($a['rol_asignacion'] ?? '') === 'elaborador') { $elaborador = $a; break; }
            }
            $nombreElab = $elaborador['nombre_completo'] ?? 'elaborador';
            $this->redirectSuccess('/tareas/revisar', "Tarea devuelta a <strong>{$nombreElab}</strong> para corrección.");
        }
    }

    // -----------------------------------------------------------------------
    // Aprobar
    // -----------------------------------------------------------------------

    /** GET /tareas/aprobar */
    public function aprobar(): void
    {
        $idEmp  = Auth::empleadoId() ?? 0;
        $tareas = $this->model->porEstadoYRol($idEmp, 'APROBACION', 'aprobador');
        $this->view('tareas/lista', [
            'pageTitle' => 'Tareas para Aprobar',
            'tareas'    => $tareas,
            'tipo'      => 'aprobar',
            'urlAccion' => '/tareas/aprobar',
            'resumen'   => $this->model->resumenEstados($idEmp),
        ]);
    }

    /** GET /tareas/aprobar/{id} */
    public function aprobarVer(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        $idSolicitud    = (int)($tarea['id_solicitud'] ?? 0);
        $archivosAnexos = $idSolicitud
            ? $this->archivoModel->deEntidad('SOLICITUD', $idSolicitud)
            : [];

        $solicitud      = $this->solModel->find($idSolicitud);
        $esTipoCreacion = ($solicitud['tipo_solicitud'] ?? '') === 'CREACION';

        $macroprocesos = $procesos = $subprocesos = $docExistente = [];
        if ($esTipoCreacion) {
            if (!empty($solicitud['codigo_documento'])) {
                $docMdl = new \App\Models\DocumentoModel();
                $idDocE = $docMdl->idPorCodigo($solicitud['codigo_documento']);
                if ($idDocE) {
                    $docExistente = $docMdl->conDetalle($idDocE) ?? [];
                }
            }
            if (empty($docExistente)) {
                $macroprocesos = (new \App\Models\MacroprocesoModel())->activos();
                $procesos      = (new \App\Models\ProcesoModel())->activos();
                $subprocesos   = (new \App\Models\SubprocesoModel())->activos();
            }
        }

        $comentarios = $this->solModel->comentariosDeSolicitud($idSolicitud);

        $this->view('tareas/aprobar', [
            'pageTitle'      => 'Aprobar Tarea #' . $id,
            'tarea'          => $tarea,
            'comentarios'    => $comentarios ?? [],
            'solicitud'      => $solicitud,
            'archivosAnexos' => $archivosAnexos,
            'esTipoCreacion' => $esTipoCreacion,
            'macroprocesos'  => $macroprocesos,
            'procesos'       => $procesos,
            'subprocesos'    => $subprocesos,
            'docExistente'   => $docExistente,
        ]);
    }

    /** POST /tareas/aprobar/{id} */
    public function aprobarGuardar(int $id): void
    {
        Csrf::verify();
        $accion     = Request::post('accion', 'aprobar');
        $comentario = trim((string) Request::post('comentario', ''));

        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        if ($accion === 'rechazar') {
            try {
                $this->svc->devolverAlRevisor($id, $tarea, $comentario);
            } catch (\Throwable $e) {
                error_log('[TareaController::aprobarGuardar] rechazar: ' . $e->getMessage());
                Session::flash('error', 'Error al devolver la tarea: ' . $e->getMessage());
                $this->redirect("/tareas/aprobar/$id");
                return;
            }
            $this->redirectSuccess('/tareas/aprobar', 'Tarea devuelta al revisor con comentario.');
            return;
        }

        // Aprobar
        try {
            $this->svc->aprobar($id, $tarea, $comentario, [
                'nombre_documento' => Request::post('nombre_documento', ''),
                'id_proceso'       => Request::post('id_proceso', 0),
                'id_subproceso'    => Request::post('id_subproceso', 0),
            ]);
        } catch (\Throwable $e) {
            error_log('[TareaController::aprobarGuardar] ' . $e->getMessage());
            Session::flash('error', 'Error al aprobar: ' . $e->getMessage());
            $this->redirect("/tareas/aprobar/$id");
            return;
        }

        $codigoDoc = $tarea['codigo_documento'] ?? 'documento';
        $this->redirectSuccess('/tareas/finalizadas',
            "Documento aprobado. Versión publicada como VIGENTE.");
    }

    // -----------------------------------------------------------------------
    // Devueltas / Finalizadas / Mis Tareas / Panel / Ver
    // -----------------------------------------------------------------------

    /** GET /tareas/devueltas */
    public function devueltas(): void
    {
        $idEmp  = Auth::empleadoId() ?? 0;
        $dElab  = $this->model->porEstadoYRol($idEmp, 'DEVUELTO', 'elaborador');
        foreach ($dElab as &$t) { $t['url_accion'] = '/tareas/elaborar/' . $t['id_tarea']; }
        $dRevis = $this->model->porEstadoYRol($idEmp, 'DEVUELTO', 'revisor');
        foreach ($dRevis as &$t) { $t['url_accion'] = '/tareas/revisar/' . $t['id_tarea']; }
        unset($t);
        $this->view('tareas/devueltas', [
            'pageTitle' => 'Tareas Devueltas',
            'tareas'    => array_values(array_merge($dElab, $dRevis)),
            'resumen'   => $this->model->resumenEstados($idEmp),
        ]);
    }

    /** GET /tareas/finalizadas */
    public function finalizadas(): void
    {
        $desde = Request::get('desde', '');
        $hasta = Request::get('hasta', '');
        $this->view('tareas/finalizadas', [
            'pageTitle' => 'Tareas Finalizadas',
            'resumen'   => $this->model->resumenEstados(Auth::empleadoId() ?? 0),
            'tareas'    => $this->model->finalizadas(Auth::empleadoId() ?? 0, $desde ?: null, $hasta ?: null),
            'desde'     => $desde,
            'hasta'     => $hasta,
        ]);
    }

    /** GET /tareas/mis-tareas */
    public function misTareas(): void
    {
        $idEmp = Auth::empleadoId() ?? 0;
        $this->view('tareas/mis_tareas', [
            'pageTitle' => 'Mis Tareas',
            'tareas'    => $this->model->misTareas($idEmp),
            'resumen'   => $this->model->resumenEstados($idEmp),
        ]);
    }

    /** GET /tareas/panel */
    public function panel(): void
    {
        $tareas = $this->model->todasLasTareas();
        $stats  = [];
        foreach ($tareas as $t) {
            $e = $t['estado_actual'];
            $stats[$e] = ($stats[$e] ?? 0) + 1;
        }
        $this->view('tareas/panel', [
            'pageTitle' => 'Panel de Tareas',
            'tareas'    => $tareas,
            'stats'     => $stats,
        ]);
    }

    /** GET /tareas/ver/{id} */
    public function ver(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        $idSolicitud    = (int)($tarea['id_solicitud'] ?? 0);
        $archivosAnexos = $idSolicitud
            ? $this->archivoModel->deEntidad('SOLICITUD', $idSolicitud)
            : [];
        $archivosDoc = $this->archivoModel->deEntidad('TAREA', $id);
        $comentarios = $this->solModel->comentariosDeSolicitud($idSolicitud);
        $solicitud   = $this->solModel->find($idSolicitud);

        $this->view('tareas/ver', [
            'pageTitle'      => 'Detalle Tarea #' . $id . ' — Solo Lectura',
            'tarea'          => $tarea,
            'solicitud'      => $solicitud,
            'archivosAnexos' => $archivosAnexos,
            'archivosDoc'    => $archivosDoc,
            'comentarios'    => $comentarios,
            'soloLectura'    => true,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------------

    /**
     * Sube el archivo de la tarea si viene en el request (Base64 o $_FILES).
     * Lanza RuntimeException ante error; el controlador lo captura y redirige.
     */
    private function subirArchivoTarea(int $idTarea): void
    {
        $tieneB64  = !empty($_POST['archivo_b64']);
        $tieneFile = isset($_FILES['archivo']) && ($_FILES['archivo']['error'] ?? 4) === UPLOAD_ERR_OK;

        if (!$tieneB64 && !$tieneFile) {
            return;
        }

        $fileRef               = $_FILES['archivo'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'size' => 0, 'tmp_name' => ''];
        $fileRef['field_name'] = 'archivo';

        try {
            $upload = subirArchivo(
                $fileRef,
                'tareas',
                ['application/pdf', 'application/msword',
                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'application/vnd.ms-excel',
                 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                 'image/jpeg', 'image/png', 'image/webp', 'text/plain'],
                52428800
            );
            $this->archivoModel->registrar('TAREA', $idTarea, $upload, Auth::id());
        } catch (\Throwable $e) {
            error_log('[TareaController] upload: ' . $e->getMessage());
            Session::flash('error', 'Error al subir archivo: ' . $e->getMessage());
            $this->redirect("/tareas/elaborar/$idTarea");
            // redirect() lanza exit/never — no llega aquí
        }
    }
}
