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

/**
 * Controlador de Tareas.
 * Gestiona flujo: Asignadas → Elaborar → Revisar → Aprobar → Finalizado.
 */
class TareaController extends Controller
{
    private TareaModel         $model;
    private SolicitudModel     $solModel;
    private VersionamientoModel $verModel;
    private ArchivoModel       $archivoModel;

    public function __construct()
    {
        $this->model        = new TareaModel();
        $this->solModel     = new SolicitudModel();
        $this->verModel     = new VersionamientoModel();
        $this->archivoModel = new ArchivoModel();
    }

    // -----------------------------------------------------------------------
    // Solicitudes Asignadas al usuario
    // -----------------------------------------------------------------------

    /** GET /tareas/asignadas */
    public function asignadas(): void
    {
        $this->view('tareas/asignadas', [
            'pageTitle'  => 'Solicitudes Asignadas',
            'asignadas'  => $this->model->asignadasAEmpleado(Auth::empleadoId() ?? 0),
        ]);
    }

    /**
     * POST /tareas/iniciar/{id}
     * Inicia la tarea para una solicitud asignada:
     * - Crea registro tarea
     * - Agrega estado CREADO
     * - Actualiza solicitud → EN DESARROLLO
     */
    public function iniciar(int $idSolicitud): void
    {
        Csrf::verify();

        $idEmpleado = Auth::empleadoId() ?? 0;

        // Crear la tarea
        $idTarea = $this->model->crearViaSP($idSolicitud, $idEmpleado);

        // Agregar estado inicial
        $this->model->agregarEstadoViaSP(
            $idTarea,
            'CREADO',
            'Tarea iniciada por ' . Auth::get('usuario'),
            Auth::id() ?? 0
        );

        // Actualizar solicitud a EN DESARROLLO
        $this->solModel->cambiarEstado($idSolicitud, 'EN DESARROLLO');

        registrarAuditoria('tareas', 'INICIAR', 'tarea', $idTarea, null, ['id_solicitud' => $idSolicitud]);
        $this->redirectSuccess('/tareas/elaborar', 'Tarea iniciada. Proceda a elaborar el documento.');
    }

    // -----------------------------------------------------------------------
    // Elaborar
    // -----------------------------------------------------------------------

    /** GET /tareas/elaborar */
    public function elaborar(): void
    {
        $idEmpleado = Auth::empleadoId() ?? 0;
        // Tareas en CREADO o DEVUELTO donde soy ELABORADOR
        $creadas   = $this->model->porEstadoYRol($idEmpleado, 'CREADO',   'elaborador');
        $devueltas = $this->model->porEstadoYRol($idEmpleado, 'DEVUELTO', 'elaborador');
        $tareas    = array_merge($creadas, $devueltas);

        $this->view('tareas/lista', [
            'pageTitle' => 'Mis Tareas — Elaborar',
            'tareas'    => $tareas,
            'tipo'      => 'elaborar',
            'urlAccion' => '/tareas/elaborar',
        ]);
    }

    /** GET /tareas/elaborar/{id} */
    public function elaborarVer(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        $this->view('tareas/elaborar', [
            'pageTitle' => 'Elaborar Tarea #' . $id,
            'tarea'     => $tarea,
        ]);
    }

    /** POST /tareas/elaborar/{id} */
    public function elaborarGuardar(int $id): void
    {
        Csrf::verify();
        $comentario = trim((string) Request::post('comentario', ''));
        $accion     = Request::post('accion', 'guardar'); // 'guardar' | 'enviar'

        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);

        // Subir documento si existe
        if (Request::hasFile('archivo')) {
            try {
                $upload = subirArchivo(
                    $_FILES['archivo'],
                    'documentos',
                    ['application/pdf',
                     'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    20971520
                );
                $this->archivoModel->registrar('TAREA', $id, $upload, Auth::get('usuario'));
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir archivo: Error interno. Contacte al administrador.');
                $this->redirect("/tareas/elaborar/$id");
            }
        }

        if ($accion === 'enviar') {
            // Enviar a REVISION
            $this->model->agregarEstadoViaSP(
                $id, 'REVISION',
                $comentario ?: 'Enviado a revisión.',
                Auth::id() ?? 0
            );
            $this->redirectSuccess('/tareas/elaborar', 'Tarea enviada a revisión.');
        } else {
            $this->redirectSuccess("/tareas/elaborar/$id", 'Cambios guardados.');
        }
    }

    // -----------------------------------------------------------------------
    // Revisar
    // -----------------------------------------------------------------------

    /** GET /tareas/revisar */
    public function revisar(): void
    {
        $tareas = $this->model->porEstadoYRol(Auth::empleadoId() ?? 0, 'REVISION', 'revisor');
        $this->view('tareas/lista', [
            'pageTitle' => 'Tareas para Revisar',
            'tareas'    => $tareas,
            'tipo'      => 'revisar',
            'urlAccion' => '/tareas/revisar',
        ]);
    }

    /** GET /tareas/revisar/{id} */
    public function revisarVer(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);
        $this->view('tareas/revisar', [
            'pageTitle' => 'Revisar Tarea #' . $id,
            'tarea'     => $tarea,
        ]);
    }

    /** POST /tareas/revisar/{id} */
    public function revisarGuardar(int $id): void
    {
        Csrf::verify();
        $accion     = Request::post('accion', 'aprobar'); // 'aprobar' | 'rechazar'
        $comentario = trim((string) Request::post('comentario', ''));

        if ($accion === 'aprobar') {
            $this->model->agregarEstadoViaSP(
                $id, 'APROBACION',
                $comentario ?: 'Aprobado en revisión.',
                Auth::id() ?? 0
            );
            $this->redirectSuccess('/tareas/revisar', 'Tarea enviada a aprobación.');
        } else {
            $this->model->agregarEstadoViaSP(
                $id, 'DEVUELTO',
                $comentario ?: 'Devuelta al elaborador.',
                Auth::id() ?? 0
            );
            $this->redirectSuccess('/tareas/revisar', 'Tarea devuelta al elaborador.');
        }
    }

    // -----------------------------------------------------------------------
    // Aprobar
    // -----------------------------------------------------------------------

    /** GET /tareas/aprobar */
    public function aprobar(): void
    {
        $tareas = $this->model->porEstadoYRol(Auth::empleadoId() ?? 0, 'APROBACION', 'aprobador');
        $this->view('tareas/lista', [
            'pageTitle' => 'Tareas para Aprobar',
            'tareas'    => $tareas,
            'tipo'      => 'aprobar',
            'urlAccion' => '/tareas/aprobar',
        ]);
    }

    /** GET /tareas/aprobar/{id} */
    public function aprobarVer(int $id): void
    {
        $tarea = $this->model->detalle($id);
        if (!$tarea) $this->abort(404);
        $this->view('tareas/aprobar', [
            'pageTitle' => 'Aprobar Tarea #' . $id,
            'tarea'     => $tarea,
        ]);
    }

    /**
     * POST /tareas/aprobar/{id}
     * Al aprobar:
     * 1. INSERT versionamiento nueva versión VIGENTE
     * 2. UPDATE versiones anteriores → OBSOLETO
     * 3. UPDATE solicitud → FINALIZADA
     * 4. INSERT tarea_estado FINALIZADO
     * Todo en transacción.
     */
    public function aprobarGuardar(int $id): void
    {
        Csrf::verify();
        $accion     = Request::post('accion', 'aprobar');
        $comentario = trim((string) Request::post('comentario', ''));
        $tarea      = $this->model->detalle($id);

        if (!$tarea) $this->abort(404);

        if ($accion === 'rechazar') {
            $this->model->agregarEstadoViaSP(
                $id, 'DEVUELTO',
                $comentario ?: 'Devuelta al elaborador desde aprobación.',
                Auth::id() ?? 0
            );
            $this->redirectSuccess('/tareas/aprobar', 'Tarea devuelta al elaborador.');
        }

        // ---- APROBACIÓN ----
        try {
            // Obtener el archivo de la tarea (para el versionamiento)
            $archivos = $this->archivoModel->deEntidad('TAREA', $id);
            $rutaArchivo = !empty($archivos) ? $archivos[0]['ruta_relativa'] : null;

            // El código_documento de la solicitud es el único vínculo con el documento
            $codigoDoc    = $tarea['codigo_documento'] ?? null;
            $idEmpleado   = Auth::empleadoId() ?? 0;
            $usuarioAct   = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? '';

            // Resolver id_documento a partir del código
            $idDocumento = 0;
            if ($codigoDoc) {
                $idDocumento = (new \App\Models\DocumentoModel())->idPorCodigo($codigoDoc);
            }
            // Si no existe documento asociado, no podemos versionar
            if ($idDocumento === 0) {
                throw new \RuntimeException('No se pudo resolver el documento asociado a la tarea (código: ' . ($codigoDoc ?: 'N/A') . ').');
            }

            // Determinar número de versión
            $maxVer  = $this->verModel->maxVersion($idDocumento);
            $nuevaVer = $maxVer + 1;

            // Usar la conexión PDO para la transacción
            $db = \App\Core\Database::getInstance();
            $db->beginTransaction();

            // 1. Crear nueva versión VIGENTE con los usuarios (nombres) reales del flujo
            $usrElab = $tarea['elaborador'] ?? $usuarioAct;
            $usrRev  = $tarea['revisor']    ?? null;
            $usrApr  = $usuarioAct;

            $this->verModel->crearVersion(
                $idDocumento,
                $nuevaVer,
                'Versión aprobada desde tarea #' . $id,
                $rutaArchivo,
                'VIGENTE',
                $usrElab,
                $usrRev,
                $usrApr,
                date('Y-m-d H:i:s')
            );

            // Obtener id de la versión recién creada
            $nuevaVersionRow = $this->verModel->ultimaVersion($idDocumento);
            $idNuevaVersion  = $nuevaVersionRow['id_versionamiento'] ?? 0;

            // Si se subió archivo en la aprobación, asociar a la versión
            if (Request::hasFile('archivo')) {
                $upload = subirArchivo(
                    $_FILES['archivo'],
                    'documentos',
                    ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    20971520
                );
                $this->archivoModel->registrar('VERSIONAMIENTO', $idNuevaVersion, $upload, Auth::get('usuario'));
            } elseif ($rutaArchivo && $idNuevaVersion) {
                // Reasociar el archivo de la tarea a la versión
                if (!empty($archivos)) {
                    $db->prepare("
                        INSERT INTO archivo (modulo, id_referencia, nombre_original, nombre_storage,
                                             ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por)
                        SELECT 'VERSIONAMIENTO', ?, nombre_original, nombre_storage,
                               ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por
                        FROM archivo WHERE id_archivo = ?
                    ")->execute([$idNuevaVersion, $archivos[0]['id_archivo']]);
                }
            }

            // 2. Obsoletizar versiones anteriores
            $this->verModel->obsoletizarAnteriores($idDocumento, $idNuevaVersion);

            // 3. Finalizar solicitud
            $this->solModel->cambiarEstado(
                (int) $tarea['id_solicitud'],
                'FINALIZADA',
                date('Y-m-d H:i:s')
            );

            // 4. Estado FINALIZADO en tarea
            $this->model->agregarEstadoViaSP(
                $id, 'FINALIZADO',
                $comentario ?: 'Documento aprobado y versión ' . $nuevaVer . ' publicada.',
                Auth::id() ?? 0
            );

            $db->commit();

            registrarAuditoria('tareas', 'APROBAR', 'tarea', $id, null, [
                'id_documento' => $idDocumento,
                'nueva_version' => $nuevaVer,
            ]);
            $this->redirectSuccess('/tareas/finalizadas', 'Documento aprobado y versión v' . $nuevaVer . ' publicada como VIGENTE.');

        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Error al aprobar: Error interno. Contacte al administrador.');
            $this->redirect("/tareas/aprobar/$id");
        }
    }

    // -----------------------------------------------------------------------
    // Devueltas y Finalizadas
    // -----------------------------------------------------------------------

    /** GET /tareas/devueltas */
    public function devueltas(): void
    {
        $tareas = $this->model->porEstadoYRol(Auth::empleadoId() ?? 0, 'DEVUELTO', 'elaborador');
        $this->view('tareas/lista', [
            'pageTitle' => 'Tareas Devueltas',
            'tareas'    => $tareas,
            'tipo'      => 'devueltas',
            'urlAccion' => '/tareas/elaborar',
        ]);
    }

    /** GET /tareas/finalizadas */
    public function finalizadas(): void
    {
        $this->view('tareas/finalizadas', [
            'pageTitle' => 'Tareas Finalizadas',
            'tareas'    => $this->model->finalizadas(Auth::empleadoId() ?? 0),
        ]);
    }
}
