<?php

namespace App\Core;

/**
 * Abstracción del request HTTP.
 * Provee acceso seguro a GET/POST/FILES/SERVER.
 */
class Request
{
    /**
     * Obtener parámetro de cualquier fuente (POST > GET).
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return $value;
    }

    /**
     * Obtener parámetro POST.
     */
    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtener parámetro GET.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtener todos los datos POST.
     */
    public static function all(): array
    {
        return $_POST;
    }

    /**
     * Obtener datos POST filtrados (solo las claves indicadas).
     */
    public static function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $_POST[$key] ?? null;
        }
        return $result;
    }

    /**
     * Verificar si el método es POST.
     */
    public static function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Verificar si el método es GET.
     */
    public static function isGet(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }

    /**
     * Obtener método HTTP.
     */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Obtener URI actual.
     */
    public static function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    /**
     * Obtener IP del cliente.
     */
    public static function ip(): string
    {
        // SECURITY: Only trust X-Forwarded-For if behind a known trusted proxy.
        // Using REMOTE_ADDR as primary source prevents IP spoofing in audit logs.
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return $remoteAddr;
        }
        return '0.0.0.0';
    }

    /**
     * Obtener User-Agent.
     */
    public static function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    }

    /**
     * Verificar si es una petición AJAX.
     */
    public static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Obtener archivo subido.
     */
    public static function file(string $key): ?array
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE
            ? $_FILES[$key]
            : null;
    }

    /**
     * Verificar si hay un archivo subido.
     */
    public static function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }
}
