<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Macroprocesos.
 */
class MacroprocesoModel extends Model
{
    protected string $table      = 'macroproceso';
    protected string $primaryKey = 'id_macroproceso';

    /**
     * Listar todos los macroprocesos.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT m.*,
                   (SELECT COUNT(*) FROM proceso p WHERE p.id_macroproceso = m.id_macroproceso) AS total_procesos
            FROM macroproceso m
            ORDER BY m.macroproceso
        ")->fetchAll();
    }

    /**
     * Listar solo activos (para selects).
     */
    public function activos(): array
    {
        return $this->query("
            SELECT id_macroproceso, macroproceso
            FROM macroproceso
            WHERE estado = 'ACTIVO'
            ORDER BY macroproceso
        ")->fetchAll();
    }

    /**
     * Crear macroproceso.
     */
    public function crear(string $nombre, string $objetivo, string $estado = 'ACTIVO'): int
    {
        return $this->insert([
            'macroproceso' => $nombre,
            'objetivo'     => $objetivo,
            'estado'       => $estado,
        ]);
    }

    /**
     * Actualizar macroproceso.
     */
    public function actualizar(int $id, string $nombre, string $objetivo, string $estado): bool
    {
        return $this->update($id, [
            'macroproceso' => $nombre,
            'objetivo'     => $objetivo,
            'estado'       => $estado,
        ]);
    }

    /**
     * Inactivar (soft-delete).
     */
    public function inactivar(int $id): bool
    {
        return $this->update($id, ['estado' => 'INACTIVO']);
    }
}
