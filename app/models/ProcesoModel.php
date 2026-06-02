<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Procesos.
 */
class ProcesoModel extends Model
{
    protected string $table      = 'proceso';
    protected string $primaryKey = 'id_proceso';

    /**
     * Listar procesos con macroproceso.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT p.*, m.macroproceso AS nombre_macroproceso
            FROM proceso p
            INNER JOIN macroproceso m ON m.id_macroproceso = p.id_macroproceso
            ORDER BY m.macroproceso, p.proceso
        ")->fetchAll();
    }

    /**
     * Listar activos para selects.
     */
    public function activos(): array
    {
        return $this->query("
            SELECT p.id_proceso, p.id_macroproceso, p.proceso, p.sigla_proceso, m.macroproceso
            FROM proceso p
            INNER JOIN macroproceso m ON m.id_macroproceso = p.id_macroproceso
            WHERE p.estado = 'ACTIVO'
            ORDER BY p.proceso
        ")->fetchAll();
    }

    /**
     * Procesos por macroproceso.
     */
    public function porMacroproceso(int $idMacroproceso): array
    {
        return $this->query("
            SELECT id_proceso, proceso, sigla_proceso
            FROM proceso
            WHERE id_macroproceso = ? AND estado = 'ACTIVO'
            ORDER BY proceso
        ", [$idMacroproceso])->fetchAll();
    }

    /**
     * Crear proceso.
     */
    public function crear(
        int    $idMacroproceso,
        string $proceso,
        string $sigla,
        string $objetivo,
        string $estado = 'ACTIVO'
    ): int {
        return $this->insert([
            'id_macroproceso' => $idMacroproceso,
            'proceso'         => $proceso,
            'sigla_proceso'   => strtoupper($sigla),
            'objetivo'        => $objetivo,
            'estado'          => $estado,
        ]);
    }

    /**
     * Actualizar proceso.
     */
    public function actualizar(
        int    $id,
        int    $idMacroproceso,
        string $proceso,
        string $sigla,
        string $objetivo,
        string $estado
    ): bool {
        return $this->update($id, [
            'id_macroproceso' => $idMacroproceso,
            'proceso'         => $proceso,
            'sigla_proceso'   => strtoupper($sigla),
            'objetivo'        => $objetivo,
            'estado'          => $estado,
        ]);
    }
}
