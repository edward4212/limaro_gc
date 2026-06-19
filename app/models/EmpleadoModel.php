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

    /** HU-E02: valida que el id_empleado exista y esté ACTIVO, antes de usarlo como FK */
    public function esActivo(int $idEmpleado): bool
    {
        if ($idEmpleado <= 0) return false;
        $row = $this->query(
            "SELECT 1 FROM empleado WHERE id_empleado = ? AND estado_empleado = 'ACTIVO' LIMIT 1",
            [$idEmpleado]
        )->fetch();
        return (bool) $row;
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
     * Obtener nombre y correo de un empleado a partir de su id_usuario.
     * Centraliza el JOIN usuario->empleado que estaba inline en 4 controladores (SEC-001/002).
     */
    public function porIdUsuario(int $idUsuario): ?array
    {
        if (!$idUsuario) return null;
        return $this->query(
            "SELECT e.id_empleado, e.nombre_completo, e.correo_empleado
               FROM usuario u
               INNER JOIN empleado e ON e.id_empleado = u.id_empleado
              WHERE u.id_usuario = ? LIMIT 1",
            [$idUsuario]
        )->fetch() ?: null;
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


    /**
     * Empleados activos con rol ELABORADOR.
     * HU-018: usado para el select de asignación de solicitudes.
     * Solo aparecen empleados cuyo usuario tiene rol 'ELABORADOR'.
     */
    public function elaboradores(): array
    {
        return $this->query("
            SELECT DISTINCT
                e.id_empleado,
                e.nombre_completo,
                c.cargo,
                u.usuario,
                u.id_usuario
            FROM empleado e
            INNER JOIN usuario     u  ON u.id_empleado = e.id_empleado
            INNER JOIN usuario_rol ur ON ur.id_usuario  = u.id_usuario
            INNER JOIN rol         r  ON r.id_rol        = ur.id_rol
            LEFT  JOIN cargo       c  ON c.id_cargo      = e.id_cargo
            WHERE e.estado_empleado = 'ACTIVO'
              AND u.estado          = 'ACTIVO'
              AND r.rol             = 'ELABORADOR'
              AND r.estado          = 'ACTIVO'
            ORDER BY e.nombre_completo
        ")->fetchAll();
    }


    /** Empleados activos con rol REVISOR — HU-021 */
    public function revisores(): array
    {
        return $this->query("
            SELECT DISTINCT e.id_empleado, e.nombre_completo,
                   c.cargo, u.usuario, u.id_usuario
            FROM empleado e
            INNER JOIN usuario     u  ON u.id_empleado = e.id_empleado
            INNER JOIN usuario_rol ur ON ur.id_usuario  = u.id_usuario
            INNER JOIN rol         r  ON r.id_rol        = ur.id_rol
            LEFT  JOIN cargo       c  ON c.id_cargo      = e.id_cargo
            WHERE e.estado_empleado = 'ACTIVO'
              AND u.estado          = 'ACTIVO'
              AND r.rol             = 'REVISOR'
              AND r.estado          = 'ACTIVO'
            ORDER BY e.nombre_completo
        ")->fetchAll();
    }


    /** Empleados activos con rol APROBADOR — HU-023 */
    public function aprobadores(): array
    {
        return $this->query("
            SELECT DISTINCT e.id_empleado, e.nombre_completo,
                   c.cargo, u.usuario, u.id_usuario
            FROM empleado e
            INNER JOIN usuario     u  ON u.id_empleado = e.id_empleado
            INNER JOIN usuario_rol ur ON ur.id_usuario  = u.id_usuario
            INNER JOIN rol         r  ON r.id_rol        = ur.id_rol
            LEFT  JOIN cargo       c  ON c.id_cargo      = e.id_cargo
            WHERE e.estado_empleado = 'ACTIVO'
              AND u.estado          = 'ACTIVO'
              AND r.rol             = 'APROBADOR'
              AND r.estado          = 'ACTIVO'
            ORDER BY e.nombre_completo
        ")->fetchAll();
    }

}