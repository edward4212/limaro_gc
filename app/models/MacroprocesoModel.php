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
     * Verificar si ya existe un macroproceso con el mismo nombre.
     * @param int $excluirId  ID a excluir en edición (0 = ninguno)
     */
    public function existeNombre(string $nombre, int $excluirId = 0): bool
    {
        $row = $this->query(
            "SELECT id_macroproceso FROM macroproceso
             WHERE UPPER(TRIM(macroproceso)) = UPPER(TRIM(?))
               AND id_macroproceso != ?
             LIMIT 1",
            [$nombre, $excluirId]
        )->fetch();
        return (bool) $row;
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

    /** Verifica si el macroproceso tiene procesos activos vinculados */
    public function tieneProcesos(int $id): bool
    {
        $total = (int) $this->query(
            "SELECT COUNT(*) FROM proceso WHERE id_macroproceso = ? AND estado = 'ACTIVO'",
            [$id]
        )->fetchColumn();
        return $total > 0;
    }

    /** Cuenta procesos activos del macroproceso */
    public function contarProcesos(int $id): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) FROM proceso WHERE id_macroproceso = ? AND estado = 'ACTIVO'",
            [$id]
        )->fetchColumn();
    }

}