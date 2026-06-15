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

    /**
     * Empleados activos de un cargo con correo (para notificaciones).
     * Elimina el Database::getInstance() en CargoController.
     */
    public function empleadosActivosConCorreo(int $idCargo): array
    {
        return $this->query(
            "SELECT e.nombre_completo, e.correo_empleado
             FROM empleado e
             WHERE e.id_cargo = ? AND e.estado_empleado = 'ACTIVO'
               AND e.correo_empleado IS NOT NULL AND e.correo_empleado <> ''",
            [$idCargo]
        )->fetchAll();
    }

    /**
     * Verificar si un cargo tiene empleados activos (antes de eliminar).
     * Elimina el Database::getInstance() en CargoController::eliminar().
     */
    public function tieneEmpleadosActivos(int $idCargo): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) FROM empleado WHERE id_cargo = ? AND estado_empleado = 'ACTIVO'",
            [$idCargo]
        )->fetchColumn();
    }

}