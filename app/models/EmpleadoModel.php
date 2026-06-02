<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Empleados.
 */
class EmpleadoModel extends Model
{
    protected string $table      = 'empleado';
    protected string $primaryKey = 'id_empleado';

    /**
     * Listar empleados con cargo.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT e.*, c.cargo AS nombre_cargo
            FROM empleado e
            INNER JOIN cargo c ON c.id_cargo = e.id_cargo
            ORDER BY e.nombre_completo
        ")->fetchAll();
    }

    /**
     * Empleados activos para selects.
     */
    public function activos(): array
    {
        return $this->query("
            SELECT e.id_empleado, e.nombre_completo, e.correo_empleado, c.cargo
            FROM empleado e
            INNER JOIN cargo c ON c.id_cargo = e.id_cargo
            WHERE e.estado_empleado = 'ACTIVO'
            ORDER BY e.nombre_completo
        ")->fetchAll();
    }

    /**
     * Crear empleado vía SP.
     */
    public function crearViaSP(
        string $nombre,
        string $correo,
        int    $idCargo,
        int    $idEmpresa
    ): void {
        $this->callSP('create_empleado', [$nombre, $correo, $idCargo, $idEmpresa]);
    }

    /**
     * Actualizar imagen de perfil.
     */
    public function actualizarImagen(int $idEmpleado, string $imagen): void
    {
        $this->update($idEmpleado, ['img_empleado' => $imagen]);
    }

    /**
     * Obtener correo y nombre de un empleado por su ID.
     */
    public function correoYNombre(int $id): ?array
    {
        $row = $this->query(
            "SELECT nombre_completo, correo_empleado FROM empleado WHERE id_empleado = ? LIMIT 1",
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Obtener correo y nombre buscando por nombre completo (para notificaciones de tarea).
     */
    public function correoYNombrePorNombre(string $nombre): ?array
    {
        if (empty($nombre)) return null;
        $row = $this->query(
            "SELECT nombre_completo, correo_empleado FROM empleado WHERE nombre_completo = ? AND estado_empleado = 'ACTIVO' LIMIT 1",
            [$nombre]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Obtener todos los correos de empleados activos (para notificación masiva de aprobación).
     * Solo retorna los que tienen correo válido y estado ACTIVO.
     */
    public function correosTodos(): array
    {
        return $this->query(
            "SELECT correo_empleado AS correo, nombre_completo AS nombre FROM empleado
             WHERE estado_empleado = 'ACTIVO' AND correo_empleado != '' AND correo_empleado LIKE '%@%'
             ORDER BY nombre_completo"
        )->fetchAll();
    }

}
