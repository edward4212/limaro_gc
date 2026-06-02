<?php

namespace App\Core;

/**
 * Generación y validación de tokens CSRF.
 * Obligatorio en todos los formularios POST.
 */
class Csrf
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_LEN = 32;

    /**
     * Obtener token CSRF (crea uno si no existe).
     */
    public static function token(): string
    {
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(self::TOKEN_LEN)));
        }
        return Session::get(self::TOKEN_KEY);
    }

    /**
     * Renderizar campo oculto con token CSRF.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    /**
     * Validar token enviado vs el de sesión.
     * Si falla, aborta con 419.
     */
    public static function verify(): void
    {
        $token = $_POST['_csrf_token'] ?? '';

        if (!hash_equals(self::token(), $token)) {
            http_response_code(419);
            // Regenerar token tras fallo
            Session::delete(self::TOKEN_KEY);
            die('Token CSRF inválido. Por favor recargue la página e intente de nuevo.');
        }

        // Regenerar token después de cada POST exitoso (double-submit mitigation)
        Session::delete(self::TOKEN_KEY);
    }

    /**
     * Verificar sin abortar — retorna bool.
     */
    public static function check(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        return hash_equals(self::token(), $token);
    }
}
