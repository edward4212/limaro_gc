<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Models\ArchivoModel;
use App\Core\Request;
use App\Core\Response;

/**
 * Descarga segura de archivos almacenados.
 */
class ArchivoController extends Controller
{
    /**
     * GET /archivo/{id}
     * Descarga el archivo autenticando al usuario.
     */
    public function descargar(int $id): void
    {
        // Autenticación obligatoria
        if (!Auth::check()) {
            $this->abort(401, 'Debe autenticarse para descargar archivos.');
        }

        // CA-4 HU-011: validar contexto de descarga mediante sesión (no referer)
        // El parámetro 'consulta' debe ser validado en sesión, no en query string
        $contextoDenegado = Session::get('archivo_contexto_denegado', false);
        
        if ($contextoDenegado) {
            error_log("[ArchivoController] Acceso denegado a archivo $id desde contexto documentos registrados.");
            Session::flash('error', 'Acceso no autorizado: la descarga de archivos no está disponible desde Documentos Registrados.');
            $this->abort(403, 'Acceso denegado desde este contexto.');
            return;
        }

        $model  = new ArchivoModel();
        $info   = $model->parDescarga($id);
        $inline = Request::get('inline') === '1';

        if (!$info) {
            $this->abort(404, 'Archivo no encontrado.');
        }

        if (!file_exists($info['ruta_absoluta'])) {
            $this->abort(404, 'El archivo físico no existe en el servidor.');
        }

        if ($inline) {
            $mime = $info['mime_type'] ?? mime_content_type($info['ruta_absoluta']) ?? 'application/octet-stream';

            // Tipos que los navegadores abren nativmente: PDF e imágenes
            $mimesNativos = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif',
                'image/webp', 'image/svg+xml',
            ];

            // Word y Excel NO se pueden mostrar en el navegador directamente.
            // Redirigir a Microsoft Office Online Viewer (gratuito, no requiere login).
            $mimesOffice = [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ];

            if (in_array($mime, $mimesOffice, true)) {
                // Generar token temporal firmado para acceso seguro (válido por 1 hora)
                $token = bin2hex(random_bytes(32));
                $expire = time() + 3600; // 1 hora
                
                // Guardar token en BD asociado al usuario y archivo
                $db = Database::getInstance();
                $db->prepare("
                    INSERT INTO archivo_acceso_temporal (id_archivo, id_usuario, token, expira_en)
                    VALUES (?, ?, ?, FROM_UNIXTIME(?))
                ")->execute([$id, Auth::id(), $token, $expire]);
                
                // Usar URL con token en lugar de URL pública
                $urlSegura = APP_URL . '/archivo/' . $id . '/view?token=' . $token;
                $urlVisor  = 'https://view.officeapps.live.com/op/view.aspx?src='
                           . urlencode($urlSegura);
                header('Location: ' . $urlVisor);
                exit;
            }

            if (in_array($mime, $mimesNativos, true)) {
                $nombre = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $info['nombre_original'] ?? 'archivo');
                header('Content-Type: ' . $mime);
                header('Content-Disposition: inline; filename="' . $nombre . '"');
                header('Content-Length: ' . filesize($info['ruta_absoluta']));
                header('Cache-Control: private, max-age=3600');
                readfile($info['ruta_absoluta']);
                exit;
            }

            // Tipo desconocido → descargar normalmente
        }

        Response::download($info['ruta_absoluta'], $info['nombre_original']);
    }

    /**
     * GET /archivo/v/{idVersionamiento}
     * Sirve el archivo de una versión de documento, resolviendo primero
     * la tabla `archivo` y como fallback la ruta legacy `versionamiento.documento`.
     * Reemplaza los enlaces directos a /public/storage/ (cerrados por seguridad
     * ISO 9001 §7.5.3 — control de acceso a información documentada).
     */
    public function porVersion(int $idVersionamiento): void
    {
        if (!Auth::check()) {
            $this->abort(401, 'Debe autenticarse para descargar archivos.');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT ar.id_archivo, v.documento AS ruta_legacy
               FROM versionamiento v
               LEFT JOIN archivo ar ON ar.modulo = 'VERSIONAMIENTO'
                AND ar.id_referencia = v.id_versionamiento
                AND ar.id_archivo = (
                    SELECT MAX(a2.id_archivo) FROM archivo a2
                     WHERE a2.modulo = 'VERSIONAMIENTO'
                       AND a2.id_referencia = v.id_versionamiento)
              WHERE v.id_versionamiento = ? LIMIT 1"
        );
        $stmt->execute([$idVersionamiento]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->abort(404, 'Versión de documento no encontrada.');
        }

        // Si existe registro moderno en `archivo`, delegar al flujo estándar
        if (!empty($row['id_archivo'])) {
            $this->descargar((int) $row['id_archivo']);
            return;
        }

        $this->servirRutaStorage((string) ($row['ruta_legacy'] ?? ''));
    }

    /**
     * GET /archivo/acta/{idRevision}
     * Sirve el acta de una Revisión por la Dirección (ruta legacy en BD).
     */
    public function acta(int $idRevision): void
    {
        if (!Auth::check()) {
            $this->abort(401, 'Debe autenticarse para descargar archivos.');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT archivo_acta FROM revision_direccion WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$idRevision]);
        $row = $stmt->fetch();

        if (!$row || empty($row['archivo_acta'])) {
            $this->abort(404, 'Acta no encontrada.');
        }

        $this->servirRutaStorage((string) $row['archivo_acta']);
    }

    /**
     * Sirve un archivo cuya ruta relativa proviene de la BD, validando con
     * realpath() que el destino final esté dentro de public/storage/
     * (previene path traversal con rutas manipuladas).
     */
    private function servirRutaStorage(string $rutaRelativa): void
    {
        $rutaRelativa = trim($rutaRelativa);
        if ($rutaRelativa === '') {
            $this->abort(404, 'El registro no tiene archivo asociado.');
        }

        $base = realpath(APP_ROOT . '/public/storage');
        $abs  = realpath(
            APP_ROOT . '/public/' . ltrim(str_replace(['..', "\0"], '', $rutaRelativa), '/')
        );

        if ($base === false || $abs === false || !str_starts_with($abs, $base . DIRECTORY_SEPARATOR)) {
            error_log("[ArchivoController] Ruta legacy inválida o fuera de storage: " . $rutaRelativa);
            $this->abort(404, 'El archivo físico no existe en el servidor.');
        }

        $mime   = mime_content_type($abs) ?: 'application/octet-stream';
        $nombre = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($abs));
        $inline = Request::get('inline') === '1'
                  && in_array($mime, ['application/pdf','image/jpeg','image/png','image/gif','image/webp'], true);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($abs));
        header('Cache-Control: private, max-age=3600');
        readfile($abs);
        exit;
    }

    /**
     * GET /archivo/{id}/view
     * Visualizar documento Office con validación de token temporal.
     * Seguro para ser usado en Microsoft Office Online Viewer.
     */
    public function verConToken(int $id): void
    {
        // Obtener token de query string
        $token = Request::get('token');
        
        if (!$token) {
            $this->abort(403, 'Token requerido.');
        }

        // Validar token contra BD
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT aat.*, a.id_archivo, a.ruta_relativa, a.nombre_original
            FROM archivo_acceso_temporal aat
            JOIN archivo a ON a.id_archivo = aat.id_archivo
            WHERE aat.token = ?
              AND aat.id_archivo = ?
              AND aat.expira_en > NOW()
              AND aat.usado_en IS NULL
            LIMIT 1
        ");
        $stmt->execute([$token, $id]);
        $acceso = $stmt->fetch();

        if (!$acceso) {
            error_log("[ArchivoController] Intento de acceso con token inválido o expirado: token=$token, id=$id");
            $this->abort(403, 'Token inválido o expirado.');
        }

        // M-01: invalidar el token inmediatamente (uso único)
        $db->prepare("UPDATE archivo_acceso_temporal SET usado_en = NOW() WHERE token = ? AND id_archivo = ?")
           ->execute([$token, $id]);

        // Construir ruta absoluta del archivo
        $model = new ArchivoModel();
        $info = $model->parDescarga($id);

        if (!$info) {
            $this->abort(404, 'Archivo no encontrado.');
        }

        if (!file_exists($info['ruta_absoluta'])) {
            $this->abort(404, 'El archivo no existe en el servidor.');
        }

        // Servir archivo Office con MIME type correcto
        $mime = $info['mime_type'] ?? mime_content_type($info['ruta_absoluta']) ?? 'application/octet-stream';
        $nombre = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $info['nombre_original'] ?? 'documento');

        // Headers para Office Viewer
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($info['ruta_absoluta']));
        header('Cache-Control: private, max-age=3600');
        header('Accept-Ranges: bytes');

        readfile($info['ruta_absoluta']);
        exit;
    }

    /**
     * GET /archivo/{id}/ver — Ver archivo en línea para usuarios autenticados.
     * PDF: se muestra embebido en el navegador.
     * Otros formatos: se descarga directamente.
     * No requiere token — requiere sesión activa.
     */
    public function ver(int $id): void
    {
        if (!Auth::check()) {
            $this->abort(401, 'Debe autenticarse para ver archivos.');
        }

        $info = (new \App\Models\ArchivoModel())->parDescarga($id);
        if (!$info) {
            $this->abort(404, 'Archivo no encontrado.');
        }

        $rutaAbs = $info['ruta_absoluta'];
        if (!file_exists($rutaAbs)) {
            $this->abort(404, 'El archivo físico no existe en el servidor.');
        }

        $mime     = $info['mime_type'] ?? mime_content_type($rutaAbs) ?: 'application/octet-stream';
        $nombre   = $info['nombre_original'] ?? basename($rutaAbs);
        $tamano   = filesize($rutaAbs);
        $esPdf    = str_ends_with(strtolower($nombre), '.pdf') || $mime === 'application/pdf';

        // Limpiar cualquier buffer de salida
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $tamano);
        // PDF → mostrar en navegador | Otros → descargar
        $disposition = $esPdf ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($nombre) . '"');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($rutaAbs);
        exit;
    }

}