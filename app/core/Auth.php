<?php

namespace App\Core;

/**
 * Gestión de autenticación y usuario en sesión.
 */
class Auth
{
    private const SESSION_KEY = '_auth_user';

    /**
     * Verificar si el usuario está autenticado.
     */
    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * Obtener usuario autenticado (o null).
     */
    public static function user(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * Obtener un campo del usuario autenticado.
     */
    public static function get(string $field, mixed $default = null): mixed
    {
        // Claves de sesión directa (no del objeto usuario)
        static $sessionKeys = ['_url_map', '_modulos'];
        if (in_array($field, $sessionKeys, true)) {
            return Session::get($field, $default);
        }
        $user = self::user();
        if (!$user) return $default;
        return $user[$field] ?? $default;
    }

    /**
     * Guardar usuario en sesión tras login exitoso.
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, $user);
    }

    /**
     * Cerrar sesión.
     */
    public static function logout(): void
    {
        Session::destroy();
    }

    /**
     * Obtener ID del usuario autenticado.
     */
    public static function id(): ?int
    {
        return self::get('id_usuario') ? (int) self::get('id_usuario') : null;
    }

    /**
     * Obtener ID de empleado del usuario autenticado.
     */
    public static function empleadoId(): ?int
    {
        return self::get('id_empleado') ? (int) self::get('id_empleado') : null;
    }

    /**
     * Obtener ID del rol del usuario autenticado.
     */
    public static function rolId(): ?int
    {
        return self::get('id_rol') ? (int) self::get('id_rol') : null;
    }

    /**
     * Verificar si el usuario tiene al menos uno de los roles indicados.
     * Compara contra el array 'roles_ids' guardado en sesión.
     *
     * @param string|int|array $roles  Nombre(s) o ID(s) de rol
     */
    public static function hasRole(string|int|array $roles): bool
    {
        $user = self::user();
        if (!$user) return false;

        $rolesUsuario = $user['roles'] ?? [];

        foreach ((array) $roles as $buscado) {
            foreach ($rolesUsuario as $r) {
                if (is_numeric($buscado)) {
                    if ((int)$r['id_rol'] === (int)$buscado) return true;
                } else {
                    if (strtoupper($r['rol']) === strtoupper((string)$buscado)) return true;
                }
            }
        }
        return false;
    }

    /**
     * Guardar módulos (con permisos) en sesión tras el login.
     */

    /**
     * Guardar un valor en la sesión (para cache de _url_map, etc.)
     */
    public static function set(string $key, mixed $value): void
    {
        Session::set($key, $value);
    }

    public static function setModulos(array $modulos): void
    {
        Session::set('_modulos', $modulos);
    }

    /**
     * Obtener módulos del usuario cargados en sesión.
     */
    public static function modulos(): array
    {
        return Session::get('_modulos', []);
    }

    /**
     * Verificar permiso sobre un módulo.
     * Admin (id_rol primario = 1) siempre puede.
     * Para los demás: UNIÓN de permisos de todos sus roles.
     *
     * @param string $codigo   Código del módulo
     * @param string $permiso  ver|crear|editar|eliminar
     */
    public static function puede(string $codigo, string $permiso = 'ver'): bool
    {
        // Admin siempre puede todo
        $rolesIds = self::get('roles_ids', []);
        if (in_array(1, array_map('intval', (array)$rolesIds), true)) {
            return true;
        }

        // Buscar en módulos cargados en sesión (unión de todos los roles)
        foreach (self::modulos() as $m) {
            if ($m['codigo'] === $codigo) {
                return (bool)($m[$permiso] ?? false);
            }
        }
        return false;
    }

    /**
     * Verificar si el usuario autenticado tiene un rol específico por nombre.
     * Útil para filtrar acciones sensibles (ej: asignar rol ADMINISTRADOR).
     */
    public static function tieneRol(string $rolNombre): bool
    {
        // Fuente primaria: array 'roles' guardado en sesión al login
        $roles = self::get('roles', []);
        if (is_array($roles) && !empty($roles)) {
            foreach ($roles as $r) {
                if (strtoupper($r['rol'] ?? '') === strtoupper($rolNombre)) {
                    return true;
                }
            }
            return false;
        }

        // Fallback: string CSV 'rol' (ej: "ADMINISTRADOR, COORDINADOR CALIDAD")
        // Usa comparación de token completo, no stripos parcial
        $csv = (string) self::get('rol', '');
        if ($csv === '') return false;
        foreach (array_map('trim', explode(',', $csv)) as $token) {
            if (strtoupper($token) === strtoupper($rolNombre)) {
                return true;
            }
        }
        return false;
    }

}