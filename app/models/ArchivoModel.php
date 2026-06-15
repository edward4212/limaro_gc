<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Archivos — gestiona la tabla archivo (storage físico).
 */
class ArchivoModel extends Model
{
    protected string $table      = 'archivo';
    protected string $primaryKey = 'id_archivo';

    /**
     * Registrar archivo en la BD.
     */
    /**
     * Registrar archivo en BD.
     * @param string $modulo       Módulo origen: ACUERDO, CARGO, TAREA, VERSIONAMIENTO, SOLICITUD...
     * @param int    $idReferencia ID del registro en el módulo origen
     * @param array  $uploadResult Resultado de subirArchivo()
     * @param int    $idUsuario    ID del usuario que sube el archivo
     */
    public function registrar(
        string $modulo,
        int    $idReferencia,
        array  $uploadResult,
        int    $idUsuario
    ): int {
        return $this->insert([
            'modulo'          => $modulo,
            'id_referencia'   => $idReferencia,
            'nombre_original' => $uploadResult['nombre_original'],
            'nombre_storage'  => $uploadResult['nombre_storage'],
            'ruta_relativa'   => $uploadResult['ruta_relativa'],
            'mime_type'       => $uploadResult['mime_type'],
            'tamano_bytes'    => $uploadResult['tamano_bytes'],
            'hash_sha256'     => $uploadResult['hash_sha256'],
            'subido_por'      => null,   // deprecado — se conserva nullable para historial
            'id_usuario'      => $idUsuario,
        ]);
    }

    /**
     * Archivos de una entidad.
     */
    public function deEntidad(string $modulo, int $idReferencia): array
    {
        return $this->query("
            SELECT ar.*, u.usuario AS usuario_subida
            FROM archivo ar
            LEFT JOIN usuario u ON u.id_usuario = ar.id_usuario
            WHERE ar.modulo = ? AND ar.id_referencia = ?
            ORDER BY ar.fecha_subida DESC
        ", [$modulo, $idReferencia])->fetchAll();
    }

    /**
     * Descargar archivo (retorna datos para enviar al cliente).
     */
    public function parDescarga(int $id): ?array
    {
        $row = $this->find($id);
        if (!$row) {
            return null;
        }
        
        // Construir ruta absoluta y validar que esté dentro del directorio permitido
        $rutaAbsoluta = realpath(APP_ROOT . '/public' . $row['ruta_relativa']);
        $rutaBase = realpath(APP_ROOT . '/public/storage');
        
        // Validar path traversal: la ruta resoluta debe estar dentro del directorio base
        if ($rutaAbsoluta === false || $rutaBase === false || 
            strpos($rutaAbsoluta, $rutaBase) !== 0) {
            error_log("[ArchivoModel] Intento de path traversal detectado: " . $row['ruta_relativa']);
            return null;
        }
        
        $row['ruta_absoluta'] = $rutaAbsoluta;
        return $row;
    }

    /**
     * Obtener el archivo más reciente de una entidad.
     */
    public function porEntidad(string $modulo, int $idReferencia): ?array
    {
        $row = $this->query(
            "SELECT ar.*, u.usuario AS usuario_subida
             FROM archivo ar
             LEFT JOIN usuario u ON u.id_usuario = ar.id_usuario
             WHERE ar.modulo = ? AND ar.id_referencia = ?
             ORDER BY ar.id_archivo DESC LIMIT 1",
            [$modulo, $idReferencia]
        )->fetch();
        return $row ?: null;
    }

}