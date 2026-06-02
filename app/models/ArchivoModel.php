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
    public function registrar(
        string $modulo,
        int    $idReferencia,
        array  $uploadResult,
        string $subidoPor
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
            'subido_por'      => $subidoPor,
        ]);
    }

    /**
     * Archivos de una entidad.
     */
    public function deEntidad(string $modulo, int $idReferencia): array
    {
        return $this->query("
            SELECT * FROM archivo
            WHERE modulo = ? AND id_referencia = ?
            ORDER BY fecha_subida DESC
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
        $row['ruta_absoluta'] = APP_ROOT . '/public' . $row['ruta_relativa'];
        return $row;
    }
}
