<?php

/**
 * Helper de subida segura de archivos.
 *
 * Soporta DOS métodos:
 *  1. Base64 POST (recomendado): el JS codifica el archivo antes de enviar.
 *     No requiere upload_tmp_dir en el servidor.
 *  2. $_FILES clásico: fallback si el servidor tiene upload_tmp_dir disponible.
 *
 * Uso en controladores (sin cambios):
 *   $upload = subirArchivo($_FILES['campo'], 'modulo', [...mimes...], maxBytes);
 *
 * El helper detecta automáticamente si viene base64 o $_FILES.
 * Para que funcione base64, el formulario debe usar el JS de app.js (class="upload-b64")
 */

if (!function_exists('subirArchivo')) {

    /**
     * Subir archivo de forma segura.
     * Detecta automáticamente base64 (POST) o $_FILES clásico.
     *
     * @param array  $file      Elemento de $_FILES['campo'] (puede tener error=UPLOAD_ERR_NO_TMP_DIR)
     * @param string $modulo    Subdirectorio destino en storage/
     * @param array  $mimesOk   MIMEs permitidos
     * @param int    $maxBytes  Tamaño máximo en bytes
     */
    function subirArchivo(
        array  $file,
        string $modulo   = 'documentos',
        ?array $mimesOk  = ['application/pdf'],
        int    $maxBytes = 20971520
    ): array {

        // ── Intentar ruta Base64 (viene de JS, campo POST {name}_b64) ──────
        // El JS pone el nombre del campo en $file['name'] como referencia
        $fieldRef = $file['field_name'] ?? null;

        // Buscar datos base64 en POST: {fieldname}_b64, {fieldname}_mime, {fieldname}_nombre
        $b64Data  = null;
        $b64Mime  = null;
        $b64Name  = null;

        if ($fieldRef) {
            $b64Data = $_POST[$fieldRef . '_b64']    ?? null;
            $b64Mime = $_POST[$fieldRef . '_mime']   ?? null;
            $b64Name = $_POST[$fieldRef . '_nombre'] ?? null;
        }

        // Si no hay field_name, buscar cualquier campo _b64 no vacío
        if (!$b64Data) {
            foreach ($_POST as $k => $v) {
                if (str_ends_with($k, '_b64') && !empty($v)) {
                    $base = substr($k, 0, -4);
                    // Verificar que corresponde a este campo de archivo
                    if (!$fieldRef || $base === $fieldRef) {
                        $b64Data = $v;
                        $b64Mime = $_POST[$base . '_mime']   ?? 'application/octet-stream';
                        $b64Name = $_POST[$base . '_nombre'] ?? ($file['name'] ?? 'archivo');
                        break;
                    }
                }
            }
        }

        if ($b64Data) {
            return _subirDesdeBase64($b64Data, $b64Mime, $b64Name, $modulo, $mimesOk, $maxBytes);
        }

        // ── Fallback: $_FILES clásico ────────────────────────────────────────
        return _subirDesdeFiles($file, $modulo, $mimesOk, $maxBytes);
    }

    /**
     * @internal — Guardar archivo desde datos Base64 (sin directorio temporal)
     */
    function _subirDesdeBase64(
        string $b64Data,
        string $mimeCliente,
        string $nombreOriginal,
        string $modulo,
        ?array $mimesOk,
        int    $maxBytes
    ): array {

        // Limpiar header de data URI si viene (data:application/pdf;base64,...)
        if (str_contains($b64Data, ',')) {
            [, $b64Data] = explode(',', $b64Data, 2);
        }

        // Decodificar
        $binData = base64_decode(trim($b64Data), true);
        if ($binData === false || strlen($binData) === 0) {
            throw new \RuntimeException('El archivo recibido está vacío o corrompido.');
        }

        // Validar tamaño
        $tamano = strlen($binData);
        if ($tamano > $maxBytes) {
            $max = round($maxBytes / 1048576, 1);
            throw new \RuntimeException("El archivo supera el tamaño máximo de {$max} MB.");
        }

        // Detectar MIME real desde los bytes del archivo
        $mimeReal = _detectarMimeDeBytes($binData, $mimeCliente, $nombreOriginal);

        // Validar MIME (null = aceptar cualquier tipo — HU-N05/N07/N09)
        if ($mimesOk !== null && !in_array($mimeReal, $mimesOk, true)) {
            throw new \RuntimeException(
                'Tipo de archivo no permitido: ' . htmlspecialchars($mimeReal)
            );
        }

        // Sanitizar nombre
        $nombreSanitizado = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $nombreOriginal);
        $nombreSanitizado = substr(trim($nombreSanitizado), 0, 250) ?: 'archivo';

        // Obtener extensión (nunca permitir extensiones ejecutables del servidor)
        $ext = _mimeAExtension($mimeReal) ?? strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) ?? 'bin';
        if (_extensionPeligrosa($ext)) { $ext = 'bin'; }

        // Hash de integridad
        $hash = hash('sha256', $binData);

        // Rutas
        $nombreStorage = $hash . '_' . time() . '.' . $ext;
        $rutaRelativa  = '/storage/' . $modulo . '/' . $nombreStorage;
        $rutaAbsoluta  = APP_ROOT . '/public' . $rutaRelativa;

        // Crear directorio
        $dir = dirname($rutaAbsoluta);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException('No se pudo crear el directorio de almacenamiento.');
        }

        // Escribir archivo directo (sin directorio temporal)
        if (file_put_contents($rutaAbsoluta, $binData) === false) {
            throw new \RuntimeException('No se pudo escribir el archivo en el disco.');
        }

        return [
            'nombre_original' => $nombreSanitizado,
            'nombre_storage'  => $nombreStorage,
            'ruta_relativa'   => $rutaRelativa,
            'mime_type'       => $mimeReal,
            'tamano_bytes'    => $tamano,
            'hash_sha256'     => $hash,
        ];
    }

    /**
     * @internal — Subir desde $_FILES clásico
     */
    function _subirDesdeFiles(
        array  $file,
        string $modulo,
        ?array $mimesOk,
        int    $maxBytes
    ): array {

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errores = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta. Intente de nuevo.',
                UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene directorio temporal configurado. Active la opción Base64 en el formulario.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                UPLOAD_ERR_EXTENSION  => 'Una extensión PHP bloqueó la subida.',
            ];
            throw new \RuntimeException(
                $errores[$file['error']] ?? 'Error PHP al subir archivo (código: ' . $file['error'] . ').'
            );
        }

        if ($file['size'] > $maxBytes) {
            $max = round($maxBytes / 1048576, 1);
            throw new \RuntimeException("El archivo supera {$max} MB.");
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($file['tmp_name']);
        $extOrig  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Si MIME es genérico usar extensión
        if (in_array($mimeReal, ['application/octet-stream','binary/octet-stream'], true)) {
            $porExt = [
                'pdf'=>'application/pdf','doc'=>'application/msword',
                'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'=>'application/vnd.ms-excel','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                'gif'=>'image/gif','webp'=>'image/webp',
            ];
            $mimeReal = $porExt[$extOrig] ?? $mimeReal;
        }

        if ($mimesOk !== null && !in_array($mimeReal, $mimesOk, true)) {
            throw new \RuntimeException('Tipo no permitido: ' . htmlspecialchars($mimeReal));
        }

        $hash      = hash_file('sha256', $file['tmp_name']);
        $ext       = _mimeAExtension($mimeReal) ?? $extOrig ?? 'bin';
        if (_extensionPeligrosa($ext)) { $ext = 'bin'; }
        $nombre    = preg_replace('/[^a-zA-Z0-9._\-\s]/','', $file['name']);
        $nombre    = substr(trim($nombre), 0, 250) ?: 'archivo';
        $storage   = $hash . '_' . time() . '.' . $ext;
        $relativa  = '/storage/' . $modulo . '/' . $storage;
        $absoluta  = APP_ROOT . '/public' . $relativa;

        $dir = dirname($absoluta);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], $absoluta)) {
            throw new \RuntimeException('No se pudo mover el archivo al destino.');
        }

        return [
            'nombre_original' => $nombre,
            'nombre_storage'  => $storage,
            'ruta_relativa'   => $relativa,
            'mime_type'       => $mimeReal,
            'tamano_bytes'    => $file['size'],
            'hash_sha256'     => $hash,
        ];
    }

    /** @internal */
    function _detectarMimeDeBytes(string $bytes, string $mimeCliente, string $nombre): string
    {
        // Magic bytes para tipos comunes
        $sig = substr($bytes, 0, 8);
        if (substr($sig, 0, 4) === '%PDF') return 'application/pdf';
        if (substr($sig, 0, 2) === "\xFF\xD8")   return 'image/jpeg';
        if (substr($sig, 0, 4) === "\x89PNG")    return 'image/png';
        if (substr($sig, 0, 6) === 'GIF87a' || substr($sig,0,6) === 'GIF89a') return 'image/gif';
        if (substr($sig, 0, 4) === 'RIFF')       return 'image/webp';
        if (substr($sig, 0, 4) === "\xD0\xCF\x11\xE0") return 'application/msword';
        if (substr($sig, 0, 4) === 'PK'."\x03\x04") {
            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            $map = ['docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            return $map[$ext] ?? 'application/zip';
        }

        // Seguridad (ARQ-002): NUNCA confiar en el MIME declarado por el
        // cliente. Si los magic bytes no son reconocibles, detectar con
        // finfo sobre el contenido real.
        $finfoMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if ($finfoMime && $finfoMime !== 'application/octet-stream') {
            return $finfoMime;
        }

        // Sin firma reconocible → octet-stream (será rechazado por la
        // lista de tipos permitidos, que ya no lo incluye).
        return 'application/octet-stream';
    }

    /** @internal */
    function _mimeAExtension(string $mime): ?string
    {
        $map = [
            'application/pdf'   => 'pdf',
            'application/msword'=> 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'image/jpeg' => 'jpg', 'image/png' => 'png',
            'image/gif'  => 'gif', 'image/webp' => 'webp',
        ];
        return $map[$mime] ?? null;
    }

    /**
     * @internal — Extensiones que el servidor podría interpretar/ejecutar.
     * Defensa en profundidad: aunque /storage/ deniega todo acceso directo,
     * nunca debe persistirse un archivo con extensión ejecutable.
     */
    function _extensionPeligrosa(string $ext): bool
    {
        return in_array(strtolower($ext), [
            'php','php3','php4','php5','php7','php8','phtml','phar',
            'pl','py','cgi','sh','bash','exe','bat','cmd','com',
            'js','mjs','jsp','asp','aspx','htaccess','htpasswd','ini',
        ], true);
    }
}

if (!function_exists('eliminarArchivo')) {
    function eliminarArchivo(string $rutaRelativa): bool
    {
        $ruta = realpath(APP_ROOT . '/public' . $rutaRelativa);
        $base = realpath(APP_ROOT . '/public/storage');
        if (!$ruta || !$base || !str_starts_with($ruta, $base)) return false;
        return file_exists($ruta) ? unlink($ruta) : false;
    }
}

// [archivoUrl] eliminada — sin uso en codebase
