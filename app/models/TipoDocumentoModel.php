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
                   (SELECT COUNT(*) FROM documento d
                    WHERE d.id_tipo_documento = td.id_tipo_documento) AS total_docs,
                   (SELECT COUNT(*) FROM acuerdos a
                    WHERE a.id_tipo_documento = td.id_tipo_documento) AS total_acuerdos
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


    /**
     * Verificar si ya existe un tipo con la misma sigla.
     */
    public function existeSigla(string $sigla, int $excluirId = 0): bool
    {
        $row = $this->query(
            "SELECT id_tipo_documento FROM tipo_documento
             WHERE UPPER(TRIM(sigla_tipo_documento)) = UPPER(TRIM(?))
               AND id_tipo_documento != ?
             LIMIT 1",
            [$sigla, $excluirId]
        )->fetch();
        return (bool) $row;
    }

    /**
     * Verificar si ya existe un tipo con el mismo nombre.
     */
    public function existeNombre(string $nombre, int $excluirId = 0): bool
    {
        $row = $this->query(
            "SELECT id_tipo_documento FROM tipo_documento
             WHERE UPPER(TRIM(tipo_documento)) = UPPER(TRIM(?))
               AND id_tipo_documento != ?
             LIMIT 1",
            [$nombre, $excluirId]
        )->fetch();
        return (bool) $row;
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

    public function contarDocumentos(int $id): int
    {
        // Cuenta documentos activos + acuerdos activos del mismo tipo
        $docs = (int) $this->query(
            "SELECT COUNT(*) FROM documento WHERE id_tipo_documento = ? AND estado = 'ACTIVO'",
            [$id]
        )->fetchColumn();

        $acuerdos = (int) $this->query(
            "SELECT COUNT(*) FROM acuerdos WHERE id_tipo_documento = ? AND estado = 'ACTIVO'",
            [$id]
        )->fetchColumn();

        return $docs + $acuerdos;
    }


    public function porNombre(string $nombre): ?array
    {
        // Búsqueda case-insensitive: 'acuerdo', 'Acuerdo', 'ACUERDO' → mismo resultado
        return $this->query(
            "SELECT * FROM tipo_documento
             WHERE LOWER(tipo_documento) = LOWER(?)
               AND estado = 'ACTIVO'
             LIMIT 1",
            [$nombre]
        )->fetch() ?: null;
    }

}
