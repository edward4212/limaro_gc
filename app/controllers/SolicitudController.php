<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;
use App\Models\TipoDocumentoModel;
use App\Models\EmpleadoModel;
use App\Models\ArchivoModel;

/**
 * Controlador de Solicitudes.
 * Gestiona radicar solicitudes y la gestión de las mismas.
 */
class SolicitudController extends Controller
{
    private SolicitudModel     $model;
    private TipoDocumentoModel $tipoModel;
    private EmpleadoModel      $empModel;
    private ArchivoModel       $archivoModel;
    private UsuarioModel       $usuarioModel;

    private array $prioridades = [
        'URGENTE_IMPORTANTE'      => 'Urgente e Importante',
        'URGENTE_NO_IMPORTANTE'   => 'Urgente, No Importante',
        'NO_URGENTE_IMPORTANTE'   => 'No Urgente, Importante',
        'NO_URGENTE_NO_IMPORTANTE'=> 'No Urgente, No Importante',
    ];

    public function __construct()
    {
        $this->model        = new SolicitudModel();
        $this->usuarioModel = new UsuarioModel();
        $this->tipoModel    = new TipoDocumentoModel();
        $this->empModel     = new EmpleadoModel();
        $this->archivoModel = new ArchivoModel();
    }

    // -----------------------------------------------------------------------
    // Radicar Solicitudes
    // -----------------------------------------------------------------------

    /** GET /solicitudes/mis-radicadas */
    public function misRadicadas(): void
    {
        $idEmp = Auth::empleadoId() ?? 0;
        $this->view('solicitudes/mis_radicadas', [
            'pageTitle'   => 'Mis Solicitudes Radicadas',
            'solicitudes' => $this->model->misRadicadas($idEmp),
            'resumen'     => $this->model->resumenPorEmpleado($idEmp),
        ]);
    }

    /** GET /solicitudes/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('solicitudes/form_radicar', [
            'pageTitle'     => 'Radicar Solicitud — Crear Documento',
            'tipoSolicitud' => 'CREACION',
            'tipos'         => $this->tipoModel->activos(),
            'prioridades'   => $this->prioridades,
        ]);
    }

    /** POST /solicitudes/crear */
    public function guardarCrear(): void
    {
        Csrf::verify();
        $this->guardarSolicitud('CREACION', '/solicitudes/crear');
    }

    /** GET /solicitudes/actualizar */
    public function actualizar(): void
    {
        Session::clearOldInput();
        $this->view('solicitudes/form_radicar', [
            'pageTitle'     => 'Radicar Solicitud — Actualizar Documento',
            'tipoSolicitud' => 'ACTUALIZACION',
            'tipos'         => $this->tipoModel->activos(),
            'prioridades'   => $this->prioridades,
        ]);
    }

    /** POST /solicitudes/actualizar */
    public function guardarActualizar(): void
    {
        Csrf::verify();
        $this->guardarSolicitud('ACTUALIZACION', '/solicitudes/actualizar');
    }

    /** GET /solicitudes/eliminar */
    public function eliminar(): void
    {
        Session::clearOldInput();
        $this->view('solicitudes/form_radicar', [
            'pageTitle'     => 'Radicar Solicitud — Inactivar Documento',
            'tipoSolicitud' => 'ELIMINACION',
            'tipos'         => $this->tipoModel->activos(),
            'prioridades'   => $this->prioridades,
        ]);
    }

    /** POST /solicitudes/eliminar */
    public function guardarEliminar(): void
    {
        Csrf::verify();
        $this->guardarSolicitud('ELIMINACION', '/solicitudes/eliminar');
    }

    /**
     * Lógica común para guardar solicitud.
     */
    private function guardarSolicitud(string $tipoSolicitud, string $backUrl): never
    {
        $data   = Request::only(['prioridad', 'descripcion', 'id_tipo_documento', 'id_documento']);
        // Si el select estaba disabled (bloqueado por JS), leer el hidden backup desde POST
        if (empty($data['id_tipo_documento'])) {
            $data['id_tipo_documento'] = Request::input('id_tipo_documento_hidden', '');
        }
        $errors = $this->validate($data, [
            'descripcion'       => 'required',
            'id_tipo_documento' => 'required|integer',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect($backUrl);
        }

        $idEmpleado = Auth::empleadoId();
        if (!$idEmpleado) {
            Session::flash('error', 'No se encontró el empleado del usuario.');
            $this->redirect($backUrl);
        }

        try {
            $idSolicitud = $this->model->crearViaSP(
                $tipoSolicitud,
                $data['prioridad'] ?? 'NO_URGENTE_IMPORTANTE',
                trim($data['descripcion']),
                $idEmpleado,
                (int) $data['id_tipo_documento'],
                $data['id_documento'] ? (int) $data['id_documento'] : null
            );

            // Agregar comentario inicial si aplica
            if (!empty($data['descripcion'])) {
                $this->model->agregarComentario($idSolicitud, $idEmpleado, trim($data['descripcion']));
            }

            // Subir adjunto si existe (Base64 o $_FILES clásico)
            $tieneB64  = !empty($_POST['adjunto_b64']);
            $tieneFile = isset($_FILES['adjunto']) && ($_FILES['adjunto']['error'] ?? 4) === UPLOAD_ERR_OK;
            if ($tieneB64 || $tieneFile) {
                try {
                    $fileRef = $_FILES['adjunto'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'size' => 0, 'tmp_name' => ''];
                    $fileRef['field_name'] = 'adjunto';
                    // HU-N05/N07/N09: aceptar cualquier tipo de archivo de evidencia
                    // La lista de MIMEs se abre; el límite de tamaño (20MB) se mantiene.
                    // La validación de extensiones peligrosas la hace _extensionPeligrosa() en upload.php
                    $upload = subirArchivo(
                        $fileRef,
                        'solicitudes',
                        null,   // sin filtro de tipo — acepta todo
                        20971520
                    );
                    $this->archivoModel->registrar('SOLICITUD', $idSolicitud, $upload, Auth::id());
                } catch (\Throwable $e) {
                    error_log('[SolicitudController] adjunto: ' . $e->getMessage());
                    Session::flash('warning', 'Solicitud creada, adjunto no pudo subirse: ' . $e->getMessage());
                }
            }

            registrarAuditoria('solicitudes', 'CREAR', 'solicitud', $idSolicitud, null, [
                'tipo' => $tipoSolicitud,
                'prioridad' => $data['prioridad'] ?? '',
            ]);
            // HU-017: Notificar al solicitante, Coordinador de Calidad y Líder de Proceso
            try {
                $solCompleta   = $this->model->find($idSolicitud);

                // Solicitante: usar id_empleado de sesión o de la solicitud
                $idEmpNotif    = $idEmpleado ?? (int)($solCompleta['id_empleado'] ?? 0);
                $solicitanteEm = $idEmpNotif
                    ? $this->usuarioModel->correoEmpleado($idEmpNotif)
                    : null;

                $coordinadores = $this->usuarioModel->usuariosPorRol('COORDINADOR CALIDAD');
                $lideres       = $this->usuarioModel->usuariosPorRol('LIDER PROCESO');

                // Log para diagnóstico
                error_log('[HU-017] Solicitud #' . $idSolicitud .
                    ' | Solicitante: ' . ($solicitanteEm['correo_empleado'] ?? 'sin correo') .
                    ' | Coordinadores: ' . count($coordinadores) .
                    ' | Líderes: ' . count($lideres));

                $resultado = notifSolicitudCreada(
                    array_merge($solCompleta ?? [], ['id_solicitud' => $idSolicitud]),
                    $solicitanteEm,
                    $coordinadores,
                    $lideres,
                    (int)(Auth::id() ?? 0)
                );

                error_log('[HU-017] Enviados: ' . $resultado['enviados'] .
                    ' | Fallidos: ' . $resultado['fallidos']);
            } catch (\Throwable $e) {
                error_log('[HU-017] Error: ' . $e->getMessage());
            }
            $this->redirectSuccess('/solicitudes/mis-radicadas', 'Solicitud radicada exitosamente. ID: ' . $idSolicitud);

        } catch (\Throwable $e) {
            Session::flash('error', 'Error al radicar: Error interno. Contacte al administrador.');
            Session::setOldInput($data);
            $this->redirect($backUrl);
        }
    }

    // -----------------------------------------------------------------------
    // Gestión de Solicitudes
    // -----------------------------------------------------------------------

    /** GET /solicitudes/radicadas */
    public function radicadas(): void
    {
        $this->view('solicitudes/gestion', [
            'pageTitle'   => 'Solicitudes Radicadas',
            'solicitudes' => $this->model->porEstado('CREADA'),
            'resumen'     => $this->model->resumenEstados(),
            'estado'      => 'CREADA',
            'empleados'   => $this->empModel->elaboradores(),
        ]);
    }

    /** GET /solicitudes/asignadas */
    public function asignadas(): void
    {
        $this->view('solicitudes/gestion', [
            'pageTitle'   => 'Solicitudes Asignadas',
            'solicitudes' => $this->model->porEstado('ASIGNADA'),
            'resumen'     => $this->model->resumenEstados(),
            'estado'      => 'ASIGNADA',
            'empleados'   => $this->empModel->elaboradores(), // HU-019
        ]);
    }

    /** GET /solicitudes/desarrollo */
    public function desarrollo(): void
    {
        $this->view('solicitudes/gestion', [
            'pageTitle'   => 'Solicitudes en Desarrollo',
            'solicitudes' => $this->model->porEstado('EN_DESARROLLO'),
            'resumen'     => $this->model->resumenEstados(),
            'estado'      => 'EN_DESARROLLO',
            'empleados'   => [],
        ]);
    }

    /** GET /solicitudes/finalizadas */
    public function finalizadas(): void
    {
        $desde = Request::get('desde');
        $hasta = Request::get('hasta');
        $this->view('solicitudes/finalizadas', [
            'pageTitle'   => 'Solicitudes Finalizadas',
            'solicitudes' => $this->model->finalizadas($desde ?: null, $hasta ?: null),
            'desde'       => $desde,
            'hasta'       => $hasta,
            'resumen'     => $this->model->resumenEstados(),
        ]);
    }

    /** GET /solicitudes/ver/{id} */
    public function ver(int $id): void
    {
        $detalle = $this->model->detalle($id);
        if (!$detalle) $this->abort(404, 'Solicitud no encontrada.');

        // HU-N04: verificar si tiene tarea asignada para ocultar "Finalizar sin Trámite"
        $tieneTarea = $this->model->tieneTareaActiva($id);
        $this->view('solicitudes/ver', [
            'pageTitle'   => 'Solicitud #' . $id,
            'sol'         => $detalle,
            'empleados'   => $this->empModel->elaboradores(),
            'tieneTarea'  => $tieneTarea,
        ]);
    }

    /** POST /solicitudes/asignar/{id} */
    public function asignar(int $id): void
    {
        Csrf::verify();
        $idEmpleado = (int) Request::post('id_empleado', 0);

        if (!$idEmpleado) {
            $this->redirectError("/solicitudes/ver/$id", 'Debe seleccionar un empleado.');
        }

        // Obtener nombre del elaborador para el comentario automático
        $elaborador = $this->empModel->find($idEmpleado);
        $nombreElab = $elaborador['nombre_completo'] ?? "Empleado #$idEmpleado";
        $fechaAsig  = date('d/m/Y H:i');

        // Asignar en solicitud_asignacion
        $this->model->asignar(
            $id,
            $idEmpleado,
            'ELABORADOR',
            Auth::get('usuario') ?? 'sistema'
        );

        // CA-2 HU-018: actualizar campo normalizado id_empleado_asignado
        $this->model->actualizarEmpleadoAsignado($id, $idEmpleado);

        // CA-2 HU-018: comentario automático con tipo_comentario='ASIGNACION'
        $this->model->comentarioSistema(
            $id,
            "Solicitud asignada a {$nombreElab} el {$fechaAsig}.",
            'ASIGNACION'
        );

        registrarAuditoria('solicitudes', 'ASIGNAR', 'solicitud', $id, null, [
            'id_empleado'  => $idEmpleado,
            'nombre'       => $nombreElab,
        ]);
        $this->redirectSuccess("/solicitudes/ver/$id",
            "Solicitud asignada a <strong>{$nombreElab}</strong>.");
    }

    /** POST /solicitudes/comentar/{id} */
    public function comentar(int $id): void
    {
        Csrf::verify();
        // CA-1 HU-024: bloquear comentarios en solicitudes finalizadas/cerradas
        $sol = $this->model->detalle($id);
        if ($sol && in_array($sol['estado_solicitud'] ?? '', ['FINALIZADA','CERRADA'])) {
            Session::flash('error', 'No se pueden agregar comentarios a una solicitud finalizada.');
            $this->redirect("/solicitudes/ver/$id");
            return;
        }
        $comentario = trim((string) Request::post('comentario', ''));

        if (empty($comentario)) {
            $this->redirectError("/solicitudes/ver/$id", 'El comentario no puede estar vacío.');
        }

        $this->model->agregarComentario($id, Auth::empleadoId() ?? 0, $comentario);
        $this->redirectSuccess("/solicitudes/ver/$id", 'Comentario agregado.');
    }

    /** POST /solicitudes/reasignar/{id} — HU-019 */
    public function reasignar(int $id): void
    {
        Csrf::verify();
        $idNuevoEmpleado = (int) Request::post('id_empleado_nuevo', 0);

        if (!$idNuevoEmpleado) {
            $this->redirectError("/solicitudes/ver/$id", 'Debe seleccionar el nuevo elaborador.');
        }

        // Obtener el elaborador actual (asignación activa)
        $asigActiva  = $this->model->asignacionActiva($id, 'ELABORADOR');
        $anteriorNom = $asigActiva['nombre_completo'] ?? 'Sin asignar';
        $anteriorId  = $asigActiva['id_empleado']    ?? 0;

        if ($anteriorId === $idNuevoEmpleado) {
            $this->redirectError("/solicitudes/ver/$id",
                'El nuevo elaborador es el mismo que el actual.');
        }

        // Obtener datos del nuevo elaborador
        $nuevoElab  = $this->empModel->find($idNuevoEmpleado);
        $nuevoNom   = $nuevoElab['nombre_completo'] ?? "Empleado #$idNuevoEmpleado";
        $fechaReas  = date('d/m/Y H:i');
        $asigPor    = Auth::get('usuario') ?? 'Sistema';

        // 1. Cancelar asignación anterior
        if ($asigActiva) {
            $this->model->cancelarAsignacionRol($id, 'ELABORADOR');
        }

        // 2. Crear nueva asignación
        $this->model->asignar($id, $idNuevoEmpleado, 'ELABORADOR', $asigPor);

        // 3. Actualizar id_empleado_asignado normalizado
        $this->model->actualizarEmpleadoAsignado($id, $idNuevoEmpleado, $nuevoNom);

        // 4. Comentario automático tipo REASIGNACION
        $this->model->comentarioSistema(
            $id,
            "Reasignado de {$anteriorNom} a {$nuevoNom} el {$fechaReas}.",
            'REASIGNACION',
            $asigPor
        );

        // 5. Correo al nuevo asignado
        try {
            $correoNuevo = $this->empModel->correoYNombre($idNuevoEmpleado);
            if ($correoNuevo && filter_var($correoNuevo['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $sol     = $this->model->find($id);
                $asunto  = "[SGC] Solicitud #{$id} — Reasignada a usted";
                $htmlMsg = "
                    <h2 style='color:#1e5fbf;margin-top:0;'>📋 Solicitud Asignada a Usted</h2>
                    <p>Hola <strong>" . htmlspecialchars($nuevoNom) . "</strong>,</p>
                    <p>Se le ha asignado la siguiente solicitud para elaboración:</p>
                    <table style='width:100%;border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                      <tr style='background:#f8fafc;'><td style='padding:10px 16px;color:#6b7280;font-weight:600;width:160px;border-bottom:1px solid #e5e7eb;'>ID Solicitud:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><strong>#{$id}</strong></td></tr>
                      <tr><td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Tipo:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>" . htmlspecialchars($sol['tipo_solicitud'] ?? '') . "</td></tr>
                      <tr style='background:#f8fafc;'><td style='padding:10px 16px;color:#6b7280;font-weight:600;'>Documento:</td><td style='padding:10px 16px;'><span>" . htmlspecialchars($sol['codigo_documento'] ?? '') . "</span></td></tr>
                    </table>
                    <br>
                    <a href='" . APP_URL . "/solicitudes/ver/{$id}'
                       style='background:#1e5fbf;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
                       Ver Solicitud →
                    </a>
                    <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";
                enviarCorreo(
                    [$correoNuevo['correo_empleado'] => $nuevoNom],
                    $asunto, $htmlMsg
                );
                _registrarNotificacionLog('REASIGNACION', $id, 'solicitud',
                    $correoNuevo['correo_empleado'], $nuevoNom, $asunto,
                    'ENVIADO', null, (int)(Auth::id() ?? 0));
            }
        } catch (\Throwable $e) {
            error_log('[HU-019] correo reasignacion: ' . $e->getMessage());
        }

        registrarAuditoria('solicitudes', 'REASIGNAR', 'solicitud', $id, null, [
            'anterior' => $anteriorNom,
            'nuevo'    => $nuevoNom,
        ]);
        $this->redirectSuccess("/solicitudes/ver/$id",
            "Solicitud reasignada de <strong>{$anteriorNom}</strong> a <strong>{$nuevoNom}</strong>.");
    }


    /** GET /solicitudes/panel */
    public function panel(): void
    {
        $this->view('solicitudes/panel', [
            'pageTitle'   => 'Panel de Solicitudes',
            'solicitudes' => $this->model->todas(),
            'resumen'     => $this->model->resumenEstados(),
        ]);
    }


    /**
     * POST /solicitudes/finalizar-sin-tramite/{id}
     * HU-027: finalizar solicitud sin tramitarla (roles con permisos)
     */
    public function finalizarSinTramite(int $id): void
    {
        Csrf::verify();

        // CA-1: solo Administrador, Coordinador de Calidad y Líder de Proceso
        // CA-1: solo Administrador, Coordinador de Calidad y Líder de Proceso
        if (!Auth::hasRole([1, 2, 3]) && !Auth::puede('solicitudes_gestion', 'editar')) {
            $this->abort(403);
        }

        $sol = $this->model->detalle($id);
        if (!$sol) $this->abort(404);

        // CA-3: no puede reabrirse — ya finalizada
        if (in_array($sol['estado_solicitud'], ['FINALIZADA', 'FINALIZADA_SIN_TRAMITE'])) {
            Session::flash('error', 'Esta solicitud ya está finalizada y no puede modificarse.');
            $this->redirect("/solicitudes/ver/$id");
            return;
        }

        // CA-2: comentario obligatorio
        $motivo = trim(Request::post('motivo', ''));
        if (mb_strlen($motivo) < 10) {
            Session::flash('error', 'El motivo es obligatorio (mínimo 10 caracteres).');
            $this->redirect("/solicitudes/ver/$id");
            return;
        }

        // Actualizar estado
        $this->model->update($id, ['estado_solicitud' => 'FINALIZADA_SIN_TRAMITE']);

        // Registrar comentario con el motivo
        $this->model->agregarComentario($id, Auth::id(),
            "🔒 Finalizada sin trámite — Motivo: $motivo"
        );

        registrarAuditoria('solicitudes', 'FINALIZAR_SIN_TRAMITE', 'solicitud', $id,
            ['estado_solicitud' => $sol['estado_solicitud']],
            ['estado_solicitud' => 'FINALIZADA_SIN_TRAMITE', 'motivo' => $motivo]
        );

        $this->redirectSuccess("/solicitudes/ver/$id",
            'Solicitud finalizada sin trámite. El motivo quedó registrado.'
        );
    }

}