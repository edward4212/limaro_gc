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
                   (SELECT COUNT(*) FROM usuario_rol ur WHERE ur.id_rol = r.id_rol) AS total_usuarios
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

        // Indexar todos por id
        $indexed = [];
        foreach ($all as $m) {
            $m['hijos'] = [];
            $indexed[$m['id_modulo']] = $m;
        }

        // Construir árbol recursivo (N niveles)
        $roots = [];
        foreach ($all as $m) {
            $id  = $m['id_modulo'];
            $pid = $m['id_padre'];
            if ($pid === null) {
                $roots[$id] = &$indexed[$id];
            } elseif (isset($indexed[$pid])) {
                $indexed[$pid]['hijos'][] = &$indexed[$id];
            }
        }
        return array_values($roots);
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

    /**
     * Auto-asignar ver=1 a todos los módulos activos al crear un rol nuevo.
     * Elimina el Database::getInstance() en RolController::guardar().
     */
    public function autoAsignarModulos(int $idRol): void
    {
        $this->query(
            "INSERT IGNORE INTO rol_modulo (id_rol, id_modulo, ver, crear, editar, eliminar)
             SELECT ?, id_modulo, 1, 0, 0, 0 FROM modulo WHERE estado = 'ACTIVO'",
            [$idRol]
        );
    }

    /**
     * Verificar si un rol tiene usuarios activos asignados.
     * Elimina el Database::getInstance() en RolController::eliminar().
     */
    public function tieneUsuariosActivos(int $idRol): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) FROM usuario_rol ur
             INNER JOIN usuario u ON u.id_usuario = ur.id_usuario
             WHERE ur.id_rol = ? AND u.estado = 'ACTIVO'",
            [$idRol]
        )->fetchColumn();
    }

    /**
     * Sincronizar módulos faltantes para un rol (agrega los que no tiene).
     * Elimina el Database::getInstance() en RolController::sincronizar().
     *
     * @return int  Cantidad de módulos agregados
     */
    public function sincronizarModulos(int $idRol): int
    {
        $stmt = $this->query(
            "INSERT IGNORE INTO rol_modulo (id_rol, id_modulo, ver, crear, editar, eliminar)
             SELECT ?, id_modulo, 1, 0, 0, 0
             FROM modulo
             WHERE estado = 'ACTIVO'
               AND id_modulo NOT IN (
                   SELECT id_modulo FROM rol_modulo WHERE id_rol = ?
               )",
            [$idRol, $idRol]
        );
        return (int) $stmt->rowCount();
    }

}