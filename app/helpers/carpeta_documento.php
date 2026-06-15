<?php

/**
 * Helper: gestión de carpetas de documentos por jerarquía ISO 9001.
 *
 * Estructura en disco:
 *   public/storage/documentos/
 *     MACROPROCESO/
 *       PROCESO/
 *         [SUBPROCESO/]          ← opcional
 *           TIPO_DOCUMENTO/
 *             NOMBRE_DOCUMENTO/
 *               V0/              ← versión 0 = CREADO (sin archivo)
 *               V1/              ← versión 1 = archivo subido
 *               V2/              ...
 */

if (!function_exists('carpetaDocumento')) {
    /**
     * Construye la ruta relativa de la carpeta de un documento.
     * Sanitiza cada segmento para que sea válido como nombre de carpeta.
     *
     * @param string      $macroproceso
     * @param string      $proceso
     * @param string|null $subproceso     null si no tiene
     * @param string      $tipoDocumento
     * @param string      $nombreDocumento
     * @return string  Ruta relativa desde public/storage/documentos/
     *                 Ejemplo: "MISIONALES/CARTERA/GESTION DE COBRO/PROCEDIMIENTO/GENERACION DE CARTAS"
     */
    function carpetaDocumento(
        string  $macroproceso,
        string  $proceso,
        ?string $subproceso,
        string  $tipoDocumento,
        string  $nombreDocumento,
        string  $siglaProceso    = '',
        string  $siglaTipoDoc    = '',
        string  $codigoDocumento = ''
    ): string {
        // Formato HU-010: separador guion '-'
        // CON subproceso:    macroproceso / sigla-proceso / subproceso / sigla-tipo / codigo-nombre
        // SIN subproceso:    macroproceso / sigla-proceso / sigla-tipo / codigo-nombre

        // Segmento proceso: "SIGLA-NOMBRE" si tiene sigla, solo nombre si no
        $segProceso = $siglaProceso
            ? sanitizarSegmentoCarpeta($siglaProceso) . '-' . sanitizarSegmentoCarpeta($proceso)
            : sanitizarSegmentoCarpeta($proceso);

        // Segmento tipo doc: "SIGLA-TIPODOC" si tiene sigla
        $segTipo = $siglaTipoDoc
            ? sanitizarSegmentoCarpeta($siglaTipoDoc) . '-' . sanitizarSegmentoCarpeta($tipoDocumento)
            : sanitizarSegmentoCarpeta($tipoDocumento);

        // Segmento documento: "CODIGO-NOMBRE" si tiene código
        $segDoc = $codigoDocumento
            ? sanitizarSegmentoCarpeta($codigoDocumento) . '-' . sanitizarSegmentoCarpeta($nombreDocumento)
            : sanitizarSegmentoCarpeta($nombreDocumento);

        $segmentos = [sanitizarSegmentoCarpeta($macroproceso), $segProceso];

        if (!empty($subproceso)) {
            $segmentos[] = sanitizarSegmentoCarpeta($subproceso);
        }

        $segmentos[] = $segTipo;
        $segmentos[] = $segDoc;

        return implode(DIRECTORY_SEPARATOR, $segmentos);
    }
}


if (!function_exists('carpetaVersion')) {
    /**
     * Ruta ABSOLUTA de la carpeta de una versión específica.
     * HU-V05: función única para carpeta — evita rutas hardcodeadas.
     *
     * @param string $rutaRelativaDocumento  Resultado de carpetaDocumento()
     * @param int    $numeroVersion
     * @return string  Ruta absoluta: APP_ROOT/public/storage/documentos/{relativa}/V{n}/
     */
    function carpetaVersion(string $rutaRelativaDocumento, int $numeroVersion): string
    {
        $base = APP_ROOT . '/public/storage/documentos/';
        return rtrim($base . $rutaRelativaDocumento, DIRECTORY_SEPARATOR)
             . DIRECTORY_SEPARATOR . 'V' . $numeroVersion . DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('getVersionPath')) {
    /**
     * HU-V05: punto único de verdad para la ruta de un archivo de versión.
     * Devuelve la ruta RELATIVA (para guardar en BD) y ABSOLUTA (para disco).
     *
     * @return array{relativa: string, absoluta: string, carpeta_abs: string}
     */
    function getVersionPath(
        string  $macroproceso,
        string  $proceso,
        ?string $subproceso,
        string  $tipoDocumento,
        string  $nombreDocumento,
        int     $numeroVersion,
        string  $nombreArchivo,
        string  $siglaProceso    = '',
        string  $siglaTipoDoc    = '',
        string  $codigoDocumento = ''
    ): array {
        $relDoc    = carpetaDocumento(
            $macroproceso, $proceso, $subproceso, $tipoDocumento, $nombreDocumento,
            $siglaProceso, $siglaTipoDoc, $codigoDocumento
        );
        $carpetaAbs = carpetaVersion($relDoc, $numeroVersion);
        $nombreSan  = sanitizarSegmentoCarpeta($nombreArchivo) ?: 'documento';
        $relativa   = '/storage/documentos/'
                     . str_replace(DIRECTORY_SEPARATOR, '/', $relDoc)
                     . '/V' . $numeroVersion . '/' . $nombreSan;
        return [
            'relativa'    => $relativa,
            'absoluta'    => $carpetaAbs . $nombreSan,
            'carpeta_abs' => $carpetaAbs,
        ];
    }
}


if (!function_exists('crearCarpetaVersion')) {
    /**
     * Crea en disco la carpeta para una versión del documento.
     * Si ya existe, no hace nada.
     *
     * @param string $macroproceso
     * @param string $proceso
     * @param string|null $subproceso
     * @param string $tipoDocumento
     * @param string $nombreDocumento
     * @param int    $numeroVersion
     * @return string  Ruta absoluta de la carpeta creada
     * @throws RuntimeException  Si no puede crear la carpeta
     */
    function crearCarpetaVersion(
        string  $macroproceso,
        string  $proceso,
        ?string $subproceso,
        string  $tipoDocumento,
        string  $nombreDocumento,
        int     $numeroVersion,
        string  $siglaProceso    = '',
        string  $siglaTipoDoc    = '',
        string  $codigoDocumento = ''
    ): string {
        $relativa = carpetaDocumento(
            $macroproceso, $proceso, $subproceso, $tipoDocumento, $nombreDocumento,
            $siglaProceso, $siglaTipoDoc, $codigoDocumento
        );
        $carpeta  = carpetaVersion($relativa, $numeroVersion);

        if (!is_dir($carpeta)) {
            if (!mkdir($carpeta, 0755, true)) {
                throw new \RuntimeException("No se pudo crear la carpeta: $carpeta");
            }
        }

        return $relativa; // HU-010: retornar ruta relativa para guardar en BD
    }
}


// [rutaArchivoEnVersion] eliminada — sin uso en codebase



// [rutaRelativaAlmacenamiento] eliminada — sin uso en codebase


if (!function_exists('sanitizarSegmentoCarpeta')) {
    /**
     * Sanitiza un string para usarlo como nombre de carpeta.
     * - Convierte a mayúsculas
     * - Elimina caracteres inválidos (solo letras, números, espacios, guiones)
     * - Reemplaza múltiples espacios por uno solo
     * - Trunca a 80 caracteres
     */
    function sanitizarSegmentoCarpeta(string $nombre): string
    {
        // Normalizar caracteres acentuados → ASCII
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre) ?: $nombre;
        // Mayúsculas
        $nombre = strtoupper($nombre);
        // Solo letras, números, espacios, guiones, guión bajo
        $nombre = preg_replace('/[^A-Z0-9 \-_]/', '', $nombre);
        // Espacios múltiples → uno solo
        $nombre = preg_replace('/\s+/', ' ', trim($nombre));

        return substr($nombre ?: 'SIN_NOMBRE', 0, 80);
    }
}

if (!function_exists('zipCarpetaDocumento')) {
    /**
     * Genera un ZIP de toda la carpeta de un documento (todas sus versiones).
     * Devuelve la ruta absoluta del ZIP temporal generado.
     *
     * @param string      $macroproceso
     * @param string      $proceso
     * @param string|null $subproceso
     * @param string      $tipoDocumento
     * @param string      $nombreDocumento
     * @return string  Ruta absoluta del archivo ZIP temporal
     * @throws RuntimeException  Si la carpeta no existe o no se puede crear el ZIP
     */
    function zipCarpetaDocumento(
        string  $macroproceso,
        string  $proceso,
        ?string $subproceso,
        string  $tipoDocumento,
        string  $nombreDocumento
    ): string {
        $relativa       = carpetaDocumento($macroproceso, $proceso, $subproceso, $tipoDocumento, $nombreDocumento);
        $carpetaAbsoluta = APP_ROOT . '/public/storage/documentos/' . $relativa;

        if (!is_dir($carpetaAbsoluta)) {
            throw new \RuntimeException("La carpeta del documento no existe en el servidor.");
        }

        // Crear ZIP en carpeta temporal del sistema
        $zipPath = sys_get_temp_dir() . '/' . sanitizarSegmentoCarpeta($nombreDocumento) . '_' . time() . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("No se pudo crear el archivo ZIP.");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($carpetaAbsoluta, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $filePath   = $file->getRealPath();
            $relativePath = substr($filePath, strlen($carpetaAbsoluta) + 1);
            $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
        }

        $zip->close();

        return $zipPath;
    }
}

if (!function_exists('carpetaUsuario')) {
    /**
     * Devuelve (y crea si no existe) la ruta absoluta de una subcarpeta
     * personal del usuario dentro de public/storage/usuarios/{id}/.
     *
     * Estructura:
     *   public/storage/usuarios/{id_usuario}/
     *     foto/        ← foto de perfil
     *     firmas/      ← firma digital (uso futuro)
     *     documentos/  ← archivos personales (uso futuro)
     *
     * @param int    $idUsuario
     * @param string $subcarpeta  'foto' | 'firmas' | 'documentos' | ''
     * @return string  Ruta absoluta en disco
     */
    function carpetaUsuario(int $idUsuario, string $subcarpeta = 'foto'): string
    {
        $base = APP_ROOT . '/public/storage/usuarios/' . $idUsuario;

        // Crear subcarpetas estándar al primer acceso
        foreach (['foto', 'firmas', 'documentos'] as $sub) {
            $dir = $base . '/' . $sub;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $ruta = $subcarpeta ? $base . '/' . trim($subcarpeta, '/') : $base;

        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }

        return $ruta;
    }
}


// [rutaFotoPerfil] eliminada — sin uso en codebase


if (!function_exists('urlFotoPerfil')) {
    /**
     * URL pública de la foto de perfil de un usuario.
     * Si no tiene foto subida, retorna la URL del avatar por defecto.
     *
     * @param string|null $imgEmpleado  Valor de empleado.img_empleado en BD
     * @return string  URL lista para usar en src=""
     */
    function urlFotoPerfil(?string $imgEmpleado, ?int $idUsuario = null): string
    {
        // Si tenemos id_usuario usar el endpoint PHP (evita 403 en storage)
        if ($idUsuario) {
            return APP_URL . '/foto-usuario/' . $idUsuario;
        }

        if (empty($imgEmpleado)) {
            return APP_URL . '/foto-usuario/0';
        }

        // Extraer id_usuario del path /storage/usuarios/{id}/foto/...
        if (str_starts_with($imgEmpleado, '/storage/usuarios/')) {
            preg_match('#/storage/usuarios/(\d+)/#', $imgEmpleado, $m);
            if (!empty($m[1])) {
                return APP_URL . '/foto-usuario/' . $m[1];
            }
        }

        // Fallback legacy
        if (str_starts_with($imgEmpleado, '/storage/')) {
            return APP_URL . $imgEmpleado;
        }

        // Ruta legacy: solo nombre de archivo en /assets/img/
        return APP_URL . '/assets/img/' . $imgEmpleado;
    }
}
