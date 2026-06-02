<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Usuario con soporte multi-rol.
 * Los permisos son la UNIÓN de todos los roles asignados.
 */
class UsuarioModel extends Model
{
    protected string $table      = 'usuario';
    protected string $primaryKey = 'id_usuario';

    // ─────────────────────────────────────────────────────────────
    // AUTENTICACIÓN
    // ─────────────────────────────────────────────────────────────

    /**
     * Autenticar usuario. Retorna array con todos sus roles o null.
     */
    public function autenticar(string $usuario, string $clave): ?array
    {
        $row = $this->query("
            SELECT u.*, e.nombre_completo, e.correo_empleado, e.img_empleado, e.id_cargo
            FROM usuario u
            INNER JOIN empleado e ON e.id_empleado = u.id_empleado
            WHERE u.usuario = ?
            LIMIT 1
        ", [$usuario])->fetch();

        if (!$row) return null;

        $claveAlmacenada = $row['clave'];

        // Soporte dual durante migración:
        // claves que aún son AES (pendientes de reset) o el placeholder de migración
        if ($claveAlmacenada === 'MIGRAR_AES_A_HASH') {
            return null; // Forzar reset
        }

        // Verificar con password_hash (Argon2ID / bcrypt)
        if (!password_verify($clave, $claveAlmacenada)) {
            // Fallback temporal: verificar AES mientras dure la migración
            // (se puede eliminar este bloque después de que todos hayan cambiado su clave)
            try {
                $aesRow = $this->query(
                    "SELECT CAST(AES_DECRYPT(clave, ?) AS CHAR) AS plain FROM usuario WHERE usuario = ? LIMIT 1",
                    [defined('AES_KEY') ? AES_KEY : '', $usuario]
                )->fetch();
                if (!$aesRow || $aesRow['plain'] !== $clave) {
                    return null;
                }
                // Actualizar a hash moderno automáticamente al primer login exitoso con AES
                $this->cambiarClave((int)$row['id_usuario'], $clave);
                error_log("[Limaro SGC] Clave de usuario '{$usuario}' migrada de AES a Argon2ID.");
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Rehashing si el algoritmo mejoró (bcrypt → Argon2ID)
        if (password_needs_rehash($claveAlmacenada, PASSWORD_ARGON2ID)) {
            $this->cambiarClave((int)$row['id_usuario'], $clave);
        }

        unset($row['clave']);

        // Cargar todos los roles del usuario
        $roles = $this->rolesPorUsuario((int)$row['id_usuario']);
        $row['roles']     = $roles;                                         // array completo
        $row['rol']       = implode(', ', array_column($roles, 'rol'));      // string legible
        $row['roles_ids'] = array_column($roles, 'id_rol');                 // array de IDs

        // id_rol = rol primario (el primero asignado, para compatibilidad)
        $row['id_rol']    = !empty($roles) ? (int)$roles[0]['id_rol'] : (int)$row['id_rol'];

        return $row;
    }

    // ─────────────────────────────────────────────────────────────
    // ROLES DEL USUARIO
    // ─────────────────────────────────────────────────────────────

    /**
     * Obtener todos los roles de un usuario.
     */
    public function rolesPorUsuario(int $idUsuario): array
    {
        return $this->query("
            SELECT r.id_rol, r.rol
            FROM usuario_rol ur
            INNER JOIN rol r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = ?
            ORDER BY r.rol
        ", [$idUsuario])->fetchAll();
    }

    /**
     * Sincronizar roles de un usuario (elimina los existentes e inserta los nuevos).
     *
     * @param int   $idUsuario
     * @param int[] $idRoles    Array de IDs de roles a asignar
     */
    public function sincronizarRoles(int $idUsuario, array $idRoles): void
    {
        $this->beginTransaction();
        try {
            // Eliminar roles actuales
            $this->query("DELETE FROM usuario_rol WHERE id_usuario = ?", [$idUsuario]);

            // Insertar nuevos roles (únicos)
            $idRoles = array_unique(array_filter(array_map('intval', $idRoles)));
            foreach ($idRoles as $idRol) {
                $this->query(
                    "INSERT IGNORE INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)",
                    [$idUsuario, $idRol]
                );
            }

            // Mantener id_rol del usuario = primer rol asignado (compatibilidad)
            $primero = $idRoles[0] ?? null;
            if ($primero) {
                $this->query(
                    "UPDATE usuario SET id_rol = ? WHERE id_usuario = ?",
                    [$primero, $idUsuario]
                );
            }

            $this->commit();
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Permisos unificados (UNIÓN) de todos los roles del usuario.
     * Si CUALQUIER rol tiene permiso sobre un módulo, el usuario lo tiene.
     */
    public function permisosPorUsuario(int $idUsuario): array
    {
        return $this->query("
            SELECT
                mo.id_modulo, mo.codigo, mo.nombre, mo.url, mo.icono,
                mo.id_padre,
                MAX(rm.ver)      AS ver,
                MAX(rm.crear)    AS crear,
                MAX(rm.editar)   AS editar,
                MAX(rm.eliminar) AS eliminar
            FROM usuario_rol ur
            INNER JOIN rol_modulo rm ON rm.id_rol    = ur.id_rol
            INNER JOIN modulo     mo ON mo.id_modulo = rm.id_modulo
            WHERE ur.id_usuario = ?
              AND mo.estado = 'ACTIVO'
            GROUP BY mo.id_modulo
            ORDER BY mo.id_padre, mo.orden
        ", [$idUsuario])->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────

    /**
     * Listar usuarios con todos sus roles concatenados.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT u.id_usuario, u.usuario, u.estado, u.ultimo_login,
                   u.intentos_fallidos, u.bloqueado_hasta,
                   e.nombre_completo, e.correo_empleado, e.img_empleado,
                   c.cargo,
                   GROUP_CONCAT(r.rol ORDER BY r.rol SEPARATOR ', ') AS roles_nombres,
                   COUNT(ur.id_rol) AS total_roles
            FROM usuario u
            INNER JOIN empleado     e  ON e.id_empleado = u.id_empleado
            INNER JOIN cargo        c  ON c.id_cargo    = e.id_cargo
            LEFT  JOIN usuario_rol  ur ON ur.id_usuario = u.id_usuario
            LEFT  JOIN rol          r  ON r.id_rol      = ur.id_rol
            GROUP BY u.id_usuario
            ORDER BY u.id_usuario
        ")->fetchAll();
    }

    /**
     * Obtener usuario con datos completos + roles asignados.
     */
    public function verConDatos(int $id): ?array
    {
        $row = $this->query("
            SELECT u.*, e.nombre_completo, e.correo_empleado, e.img_empleado,
                   e.id_cargo, c.cargo
            FROM usuario u
            INNER JOIN empleado e ON e.id_empleado = u.id_empleado
            INNER JOIN cargo    c ON c.id_cargo    = e.id_cargo
            WHERE u.id_usuario = ?
            LIMIT 1
        ", [$id])->fetch();

        if (!$row) return null;

        $row['roles']     = $this->rolesPorUsuario($id);
        $row['roles_ids'] = array_column($row['roles'], 'id_rol');
        return $row;
    }

    /**
     * Crear usuario + asignar roles.
     *
     * @param int[] $idRoles  Uno o más IDs de rol
     */
    public function crear(
        string $nombre,
        string $correo,
        int    $idCargo,
        int    $idEmpresa,
        array  $idRoles,
        string $usuario,
        string $clave,
        string $estado = 'ACTIVO'
    ): int {
        $idRoles  = array_unique(array_filter(array_map('intval', $idRoles)));
        $rolPrimario = $idRoles[0] ?? 1;

        $this->beginTransaction();
        try {
            // 1. Crear empleado
            $this->query("
                INSERT INTO empleado
                    (nombre_completo, img_empleado, correo_empleado, id_cargo, id_empresa, estado_empleado)
                VALUES (?, 'usuario.png', ?, ?, ?, 'ACTIVO')
            ", [$nombre, $correo, $idCargo, $idEmpresa]);
            $idEmpleado = (int)$this->db->lastInsertId();

            // 2. Crear usuario
            $algo  = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $hash  = password_hash($clave, $algo);
            $this->query("
                INSERT INTO usuario (usuario, clave, id_rol, id_empleado, estado, clave_requiere_reset)
                VALUES (?, ?, ?, ?, ?, 0)
            ", [$usuario, $hash, $rolPrimario, $idEmpleado, $estado]);
            $idUsuario = (int)$this->db->lastInsertId();

            // 3. Insertar en usuario_rol
            foreach ($idRoles as $idRol) {
                $this->query(
                    "INSERT IGNORE INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)",
                    [$idUsuario, $idRol]
                );
            }

            $this->commit();
            return $idUsuario;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Actualizar estado y roles de un usuario.
     *
     * @param int[] $idRoles
     */
    public function actualizarEstadoRoles(int $idUsuario, string $estado, array $idRoles): void
    {
        $this->update($idUsuario, ['estado' => $estado]);
        $this->sincronizarRoles($idUsuario, $idRoles);
    }

    // ─────────────────────────────────────────────────────────────
    // SEGURIDAD
    // ─────────────────────────────────────────────────────────────

    public function registrarFallo(string $usuario): void
    {
        $this->query("
            UPDATE usuario
            SET intentos_fallidos = intentos_fallidos + 1,
                bloqueado_hasta = IF(intentos_fallidos + 1 >= 5,
                                     DATE_ADD(NOW(), INTERVAL 15 MINUTE), bloqueado_hasta)
            WHERE usuario = ?
        ", [$usuario]);
    }

    public function registrarLoginExitoso(int $idUsuario): void
    {
        $this->query("
            UPDATE usuario
            SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_login = NOW()
            WHERE id_usuario = ?
        ", [$idUsuario]);
    }

    public function estaBloqueado(string $usuario): bool
    {
        $row = $this->query(
            "SELECT bloqueado_hasta FROM usuario WHERE usuario = ? LIMIT 1",
            [$usuario]
        )->fetch();
        return $row && $row['bloqueado_hasta'] && strtotime($row['bloqueado_hasta']) > time();
    }

    public function cambiarClave(int $idUsuario, string $nuevaClave): void
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($nuevaClave, $algo);
        $this->query("
            UPDATE usuario
            SET clave = ?,
                fecha_cambio_clave = NOW(),
                clave_requiere_reset = 0
            WHERE id_usuario = ?
        ", [$hash, $idUsuario]);
    }

    public function verificarClave(int $idUsuario, string $clave): bool
    {
        $row = $this->query(
            "SELECT clave FROM usuario WHERE id_usuario = ? LIMIT 1",
            [$idUsuario]
        )->fetch();
        if (!$row) return false;
        return password_verify($clave, $row['clave']);
    }

    public function desbloquear(int $idUsuario): void
    {
        $this->query(
            "UPDATE usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?",
            [$idUsuario]
        );
    }

    public function porNombre(string $usuario): ?array
    {
        $row = $this->query(
            "SELECT * FROM usuario WHERE usuario = ? LIMIT 1",
            [$usuario]
        )->fetch();
        return $row ?: null;
    }
}
