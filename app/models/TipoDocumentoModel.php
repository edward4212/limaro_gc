<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Tipos de Documento.
 */
class TipoDocumentoModel extends Model
{
    protected string $table      = 'tipo_documento';
    protected string $primaryKey = 'id_tipo_documento';

    /**
     * Listar todos con conteo de documentos.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT td.*,
                   (SELECT COUNT(*) FROM documento d WHERE d.id_tipo_documento = td.id_tipo_documento) AS total_docs
            FROM tipo_documento td
            ORDER BY td.tipo_documento
        ")->fetchAll();
    }

    /**
     * Activos para selects.
     */
    public function activos(): array
    {
        return $this->query("
            SELECT id_tipo_documento, tipo_documento, sigla_tipo_documento
            FROM tipo_documento
            WHERE estado = 'ACTIVO'
            ORDER BY tipo_documento
        ")->fetchAll();
    }

    public function crear(string $tipo, string $sigla, string $estado = 'ACTIVO'): int
    {
        return $this->insert([
            'tipo_documento'       => strtoupper($tipo),
            'sigla_tipo_documento' => strtoupper($sigla),
            'estado'               => $estado,
        ]);
    }

    public function actualizar(int $id, string $tipo, string $sigla, string $estado): bool
    {
        return $this->update($id, [
            'tipo_documento'       => strtoupper($tipo),
            'sigla_tipo_documento' => strtoupper($sigla),
            'estado'               => $estado,
        ]);
    }

    /**
     * Buscar tipo de documento por nombre (búsqueda parcial, insensible a mayúsculas).
     */
    public function buscarPorNombre(string $nombre): ?array
    {
        $row = $this->query(
            "SELECT * FROM tipo_documento WHERE UPPER(tipo_documento) LIKE UPPER(?) AND estado = 'ACTIVO' LIMIT 1",
            ['%' . $nombre . '%']
        )->fetch();
        return $row ?: null;
    }
}
