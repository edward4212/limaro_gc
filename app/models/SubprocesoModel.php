<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Subprocesos.
 * Nivel intermedio opcional entre Proceso y Documento.
 * El documento SIEMPRE pertenece a un proceso; el subproceso
 * es un dato informativo adicional (nullable en documento).
 */
class SubprocesoModel extends Model
{
    protected string $table      = 'subproceso';
    protected string $primaryKey = 'id_subproceso';

    /**
     * Listar todos los subprocesos con nombre del proceso padre.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT s.*,
                   p.proceso AS nombre_proceso,
                   p.sigla_proceso,
                   m.macroproceso AS nombre_macroproceso
            FROM subproceso s
            INNER JOIN proceso      p ON p.id_proceso      = s.id_proceso
            INNER JOIN macroproceso m ON m.id_macroproceso = p.id_macroproceso
            ORDER BY m.macroproceso, p.proceso, s.subproceso
        ")->fetchAll();
    }

    /**
     * Subprocesos activos filtrados por proceso (para select dependiente vía Ajax).
     */
    public function porProceso(int $idProceso): array
    {
        return $this->query("
            SELECT s.id_subproceso, s.subproceso, s.sigla_subproceso,
                   p.proceso AS nombre_proceso, p.id_proceso
            FROM subproceso s
            INNER JOIN proceso p ON p.id_proceso = s.id_proceso
            WHERE s.id_proceso = ? AND s.estado = 'ACTIVO'
            ORDER BY s.subproceso
        ", [$idProceso])->fetchAll();
    }

    /**
     * Todos los subprocesos activos (para selects sin filtro previo).
     */
    public function activos(): array
    {
        return $this->query("
            SELECT s.id_subproceso, s.subproceso, s.sigla_subproceso,
                   p.proceso AS nombre_proceso, p.id_proceso
            FROM subproceso s
            INNER JOIN proceso p ON p.id_proceso = s.id_proceso
            WHERE s.estado = 'ACTIVO'
            ORDER BY p.proceso, s.subproceso
        ")->fetchAll();
    }

    /**
     * Crear subproceso.
     */
        public function crear(
        int     $idProceso,
        string  $nombre,
        string  $sigla    = '',
        string  $objetivo = '',
        string  $estado   = 'ACTIVO'
    ): int {
        // Sigla: NULL si viene vacía para no violar el UNIQUE (id_proceso, sigla_subproceso)
        $siglaVal = strtoupper(trim($sigla)) ?: null;

        return $this->insert([
            'id_proceso'       => $idProceso,
            'subproceso'       => strtoupper(trim($nombre)),
            'sigla_subproceso' => $siglaVal,
            'objetivo'         => trim($objetivo),
            'estado'           => $estado,
        ]);
    }

    /**
     * Actualizar subproceso.
     */
        public function actualizar(
        int    $id,
        int    $idProceso,
        string $nombre,
        string $sigla    = '',
        string $objetivo = '',
        string $estado   = 'ACTIVO'
    ): bool {
        $siglaVal = strtoupper(trim($sigla)) ?: null;

        return $this->update($id, [
            'id_proceso'       => $idProceso,
            'subproceso'       => strtoupper(trim($nombre)),
            'sigla_subproceso' => $siglaVal,
            'objetivo'         => trim($objetivo),
            'estado'           => $estado,
        ]);
    }

    public function contarDocumentos(int $id): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) FROM documento WHERE id_subproceso = ? AND estado = 'ACTIVO'",
            [$id]
        )->fetchColumn();
    }

}