<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Cargos.
 */
class CargoModel extends Model
{
    protected string $table      = 'cargo';
    protected string $primaryKey = 'id_cargo';

    /**
     * Listar cargos con conteo de empleados.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT c.*,
                   (SELECT COUNT(*) FROM empleado e WHERE e.id_cargo = c.id_cargo) AS total_empleados,
                   ar.id_archivo, ar.nombre_original AS manual_nombre
            FROM cargo c
            LEFT JOIN archivo ar ON ar.modulo = 'CARGO' AND ar.id_referencia = c.id_cargo
            ORDER BY c.cargo
        ")->fetchAll();
    }

    /**
     * Activos para selects.
     */
    public function activos(): array
    {
        return $this->query("
            SELECT id_cargo, cargo FROM cargo
            WHERE estado = 'ACTIVO'
            ORDER BY cargo
        ")->fetchAll();
    }

    public function crear(array $data): int
    {
        return $this->insert($data);
    }

    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
