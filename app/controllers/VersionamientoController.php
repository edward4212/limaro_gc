<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\VersionamientoModel;
use App\Models\DocumentoModel;
use App\Models\UsuarioModel;
use App\Core\Database;

/**
 * Controlador de Versionamiento.
 */
class VersionamientoController extends Controller
{
    private VersionamientoModel $model;
    private DocumentoModel      $docModel;
    private UsuarioModel        $usuarioModel;

    public function __construct()
    {
        $this->model        = new VersionamientoModel();
        $this->docModel     = new DocumentoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /** GET /versionamiento */
    public function index(): void
    {
        $this->view('documentos/versionamiento', [
            'pageTitle' => 'Versionamiento',
            'versiones' => $this->model->listar(),
        ]);
    }

    /** GET /versionamiento/documento/{id} */
    public function detalle(int $id): void
    {
        $doc = $this->docModel->conDetalle($id);
        if (!$doc) $this->abort(404, 'Documento no encontrado.');

        $this->view('documentos/versionamiento_detalle', [
            'pageTitle' => 'Historial: ' . $doc['nombre_documento'],
            'documento' => $doc,
            'versiones' => $this->model->porDocumento($id),
        ]);
    }

    /** GET /versionamiento/nueva/{id} */
    public function nueva(int $id): void
    {
        $doc = $this->docModel->find($id);
        if (!$doc) $this->abort(404);

        $ultimaVersion = $this->model->ultimaVersion($id);
        $siguiente     = $this->model->maxVersion($id) + 1;

        $this->view('documentos/versionamiento_form', [
            'pageTitle'     => 'Nueva Versión — ' . $doc['nombre_documento'],
            'documento'     => $doc,
            'ultimaVersion' => $ultimaVersion,
            'siguiente'     => $siguiente,
            'usuarios'      => $this->usuarioModel->usuariosActivosConCorreo(),
        ]);
    }

    /** POST /versionamiento/nueva/{id} */
    public function guardar(int $id): void
    {
        Csrf::verify();

        $doc = $this->docModel->find($id);
        if (!$doc) $this->abort(404);

        $data = Request::only([
            'descripcion_version',
            'id_usuario_creacion', 'id_usuario_revision', 'id_usuario_aprobacion',
            'fecha_aprobacion', 'estado_version',
        ]);

        $errors = $this->validate($data, [
            'descripcion_version'   => 'required',
            'id_usuario_creacion'   => 'required|integer',
            'id_usuario_revision'   => 'required|integer',
            'id_usuario_aprobacion' => 'required|integer',
            'estado_version'        => 'required',
        ]);

        if ($errors) {
            Session::flash('error', 'Descripción, elaborador, revisor, aprobador y estado son obligatorios.');
            $this->redirect("/versionamiento/nueva/$id");
            return;
        }

        // Segregación de funciones (recomendado, no bloqueante mientras el equipo sea pequeño):
        // advertir si la misma persona figura en más de un rol de la cadena de aprobación.
        $idsRoles = [(int)$data['id_usuario_creacion'], (int)$data['id_usuario_revision'], (int)$data['id_usuario_aprobacion']];
        if (count(array_unique($idsRoles)) < count($idsRoles)) {
            Session::flash('warning',
                'Atención: la misma persona figura en más de un rol (elaborador/revisor/aprobador) ' .
                'de esta versión. Se recomienda que sean personas distintas para mantener la segregación de funciones.');
        }

        // Obtener nombres completos desde IDs (para campos varchar legacy)
        $usrCr  = $this->usuarioModel->find((int)$data['id_usuario_creacion']);
        $usrRv  = !empty($data['id_usuario_revision'])  ? $this->usuarioModel->find((int)$data['id_usuario_revision'])  : null;
        $usrAp  = !empty($data['id_usuario_aprobacion']) ? $this->usuarioModel->find((int)$data['id_usuario_aprobacion']) : null;
        $nomCr  = $this->usuarioModel->nombreCompleto((int)$data['id_usuario_creacion']);
        $nomRv  = $usrRv  ? $this->usuarioModel->nombreCompleto((int)$data['id_usuario_revision'])  : '';
        $nomAp  = $usrAp  ? $this->usuarioModel->nombreCompleto((int)$data['id_usuario_aprobacion']) : '';

        $estado       = $data['estado_version'];
        $siguienteVer = $this->model->maxVersion($id) + 1;
        $uploadData   = null;

        // HU-N02: archivo obligatorio al crear nueva versión
        $tieneB64  = !empty($_POST['archivo_b64']);
        $tieneFile = isset($_FILES['archivo']) && ($_FILES['archivo']['error'] ?? 4) === UPLOAD_ERR_OK;
        if (!$tieneB64 && !$tieneFile) {
            Session::flash('error', 'El archivo del documento es obligatorio para crear una nueva versión.');
            $this->redirect("/versionamiento/nueva/$id");
            return;
        }

        if ($tieneB64 || $tieneFile) {
            try {
                $fileRef = $_FILES['archivo'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'size' => 0, 'tmp_name' => ''];
                $fileRef['field_name'] = 'archivo';
                $uploadData = subirArchivo(
                    $fileRef,
                    'versionamiento',
                    ['application/pdf','application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'application/vnd.ms-excel',
                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     'application/octet-stream'],
                    30971520
                );
            } catch (\Throwable $e) {
                error_log('[Versionamiento] upload: ' . $e->getMessage());
                Session::flash('error', 'Error al subir archivo: ' . $e->getMessage());
                $this->redirect("/versionamiento/nueva/$id");
                return;
            }
        }

        try {
            $idVersion = $this->model->crearVersionManual(
                $id,
                $siguienteVer,
                trim($data['descripcion_version']),
                $uploadData ? $uploadData['ruta_relativa'] : null,
                $estado,
                $nomCr,
                $nomRv,
                $nomAp,
                !empty($data['fecha_aprobacion']) ? $data['fecha_aprobacion'] : null,
                (int)$data['id_usuario_creacion'],
                !empty($data['id_usuario_revision'])  ? (int)$data['id_usuario_revision']  : null,
                !empty($data['id_usuario_aprobacion']) ? (int)$data['id_usuario_aprobacion'] : null
            );

            // HU-V04: mover archivo a la carpeta del versionamiento
            if ($uploadData !== null) {
                try {
                    $docInfo   = (new \App\Models\DocumentoModel())->conDetalle($id);
                    $rutaFinal = $uploadData['ruta_relativa'];

                    if ($docInfo) {
                        $pathInfo = getVersionPath(
                            $docInfo['macroproceso']         ?? '',
                            $docInfo['proceso']              ?? '',
                            $docInfo['subproceso']           ?? null,
                            $docInfo['tipo_documento']       ?? '',
                            $docInfo['nombre_documento']     ?? '',
                            $siguienteVer,
                            $uploadData['nombre_original']   ?? 'documento',
                            $docInfo['sigla_proceso']        ?? '',
                            $docInfo['sigla_tipo_documento'] ?? '',
                            $docInfo['codigo']               ?? ''
                        );
                        $absOrigen = APP_ROOT . '/public' . $uploadData['ruta_relativa'];
                        if (!is_dir($pathInfo['carpeta_abs'])) {
                            mkdir($pathInfo['carpeta_abs'], 0755, true);
                        }
                        if (file_exists($absOrigen) && copy($absOrigen, $pathInfo['absoluta'])) {
                            @unlink($absOrigen);
                            $rutaFinal = $pathInfo['relativa'];
                            $this->model->actualizarRutaDocumento($idVersion, $rutaFinal);
                        }
                        $uploadData['ruta_relativa'] = $rutaFinal;
                    }
                    (new \App\Models\ArchivoModel())->registrar(
                        'VERSIONAMIENTO', $idVersion, $uploadData, Auth::id()
                    );
                } catch (\Throwable $e) {
                    error_log('[Versionamiento] mover/registrar archivo: ' . $e->getMessage());
                }
            }

            if ($estado === 'VIGENTE') {
                $this->model->obsoletizarAnteriores($id, $idVersion);
            }

            registrarAuditoria('versionamiento', 'CREAR_VERSION', 'versionamiento', $idVersion, null, [
                'id_documento' => $id, 'numero_version' => $siguienteVer, 'estado' => $estado,
            ]);

            // HU-014: Correo a todos los usuarios activos
            try {
                $usuarios = $this->usuarioModel->usuariosActivosConCorreo();
                notifVersionCreada([
                    'id_versionamiento' => $idVersion,
                    'numero_version'    => $siguienteVer,
                    'estado_version'    => $estado,
                    'nombre_documento'  => $doc['nombre_documento'],
                    'codigo'            => $doc['codigo'],
                    'descripcion'       => trim($data['descripcion_version']),
                    'elaborador'        => $nomCr,
                ], $usuarios, (int)(Auth::id() ?? 0));
            } catch (\Throwable $e) {
                error_log('[HU-014] notifVersionCreada: ' . $e->getMessage());
            }

            $this->redirectSuccess(
                "/versionamiento/documento/$id",
                "Versión V$siguienteVer creada como <strong>$estado</strong>."
            );

        } catch (\Throwable $e) {
            error_log('[Versionamiento] guardar: ' . $e->getMessage());
            Session::flash('error', 'Error al guardar: ' . $e->getMessage());
            $this->redirect("/versionamiento/nueva/$id");
        }
    }

    /** POST /versionamiento/cambiar-estado/{id} */
    public function cambiarEstado(int $idVersion): void
    {
        Csrf::verify();
        $nuevoEstado = Request::post('estado_version', '');
        $idDocumento = (int) Request::post('id_documento', 0);

        if (!in_array($nuevoEstado, ['VIGENTE', 'OBSOLETO', 'CREADO'], true)) {
            Session::flash('error', 'Estado no válido.');
            $this->redirect("/versionamiento/documento/$idDocumento");
            return;
        }

        $antes = $this->model->find($idVersion);
        if (!$antes) $this->abort(404);

        // Segregación de funciones: solo el usuario designado como aprobador de
        // ESTA versión puede pasarla a VIGENTE (equivale a "aprobar" el documento).
        // ADMINISTRADOR conserva override por si el aprobador original ya no está disponible.
        if ($nuevoEstado === 'VIGENTE') {
            $idAprobadorAsignado = (int) ($antes['id_usuario_aprobacion'] ?? 0);
            $esAprobadorAsignado = $idAprobadorAsignado > 0 && $idAprobadorAsignado === (int) Auth::id();
            if (!$esAprobadorAsignado && !Auth::hasRole([1])) {
                Session::flash('error',
                    'Solo el usuario designado como aprobador de esta versión puede marcarla como VIGENTE.');
                $this->redirect("/versionamiento/documento/$idDocumento");
                return;
            }
        }

        if ($nuevoEstado === 'VIGENTE') {
            $this->model->obsoletizarAnteriores($idDocumento, $idVersion);
        }

        $this->model->actualizarEstado(
            $idVersion,
            $nuevoEstado,
            $nuevoEstado === 'OBSOLETO' ? date('Y-m-d H:i:s') : null
        );

        registrarAuditoria('versionamiento', 'CAMBIAR_ESTADO', 'versionamiento', $idVersion, $antes, [
            'estado_nuevo' => $nuevoEstado,
        ]);

        $this->redirectSuccess(
            "/versionamiento/documento/$idDocumento",
            "Estado actualizado a <strong>$nuevoEstado</strong>."
        );
    }

    /** GET /versionamiento/descargar/{id} */
    public function descargar(int $id): void
    {
        $doc  = $this->docModel->find($id);
        if (!$doc) $this->abort(404);
        $info = $this->docModel->infoCarpeta($id);
        if (!$info) $this->abort(404);

        try {
            // Crear carpeta si no existe para evitar RuntimeException
            $relativa        = carpetaDocumento(
                $info['macroproceso'], $info['proceso'],
                $info['nombre_subproceso'] ?? null,
                $info['tipo_documento'],    $doc['nombre_documento']
            );
            $carpetaAbsoluta = APP_ROOT . '/public/storage/documentos/' . $relativa;
            if (!is_dir($carpetaAbsoluta)) {
                mkdir($carpetaAbsoluta, 0755, true);
            }

            // HU-019 CA-3: contar archivos en la carpeta
            $nArchivos = 0;
            if (is_dir($carpetaAbsoluta)) {
                $iter = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($carpetaAbsoluta, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iter as $f2) { if ($f2->isFile()) $nArchivos++; }
            }

            if ($nArchivos === 0) {
                // HU-N03: si no hay archivos físicos, buscar en tabla archivo (nuevo sistema)
                $archivosEnBD = (new \App\Models\ArchivoModel())->deEntidad('VERSIONAMIENTO', $id);
                // deEntidad usa id_documento como id_referencia para VERSIONAMIENTO global
                // buscar también por cada id_versionamiento
                if (empty($archivosEnBD)) {
                    $versiones = $this->model->porDocumento($id);
                    foreach ($versiones as $v) {
                        $arcsVer = (new \App\Models\ArchivoModel())->deEntidad('VERSIONAMIENTO', (int)$v['id_versionamiento']);
                        $archivosEnBD = array_merge($archivosEnBD, $arcsVer);
                    }
                }
                if (empty($archivosEnBD)) {
                    Session::flash('error', 'La carpeta del documento está vacía. No hay archivos para descargar.');
                    $this->redirect('/versionamiento/documento/' . $id);
                    return;
                }
                // Generar ZIP desde archivos en BD
                $tmpZip = tempnam(sys_get_temp_dir(), 'limaro_doc_') . '.zip';
                $zip = new \ZipArchive();
                $zip->open($tmpZip, \ZipArchive::CREATE);
                foreach ($archivosEnBD as $ar) {
                    $abs = APP_ROOT . '/public' . $ar['ruta_relativa'];
                    if (file_exists($abs)) {
                        $zip->addFile($abs, $ar['nombre_original'] ?? basename($abs));
                    }
                }
                $zip->close();
                $nombreDescarga = sanitizarSegmentoCarpeta($doc['nombre_documento']) . '.zip';
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
                header('Content-Length: ' . filesize($tmpZip));
                readfile($tmpZip);
                unlink($tmpZip);
                exit;
            }

            $zipPath        = zipCarpetaDocumento(
                $info['macroproceso'], $info['proceso'],
                $info['nombre_subproceso'] ?? null,
                $info['tipo_documento'],    $doc['nombre_documento']
            );
            $nombreDescarga = sanitizarSegmentoCarpeta($doc['nombre_documento']) . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Pragma: no-cache');
            readfile($zipPath);
            unlink($zipPath);
            exit;
        } catch (\Throwable $e) {
            error_log('[descargar] id=' . $id . ' | ' . $e->getMessage());
            Session::flash('error',
                'No se pudo generar la descarga: <strong>' . htmlspecialchars($e->getMessage()) . '</strong>'
            );
            $this->redirect('/versionamiento/documento/' . $id);
        }
    }

    /**
     * GET /versionamiento/reemplazar/{id_ver}
     * Formulario para reemplazar el archivo sin crear version nueva.
     * Solo ADMINISTRADOR, COORDINADOR CALIDAD o LIDER PROCESO.
     */
    public function reemplazarForm(int $id_ver): void
    {
        $rolesPermitidos = ['ADMINISTRADOR', 'COORDINADOR CALIDAD', 'LIDER PROCESO'];
        if (!Auth::hasRole($rolesPermitidos)) $this->abort(403);

        $version = $this->model->find($id_ver);
        if (!$version) $this->abort(404);

        $doc = $this->docModel->find($version['id_documento']);
        if (!$doc) $this->abort(404);

        // Solo versiones VIGENTE pueden reemplazarse
        if (($version['estado_version'] ?? '') !== 'VIGENTE') {
            Session::flash('error', 'Solo se puede reemplazar el archivo de versiones en estado VIGENTE.');
            $this->redirect("/versionamiento/documento/{$version['id_documento']}");
        }

        $archivoModel  = new \App\Models\ArchivoModel();
        $archivosActual = $archivoModel->deEntidad('VERSIONAMIENTO', $id_ver);

        $this->view('documentos/versionamiento_reemplazar', [
            'pageTitle'     => 'Reemplazar Archivo — ' . $doc['nombre_documento'],
            'version'       => $version,
            'documento'     => $doc,
            'archivoActual' => $archivosActual[0] ?? null,
        ]);
    }

    /**
     * POST /versionamiento/reemplazar/{id_ver}
     * Guarda el nuevo archivo reemplazando el actual sin crear version nueva.
     */
    public function reemplazarGuardar(int $id_ver): void
    {
        Csrf::verify();

        $rolesPermitidos = ['ADMINISTRADOR', 'COORDINADOR CALIDAD', 'LIDER PROCESO'];
        if (!Auth::hasRole($rolesPermitidos)) $this->abort(403);

        $version = $this->model->find($id_ver);
        if (!$version) $this->abort(404);

        $idDocumento = (int)$version['id_documento'];
        $doc         = $this->docModel->find($idDocumento);
        if (!$doc) $this->abort(404);

        // Validar que viene el archivo (Base64 o $_FILES)
        $tieneB64  = !empty($_POST['archivo_nuevo_b64']);
        $tieneFile = isset($_FILES['archivo_nuevo']) && ($_FILES['archivo_nuevo']['error'] ?? 4) === UPLOAD_ERR_OK;

        if (!$tieneB64 && !$tieneFile) {
            Session::flash('error', 'Debe seleccionar un archivo.');
            $this->redirect("/versionamiento/reemplazar/{$id_ver}");
        }

        try {
            // 1. Subir nuevo archivo — Base64 o $_FILES
            $fileRef = $_FILES['archivo_nuevo'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'size' => 0, 'tmp_name' => ''];
            $fileRef['field_name'] = 'archivo_nuevo';

            $uploadData = subirArchivo(
                $fileRef,
                'versionamiento',
                ['application/pdf','application/msword',
                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'application/vnd.ms-excel',
                 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                 'application/vnd.ms-powerpoint',
                 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                 'application/octet-stream'],
                30971520  // 30 MB
            );

            // 2. Mover a la carpeta correcta de esta versión
            $docInfo = $this->docModel->conDetalle($idDocumento);
            if ($docInfo) {
                $pathInfo = getVersionPath(
                    $docInfo['macroproceso']         ?? '',
                    $docInfo['proceso']              ?? '',
                    $docInfo['subproceso']           ?? null,
                    $docInfo['tipo_documento']       ?? '',
                    $docInfo['nombre_documento']     ?? '',
                    (int)$version['numero_version'],
                    $uploadData['nombre_original'],
                    $docInfo['sigla_proceso']        ?? '',
                    $docInfo['sigla_tipo_documento'] ?? '',
                    $docInfo['codigo']               ?? ''
                );
                $absOrigen = APP_ROOT . '/public' . $uploadData['ruta_relativa'];
                if (!is_dir($pathInfo['carpeta_abs'])) {
                    mkdir($pathInfo['carpeta_abs'], 0755, true);
                }
                if (file_exists($absOrigen)) {
                    rename($absOrigen, $pathInfo['absoluta']);
                    $uploadData['ruta_relativa'] = $pathInfo['relativa'];
                }
            }

            // 3. Eliminar archivos anteriores registrados para esta versión
            $archivoModel = new \App\Models\ArchivoModel();
            $anteriores   = $archivoModel->deEntidad('VERSIONAMIENTO', $id_ver);
            foreach ($anteriores as $ant) {
                $rutaAbs = APP_ROOT . '/public' . $ant['ruta_relativa'];
                if (file_exists($rutaAbs)) @unlink($rutaAbs);
                Database::getInstance()->prepare(
                    "DELETE FROM archivo WHERE id_archivo = ?"
                )->execute([$ant['id_archivo']]);
            }

            // 4. Registrar nuevo archivo
            $archivoModel->registrar(
                'VERSIONAMIENTO',
                $id_ver,
                $uploadData,
                Auth::id() ?? 1
            );

            // 5. Auditoría
            registrarAuditoria(
                'versionamiento', 'REEMPLAZAR_ARCHIVO', 'versionamiento', $id_ver,
                null, ['codigo' => $doc['codigo'], 'version' => $version['numero_version']]
            );

            $this->redirectSuccess(
                "/versionamiento/documento/{$idDocumento}",
                "Archivo reemplazado correctamente en V{$version['numero_version']}."
            );

        } catch (\Throwable $e) {
            error_log('[VersionamientoController::reemplazarGuardar] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            Session::flash('error', 'Error: ' . $e->getMessage());
            $this->redirect("/versionamiento/reemplazar/{$id_ver}");
        }
    }

    /** GET /versionamiento/inactivos — HU-020 */
    public function inactivos(): void
    {
        $this->view('documentos/versionamiento_inactivos', [
            'pageTitle'  => 'Documentos Inactivos / Obsoletos',
            'documentos' => $this->model->inactivos(),
        ]);
    }

    /** POST /versionamiento/reactivar/{id} — HU-020 */
    public function reactivar(int $id): void
    {
        Csrf::verify();
        if (!Auth::puede('versionamiento', 'editar')) $this->abort(403);

        $doc = $this->docModel->find($id);
        if (!$doc) $this->abort(404);

        $antes = $doc;
        $this->docModel->update($id, ['estado' => 'ACTIVO']);
        registrarAuditoria('versionamiento', 'REACTIVAR', 'documento', $id, $antes, ['estado' => 'ACTIVO']);
        $this->redirectSuccess('/versionamiento', 'Documento reactivado correctamente.');
    }

}