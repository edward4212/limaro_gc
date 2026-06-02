<?php

/**
 * Helper de subida segura de archivos.
 * Valida MIME, tamaño, sanitiza nombre, guarda en storage.
 */

if (!function_exists('subirArchivo')) {
    /**
     * Subir archivo de forma segura.
     *
     * @param array  $file      Elemento de $_FILES
     * @param string $modulo    Subdirectorio destino (documentos|acuerdos|organigrama)
     * @param array  $mimesOk   MIMEs permitidos
     * @param int    $maxBytes  Tamaño máximo en bytes (default 20MB)
     * @return array{nombre_original:string, nombre_storage:string, ruta_relativa:string,
     *               mime_type:string, tamano_bytes:int, hash_sha256:string}
     * @throws RuntimeException Si la validación falla
     */
    function subirArchivo(
        array  $file,
        string $modulo    = 'documentos',
        array  $mimesOk   = ['application/pdf', 'application/msword',
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        int    $maxBytes  = 20971520
    ): array {
        // Verificar error de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errores = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta.',
                UPLOAD_ERR_NO_TMP_DIR => 'No se encontró directorio temporal.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo.',
            ];
            throw new RuntimeException($errores[$file['error']] ?? 'Error al subir archivo.');
        }

        // Validar tamaño
        if ($file['size'] > $maxBytes) {
            $max = round($maxBytes / 1048576, 1);
            throw new RuntimeException("El archivo supera el tamaño máximo de {$max} MB.");
        }

        // Validar MIME real (no confiar en Content-Type del cliente)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($file['tmp_name']);

        if (!in_array($mimeReal, $mimesOk, true)) {
            throw new RuntimeException(
                'Tipo de archivo no permitido: ' . htmlspecialchars($mimeReal) . '. ' .
                'Tipos aceptados: ' . implode(', ', $mimesOk)
            );
        }

        // Calcular hash SHA-256 para integridad
        $hash = hash_file('sha256', $file['tmp_name']);

        // Sanitizar nombre original
        $nombreOriginal = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $file['name']);
        $nombreOriginal = substr(trim($nombreOriginal), 0, 250) ?: 'archivo';

        // Obtener extensión segura desde MIME
        $extensiones = [
            'application/pdf'          => 'pdf',
            'application/msword'       => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',
            'image/jpeg'               => 'jpg',
            'image/png'                => 'png',
            'image/gif'                => 'gif',
            'image/webp'               => 'webp',
        ];
        $ext = $extensiones[$mimeReal] ?? 'bin';

        // Nombre de storage = hash + timestamp (no reversible)
        $nombreStorage  = $hash . '_' . time() . '.' . $ext;
        $rutaRelativa   = '/storage/' . $modulo . '/' . $nombreStorage;
        $rutaAbsoluta   = APP_ROOT . '/public' . $rutaRelativa;

        // Crear directorio si no existe
        $dir = dirname($rutaAbsoluta);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $rutaAbsoluta)) {
            throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
        }

        return [
            'nombre_original' => $nombreOriginal,
            'nombre_storage'  => $nombreStorage,
            'ruta_relativa'   => $rutaRelativa,
            'mime_type'       => $mimeReal,
            'tamano_bytes'    => $file['size'],
            'hash_sha256'     => $hash,
        ];
    }
}

if (!function_exists('eliminarArchivo')) {
    /**
     * Eliminar archivo de storage de forma segura.
     */
    function eliminarArchivo(string $rutaRelativa): bool
    {
        // Sanear ruta para evitar path traversal
        $ruta = realpath(APP_ROOT . '/public' . $rutaRelativa);
        $base = realpath(APP_ROOT . '/public/storage');

        if (!$ruta || !$base || !str_starts_with($ruta, $base)) {
            return false; // Path traversal
        }

        if (file_exists($ruta)) {
            return unlink($ruta);
        }
        return false;
    }
}

if (!function_exists('archivoUrl')) {
    /**
     * URL pública de descarga de un archivo.
     */
    function archivoUrl(int $idArchivo): string
    {
        return APP_URL . '/archivo/' . $idArchivo;
    }
}
