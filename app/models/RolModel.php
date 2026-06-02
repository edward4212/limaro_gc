<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Roles y permisos (rol_modulo).
 */
class RolModel extends Model
{
    protected string $table      = 'rol';
    protected string $primaryKey = 'id_rol';

    /**
     * Listar roles con conteo de usuarios.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT r.*,
                   (SELECT COUNT(*) FROM usuario u WHERE u.id_rol = r.id_rol) AS total_usuarios
            FROM rol r
            ORDER BY r.rol
        ")->fetchAll();
    }

    /**
     * Activos para selects.
     */
    public function activos(): array
    {
        return $this->query("
            SELECT id_rol, rol FROM rol WHERE estado = 'ACTIVO' ORDER BY rol
        ")->fetchAll();
    }

    /**
     * Permisos de un rol (para la matriz de checkboxes).
     */
    public function permisosDelRol(int $idRol): array
    {
        $rows = $this->query("
            SELECT rm.*, m.codigo, m.nombre AS nombre_modulo, m.id_padre, m.orden
            FROM modulo m
            LEFT JOIN rol_modulo rm ON rm.id_modulo = m.id_modulo AND rm.id_rol = ?
            WHERE m.estado = 'ACTIVO'
            ORDER BY m.id_padre, m.orden
        ", [$idRol])->fetchAll();

        // Indexar por id_modulo
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['id_modulo']] = $row;
        }
        return $indexed;
    }

    /**
     * Obtener árbol de módulos (padres e hijos) para la matriz.
     */
    public function modulosArbol(): array
    {
        $all = $this->query("
            SELECT * FROM modulo WHERE estado = 'ACTIVO' ORDER BY id_padre, orden
        ")->fetchAll();

        $tree = [];
        foreach ($all as $m) {
            if ($m['id_padre'] === null) {
                $m['hijos'] = [];
                $tree[$m['id_modulo']] = $m;
            }
        }
        foreach ($all as $m) {
            if ($m['id_padre'] !== null && isset($tree[$m['id_padre']])) {
                $tree[$m['id_padre']]['hijos'][] = $m;
            }
        }
        return array_values($tree);
    }

    /**
     * Guardar matriz de permisos de un rol.
     */
    public function guardarPermisos(int $idRol, array $permisos): void
    {
        // Eliminar permisos actuales
        $this->query("DELETE FROM rol_modulo WHERE id_rol = ?", [$idRol]);

        if (empty($permisos)) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO rol_modulo (id_rol, id_modulo, ver, crear, editar, eliminar)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($permisos as $idModulo => $perm) {
            if (empty($perm['ver'])) {
                continue; // No se le da ningún permiso si no tiene VER
            }
            $stmt->execute([
                $idRol,
                (int) $idModulo,
                !empty($perm['ver'])     ? 1 : 0,
                !empty($perm['crear'])   ? 1 : 0,
                !empty($perm['editar'])  ? 1 : 0,
                !empty($perm['eliminar'])? 1 : 0,
            ]);
        }
    }
}
