<?php

namespace App\Core;

/**
 * Gestor de sesiones seguras de la aplicación.
 * Configura cookie httpOnly, Secure (en producción), SameSite=Lax.
 */
class Session
{
    private static bool $started = false;

    /**
     * Iniciar sesión con configuración segura.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        session_name(SESSION_NAME);

        $secure   = (APP_ENV === 'production');
        $lifetime = (int) env('SESSION_LIFETIME', 7200);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    /**
     * Regenerar ID de sesión (usar tras login exitoso).
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Obtener valor de sesión.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Establecer valor de sesión.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Eliminar clave de sesión.
     */
    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Verificar si existe una clave.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Destruir sesión completamente.
     */
    public static function destroy(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            self::$started = false;
        }
    }

    // -------------------------------------------------------------------
    // Flash messages (viven solo un request)
    // -------------------------------------------------------------------

    /**
     * Guardar mensaje flash.
     */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Leer y eliminar mensaje flash.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Verificar si existe un flash.
     */
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    // -------------------------------------------------------------------
    // Old input (repopular formularios tras error)
    // -------------------------------------------------------------------

    public static function setOldInput(array $data): void
    {
        $_SESSION['_old_input'] = $data;
    }

    public static function getOldInput(string $key, mixed $default = ''): mixed
    {
        $value = $_SESSION['_old_input'][$key] ?? $default;
        return $value;
    }

    public static function clearOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }
}
