<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\TareaModel;
use App\Models\SolicitudModel;
use App\Models\VersionamientoModel;
use App\Models\ArchivoModel;
use App\Models\DocumentoModel;
use App\Models\EmpleadoModel;

/**
 * TareaService
 *
 * Contiene toda la lógica de negocio del flujo de tareas:
 *   iniciar → enviarARevision → enviarAAprobacion
 *             ↓ (devolver)         ↓ (devolver)
 *         elaborador ←────── revisor ←──── aprobador
 *                                              ↓ (aprobar)
 *                                          FINALIZADO
 *
 * El controlador solo valida input HTTP, llama al servicio y redirige.
 * Este servicio no tiene acceso a Request, Session ni Response.
 *
 * Cada método lanza \RuntimeException con mensaje legible si el flujo
 * no puede completarse — el controlador los captura y muestra al usuario.
 */
class TareaService
{
    public function __construct(
        private TareaModel           $tareaModel,
        private SolicitudModel       $solModel,
        private VersionamientoModel  $verModel,
        private ArchivoModel         $archivoModel,
        private NotificacionTareaService $notif
    ) {}

    // ────────────────────────────────────────────────────────────────────
    // Iniciar
    // ────────────────────────────────────────────────────────────────────

    /**
     * Inicia una tarea desde una solicitud asignada.
     * Crea la tarea, cambia la solicitud a EN DESARROLLO y registra comentario.
     *
     * @return int  id_tarea creado
     */
    public function iniciar(int $idSolicitud): int
    {
        $idEmpleado = Auth::empleadoId() ?? 0;

        // HU-N10: verificar que el empleado asignado existe y está activo
        if (!$idEmpleado) {
            throw new \RuntimeException(
                'No se encontró un empleado asociado al usuario actual. '
                . 'Verifique la configuración del usuario en Seguridad → Usuarios.'
            );
        }
        $empModel = new \App\Models\EmpleadoModel();
        $empleado = $empModel->find($idEmpleado);
        if (!$empleado || ($empleado['estado_empleado'] ?? '') !== 'ACTIVO') {
            throw new \RuntimeException(
                'El empleado asignado no existe o está inactivo. '
                . 'Contacte al administrador del sistema.'
            );
        }

        $idTarea = $this->tareaModel->crearViaSP($idSolicitud, $idEmpleado);

        $this->tareaModel->agregarEstadoViaSP(
            $idTarea, 'CREADO',
            'Tarea iniciada por ' . (Auth::get('usuario') ?? 'sistema'),
            Auth::id() ?? 0
        );

        $this->solModel->cambiarEstado($idSolicitud, 'EN_DESARROLLO');

        $usuario  = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? 'Sistema';
        $fechaIni = date('d/m/Y H:i');
        $this->solModel->comentarioSistema(
            $idSolicitud,
            "Tarea #{$idTarea} iniciada por {$usuario} el {$fechaIni}.",
            'TAREA'
        );

        registrarAuditoria('tareas', 'INICIAR', 'tarea', $idTarea, null, [
            'id_solicitud' => $idSolicitud,
        ]);

        return $idTarea;
    }

    // ────────────────────────────────────────────────────────────────────
    // Elaborar → Revisión
    // ────────────────────────────────────────────────────────────────────

    /**
     * Crea o actualiza el documento v0 (BORRADOR) para solicitudes de CREACION.
     * Llamado por el controlador antes de enviarARevision cuando el tipo es CREACION.
     *
     * @param array $solicitud  Datos de la solicitud (find())
     * @param array $params     ['nombre_documento', 'id_proceso', 'id_subproceso']
     * @param int   $idTarea    Para vincular el archivo de la tarea a la v0
     * @throws \RuntimeException si faltan datos o falla la BD
     */
    public function crearOActualizarDocumentoV0(array $solicitud, array $params, int $idTarea): int
    {
        $nombreDoc = trim($params['nombre_documento'] ?? '');
        $idProceso = (int)($params['id_proceso'] ?? 0);
        $idSubproc = (int)($params['id_subproceso'] ?? 0) ?: null;
        $idTipoDoc = (int)($solicitud['id_tipo_documento'] ?? 0);
        $objetivo  = trim($solicitud['solicitud'] ?? '');
        $idSol     = (int)$solicitud['id_solicitud'];

        if (!$nombreDoc || !$idProceso || !$idTipoDoc) {
            throw new \RuntimeException(
                'Para solicitudes de CREACIÓN debe completar: ' .
                'Nombre del documento, Proceso' .
                (!$idTipoDoc ? ' y Tipo de documento' : '') . '.'
            );
        }

        $db     = Database::getInstance();
        $docMod = new DocumentoModel();

        // Si ya existe documento v0 (tarea devuelta y re-elaborada) → actualizar
        $idDocumento = null;
        if (!empty($solicitud['codigo_documento'])) {
            $idDocumento = $docMod->idPorCodigo($solicitud['codigo_documento']);
        }

        if ($idDocumento) {
            $db->prepare("UPDATE documento SET nombre_documento=?, id_proceso=?, id_subproceso=? WHERE id_documento=?")
               ->execute([$nombreDoc, $idProceso, $idSubproc, $idDocumento]);
        } else {
            $idDocumento = $docMod->crear($idTipoDoc, $idProceso, $nombreDoc, $objetivo, Auth::id(), $idSubproc);
            $docNuevo    = $docMod->find($idDocumento);
            $nuevoCod    = $docNuevo['codigo'] ?? '';
            $db->prepare("UPDATE solicitud SET codigo_documento=? WHERE id_solicitud=?")
               ->execute([$nuevoCod, $idSol]);
        }

        // Versión 0 (BORRADOR)
        $archTarea = $this->archivoModel->deEntidad('TAREA', $idTarea);
        $rutaV0    = !empty($archTarea) ? $archTarea[0]['ruta_relativa'] : null;
        $usuNom    = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? '';

        $v0stmt = $db->prepare(
            "SELECT id_versionamiento FROM versionamiento WHERE id_documento=? AND numero_version=0 LIMIT 1"
        );
        $v0stmt->execute([$idDocumento]);
        $v0Row = $v0stmt->fetch();

        if ($v0Row) {
            $idV0 = (int)$v0Row['id_versionamiento'];
            $db->prepare(
                "UPDATE versionamiento SET documento=?, usuario_creacion=?, id_usuario_creacion=?, fecha_creacion=NOW()
                  WHERE id_versionamiento=?"
            )->execute([$rutaV0, $usuNom, Auth::id(), $idV0]);
        } else {
            $idV0 = (int)$this->verModel->crearVersion(
                $idDocumento, 0, 'Versión inicial — en elaboración',
                $rutaV0, 'CREADO', $usuNom
            );
            if ($idV0) {
                $db->prepare("UPDATE versionamiento SET id_usuario_creacion=? WHERE id_versionamiento=?")
                   ->execute([Auth::id(), $idV0]);
            }
        }

        // Vincular archivo de la tarea a la v0
        if (!empty($archTarea) && !empty($idV0)) {
            $db->prepare("
                INSERT IGNORE INTO archivo
                    (modulo, id_referencia, nombre_original, nombre_storage,
                     ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por)
                SELECT 'VERSIONAMIENTO', ?, nombre_original, nombre_storage,
                       ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por
                FROM archivo WHERE id_archivo = ?
            ")->execute([$idV0, $archTarea[0]['id_archivo']]);
        }

        error_log("[TareaService] Documento v0 creado/actualizado: id={$idDocumento}");
        return $idDocumento;
    }

    /**
     * Envía una tarea a revisión: registra asignación, comentario y correo.
     */
    public function enviarARevision(int $idTarea, array $tarea, int $idRevisor, string $comentario = ''): void
    {
        $empModel   = new EmpleadoModel();
        $revisor    = $empModel->find($idRevisor);
        $nombreRev  = $revisor['nombre_completo'] ?? "Empleado #{$idRevisor}";
        $usuario    = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? 'Sistema';
        $fecha      = date('d/m/Y H:i');
        $idSol      = (int)$tarea['id_solicitud'];
        $por        = Auth::get('usuario') ?? 'sistema';

        $this->solModel->completarAsignacionRol($idSol, 'ELABORADOR');
        $this->solModel->cancelarAsignacionRol($idSol, 'REVISOR');
        $this->solModel->upsertAsignacion($idSol, $idRevisor, 'REVISOR', $por);

        $this->solModel->comentarioSistema(
            $idSol,
            "Documento elaborado por {$usuario} el {$fecha}. Enviado a revisión de {$nombreRev}.",
            'TAREA'
        );

        $correoRev = $empModel->correoYNombre($idRevisor);
        if ($correoRev) {
            $this->notif->documentoEnviadoARevision(
                $idTarea, $tarea,
                $correoRev['correo_empleado'] ?? '',
                $nombreRev,
                $usuario
            );
        }

        $this->tareaModel->agregarEstadoViaSP(
            $idTarea, 'REVISION',
            $comentario ?: "Enviado a revisión de {$nombreRev}.",
            Auth::id() ?? 0
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Revisar → Aprobación / Devolución al elaborador
    // ────────────────────────────────────────────────────────────────────

    /**
     * Envía una tarea a aprobación: registra asignación, comentario y correo.
     */
    public function enviarAAprobacion(int $idTarea, array $tarea, int $idAprobador, string $comentario = ''): void
    {
        $empModel    = new EmpleadoModel();
        $aprobador   = $empModel->find($idAprobador);
        $nombreApro  = $aprobador['nombre_completo'] ?? "Empleado #{$idAprobador}";
        $usuario     = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? 'Sistema';
        $fecha       = date('d/m/Y H:i');
        $idSol       = (int)$tarea['id_solicitud'];
        $por         = Auth::get('usuario') ?? 'sistema';

        $this->solModel->completarAsignacionRol($idSol, 'REVISOR');
        $this->solModel->cancelarAsignacionRol($idSol, 'APROBADOR');
        $this->solModel->upsertAsignacion($idSol, $idAprobador, 'APROBADOR', $por);

        $this->solModel->comentarioSistema(
            $idSol,
            "Revisión aprobada por {$usuario} el {$fecha}. Enviado a aprobación de {$nombreApro}.",
            'TAREA'
        );

        $correoApro = $empModel->correoYNombre($idAprobador);
        if ($correoApro) {
            $this->notif->documentoEnviadoAAprobacion(
                $idTarea, $tarea,
                $correoApro['correo_empleado'] ?? '',
                $nombreApro,
                $usuario
            );
        }

        $this->tareaModel->agregarEstadoViaSP(
            $idTarea, 'APROBACION',
            $comentario ?: "Revisado por {$usuario}. Enviado a aprobación de {$nombreApro}.",
            Auth::id() ?? 0
        );
    }

    /**
     * Devuelve una tarea al elaborador desde la etapa de revisión.
     */
    public function devolverAlElaborador(int $idTarea, array $tarea, string $comentario = ''): void
    {
        $usuario = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? 'Sistema';
        $fecha   = date('d/m/Y H:i');
        $idSol   = (int)$tarea['id_solicitud'];

        $this->solModel->completarAsignacionRol($idSol, 'REVISOR');
        $this->solModel->reactivarAsignacionRol($idSol, 'ELABORADOR');

        $this->solModel->comentarioSistema(
            $idSol,
            "Documento devuelto por {$usuario} el {$fecha}. Motivo: " . ($comentario ?: 'Sin especificar') . ".",
            'TAREA'
        );

        // Obtener elaborador ACTIVO desde las asignaciones de la tarea
        // Filtrar por estado ACTIVA para no usar asignaciones de rondas anteriores (BUG FIX)
        $elaborador = null;
        foreach ($tarea['asignaciones'] ?? [] as $a) {
            if (strtolower($a['rol_asignacion'] ?? '') === 'elaborador'
                && ($a['estado'] ?? 'ACTIVA') === 'ACTIVA') {
                $elaborador = $a;
                break;
            }
        }
        // Fallback: si no hay elaborador ACTIVO, tomar el último COMPLETADO
        if (!$elaborador) {
            foreach (array_reverse($tarea['asignaciones'] ?? []) as $a) {
                if (strtolower($a['rol_asignacion'] ?? '') === 'elaborador') {
                    $elaborador = $a;
                    break;
                }
            }
        }
        $nombreElab = $elaborador['nombre_completo'] ?? 'elaborador';

        if ($elaborador) {
            $empModel   = new EmpleadoModel();
            $correoElab = $empModel->correoYNombre((int)$elaborador['id_empleado']);
            if ($correoElab) {
                $this->notif->documentoDevueltoAlElaborador(
                    $idTarea, $tarea,
                    $correoElab['correo_empleado'] ?? '',
                    $nombreElab,
                    $usuario,
                    $comentario
                );
            }

            // Confirmación al revisor
            $correoRevis = $empModel->correoYNombre(Auth::empleadoId() ?? 0);
            if ($correoRevis) {
                $this->notif->confirmacionDevolucionRevisor(
                    $idTarea, $tarea,
                    $correoRevis['correo_empleado'] ?? '',
                    $usuario,
                    $nombreElab
                );
            }
        }

        $this->tareaModel->agregarEstadoViaSP(
            $idTarea, 'DEVUELTO',
            $comentario ?: 'Devuelta al elaborador para corrección.',
            Auth::id() ?? 0
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Aprobar → Finalizado / Devolución al revisor
    // ────────────────────────────────────────────────────────────────────

    /**
     * Devuelve una tarea al revisor desde la etapa de aprobación.
     */
    public function devolverAlRevisor(int $idTarea, array $tarea, string $comentario = ''): void
    {
        $usuario = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? 'Sistema';
        $fecha   = date('d/m/Y H:i');
        $idSol   = (int)$tarea['id_solicitud'];

        $this->solModel->reactivarAsignacionRol($idSol, 'REVISOR');
        $this->solModel->cancelarAsignacionRol($idSol, 'APROBADOR');

        $this->solModel->comentarioSistema(
            $idSol,
            "Documento devuelto desde aprobación por {$usuario} el {$fecha}. Motivo: " . ($comentario ?: 'Sin especificar') . ".",
            'TAREA'
        );

        $revisor    = $this->solModel->empleadoPorRol($idSol, 'REVISOR');
        $empModel   = new EmpleadoModel();

        if ($revisor) {
            $this->notif->documentoDevueltoAlRevisor(
                $idTarea, $tarea,
                $revisor['correo_empleado'] ?? '',
                $revisor['nombre_completo'] ?? '',
                $usuario,
                $comentario
            );
        }

        $correoApro = $empModel->correoYNombre(Auth::empleadoId() ?? 0);
        if ($correoApro) {
            $this->notif->confirmacionDevolucionAprobador(
                $idTarea, $tarea,
                $correoApro['correo_empleado'] ?? '',
                $usuario,
                $revisor['nombre_completo'] ?? 'el revisor',
                $comentario
            );
        }

        $this->tareaModel->agregarEstadoViaSP(
            $idTarea, 'DEVUELTO',
            $comentario ?: 'Devuelta desde aprobación.',
            Auth::id() ?? 0
        );
    }

    /**
     * Aprueba una tarea: crea versión VIGENTE, obsoletiza anteriores,
     * finaliza solicitud y notifica.
     *
     * @param array $postData  Datos del formulario ['nombre_documento', 'id_proceso', 'id_subproceso']
     * @throws \RuntimeException si el flujo no puede completarse
     */
    public function aprobar(int $idTarea, array $tarea, string $comentario = '', array $postData = []): void
    {
        $codigoDoc  = $tarea['codigo_documento'] ?? null;
        $usuarioAct = Auth::get('nombre_completo') ?? Auth::get('usuario') ?? '';
        $fecha      = date('d/m/Y H:i');
        $idSol      = (int)$tarea['id_solicitud'];

        $solicitud     = $this->solModel->find($idSol);
        $tipoSolicitud = $solicitud['tipo_solicitud'] ?? '';

        if (empty($tipoSolicitud)) {
            throw new \RuntimeException(
                "No se pudo leer el tipo de solicitud (id_solicitud: {$idSol})."
            );
        }

        $docModel = new DocumentoModel();
        $archivos = $this->archivoModel->deEntidad('TAREA', $idTarea);
        $rutaArchivo = !empty($archivos) ? $archivos[0]['ruta_relativa'] : null;

        // ── ELIMINACION ──────────────────────────────────────────────────
        if ($tipoSolicitud === 'ELIMINACION') {
            $idDocumento = $docModel->idPorCodigo($codigoDoc ?? '');
            if (!$idDocumento) {
                throw new \RuntimeException("Documento '{$codigoDoc}' no encontrado para eliminación.");
            }

            $this->verModel->obsoletizarAnteriores($idDocumento, 0);

            $nomDoc = $docModel->find($idDocumento)['nombre_documento'] ?? $codigoDoc;
            $this->solModel->comentarioSistema(
                $idSol,
                "⛔ Se inactivó el documento «{$nomDoc}» ({$codigoDoc}). "
                . "Todas sus versiones VIGENTE pasaron a OBSOLETO. "
                . "Inactivado por {$usuarioAct} el {$fecha}."
                . ($comentario ? " Motivo: {$comentario}" : ''),
                'TAREA'
            );

            $this->solModel->cambiarEstado($idSol, 'FINALIZADA', date('Y-m-d H:i:s'));
            $this->tareaModel->agregarEstadoViaSP(
                $idTarea, 'FINALIZADO',
                $comentario ?: "Documento {$codigoDoc} marcado como obsoleto.",
                Auth::id() ?? 0
            );
            $this->solModel->completarAsignacionRol($idSol, 'APROBADOR');

            registrarAuditoria('documentos', 'OBSOLETIZAR', 'documento', $idDocumento, null, [
                'codigo'      => $codigoDoc,
                'solicitante' => $usuarioAct,
            ]);
            return;
        }

        // ── CREACION / ACTUALIZACION ─────────────────────────────────────
        $db = Database::getInstance();

        if ($tipoSolicitud === 'CREACION') {
            $idDocumento = !empty($codigoDoc) ? $docModel->idPorCodigo($codigoDoc) : null;

            if (empty($idDocumento)) {
                $nombreDoc = trim($postData['nombre_documento'] ?? '');
                $idProceso = (int)($postData['id_proceso'] ?? 0);
                $idSubproc = (int)($postData['id_subproceso'] ?? 0) ?: null;
                $idTipoDoc = (int)($solicitud['id_tipo_documento'] ?? 0);
                $objetivo  = trim($solicitud['solicitud'] ?? '');

                if (!$nombreDoc || !$idProceso || !$idTipoDoc) {
                    throw new \RuntimeException(
                        'Faltan datos del documento: ' .
                        (!$nombreDoc ? 'nombre_documento ' : '') .
                        (!$idProceso ? 'id_proceso ' : '') .
                        (!$idTipoDoc ? 'id_tipo_documento' : '')
                    );
                }

                $idDocumento = $docModel->crear($idTipoDoc, $idProceso, $nombreDoc, $objetivo, Auth::id(), $idSubproc);
                $docNuevo    = $docModel->find($idDocumento);
                $codigoDoc   = $docNuevo['codigo'] ?? $codigoDoc;
                $db->prepare("UPDATE solicitud SET codigo_documento=? WHERE id_solicitud=?")
                   ->execute([$codigoDoc, $idSol]);
            }
        } else {
            // ACTUALIZACION
            $idDocumento = $docModel->idPorCodigo($codigoDoc ?? '');
            if (!$idDocumento) {
                throw new \RuntimeException(
                    "Documento '{$codigoDoc}' no encontrado. Verifique el código en la solicitud."
                );
            }
        }

        $maxVer   = $this->verModel->maxVersion($idDocumento);
        $nuevaVer = $maxVer + 1;

        $db->beginTransaction();
        try {
            $this->verModel->crearVersion(
                $idDocumento, $nuevaVer,
                'Versión aprobada desde tarea #' . $idTarea,
                $rutaArchivo, 'VIGENTE',
                $tarea['elaborador'] ?? $usuarioAct,
                $tarea['revisor'] ?? null,
                $usuarioAct,
                date('Y-m-d H:i:s')
            );

            $nuevaVersionRow = $this->verModel->ultimaVersion($idDocumento);
            $idNuevaVersion  = (int)($nuevaVersionRow['id_versionamiento'] ?? 0);

            // Normalizar FKs de usuarios
            if ($idNuevaVersion) {
                $db->prepare(
                    "UPDATE versionamiento
                        SET id_usuario_creacion   = ?,
                            id_usuario_revision   = ?,
                            id_usuario_aprobacion = ?
                      WHERE id_versionamiento = ?"
                )->execute([
                    $tarea['elaborador_id_usuario'] ?? null,
                    $tarea['revisor_id_usuario']    ?? null,
                    Auth::id(),
                    $idNuevaVersion,
                ]);
            }

            // HU-V03: mover archivo de TAREA a la carpeta del VERSIONAMIENTO
            if ($idNuevaVersion && !empty($archivos)) {
                $archivoOrigen = $archivos[0];
                $rutaAbsOrigen = APP_ROOT . '/public' . $archivoOrigen['ruta_relativa'];

                // Obtener datos del documento para construir la ruta destino
                $docInfo = (new \App\Models\DocumentoModel())->conDetalle($idDocumento);

                $moverOk     = false;
                $rutaDestino = $archivoOrigen['ruta_relativa']; // fallback: misma ruta

                if ($docInfo && file_exists($rutaAbsOrigen)) {
                    $pathInfo = getVersionPath(
                        $docInfo['macroproceso']    ?? '',
                        $docInfo['proceso']         ?? '',
                        $docInfo['subproceso']      ?? null,
                        $docInfo['tipo_documento']  ?? '',
                        $docInfo['nombre_documento'] ?? '',
                        $nuevaVer,
                        $archivoOrigen['nombre_original'] ?? 'documento',
                        $docInfo['sigla_proceso']         ?? '',
                        $docInfo['sigla_tipo_documento']  ?? '',
                        $docInfo['codigo']                ?? ''
                    );

                    // Crear carpeta si no existe
                    if (!is_dir($pathInfo['carpeta_abs'])) {
                        mkdir($pathInfo['carpeta_abs'], 0755, true);
                    }

                    // Mover (copiar + eliminar original solo si copia exitosa)
                    if (copy($rutaAbsOrigen, $pathInfo['absoluta'])) {
                        @unlink($rutaAbsOrigen);
                        $rutaDestino = $pathInfo['relativa'];
                        $moverOk     = true;
                        // Actualizar ruta en tabla archivo original
                        $db->prepare(
                            "UPDATE archivo SET ruta_relativa = ? WHERE id_archivo = ?"
                        )->execute([$rutaDestino, $archivoOrigen['id_archivo']]);
                        // Actualizar campo legado versionamiento.documento
                        $db->prepare(
                            "UPDATE versionamiento SET documento = ? WHERE id_versionamiento = ?"
                        )->execute([$rutaDestino, $idNuevaVersion]);
                    } else {
                        error_log(sprintf(
                            '[TareaService::aprobar] No se pudo mover archivo de "%s" a "%s"',
                            $rutaAbsOrigen, $pathInfo['absoluta']
                        ));
                    }
                }

                // Registrar en tabla archivo para VERSIONAMIENTO (nueva ruta)
                $db->prepare("
                    INSERT INTO archivo
                        (modulo, id_referencia, nombre_original, nombre_storage,
                         ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por)
                    SELECT 'VERSIONAMIENTO', ?, nombre_original, nombre_storage,
                           ?, mime_type, tamano_bytes, hash_sha256, subido_por
                    FROM archivo WHERE id_archivo = ?
                ")->execute([$idNuevaVersion, $rutaDestino, $archivoOrigen['id_archivo']]);
            }

            $this->verModel->obsoletizarAnteriores($idDocumento, $idNuevaVersion);
            $this->solModel->cambiarEstado($idSol, 'FINALIZADA', date('Y-m-d H:i:s'));

            $this->tareaModel->agregarEstadoViaSP(
                $idTarea, 'FINALIZADO',
                $comentario ?: "Documento aprobado y versión {$nuevaVer} publicada.",
                Auth::id() ?? 0
            );

            $docInfo  = $docModel->find($idDocumento);
            $nomDoc   = $docInfo['nombre_documento'] ?? '';
            $codDoc   = $codigoDoc ?? ($docInfo['codigo'] ?? '');
            $this->solModel->comentarioSistema(
                $idSol,
                "✅ Se aprobó el documento «{$nomDoc}» ({$codDoc}) y se publicó la Versión v{$nuevaVer} como VIGENTE. "
                . "Aprobado por {$usuarioAct} el {$fecha}.",
                'TAREA'
            );

            $this->solModel->completarAsignacionRol($idSol, 'APROBADOR');

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e; // re-lanzar para que el controlador muestre el error
        }

        // Notificar publicación (fuera de la transacción: un fallo de correo no revierte)
        try {
            $notifData = [
                'id_versionamiento' => $idNuevaVersion ?? 0,
                'codigo'            => $tarea['codigo_documento'] ?? '',
                'nombre_documento'  => $docInfo['nombre_documento'] ?? $tarea['codigo_documento'],
                'numero_version'    => $nuevaVer,
                'estado_version'    => 'VIGENTE',
                'descripcion'       => $comentario ?: "Versión v{$nuevaVer} aprobada por {$usuarioAct}.",
                'elaborador'        => $tarea['elaborador'] ?? $usuarioAct,
            ];
            $usuarios = (new \App\Models\UsuarioModel())->usuariosActivosConCorreo();
            notifVersionCreada($notifData, $usuarios, (int)(Auth::id() ?? 0));
        } catch (\Throwable $eM) {
            error_log('[TareaService::aprobar] notifVersionCreada: ' . $eM->getMessage());
        }

        registrarAuditoria('tareas', 'APROBAR', 'tarea', $idTarea, null, [
            'id_documento'  => $idDocumento,
            'nueva_version' => $nuevaVer,
        ]);
    }
}
