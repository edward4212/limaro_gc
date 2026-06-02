<?php

namespace App\Core;

/**
 * Respuestas HTTP de la aplicación.
 */
class Response
{
    /**
     * Redirigir a una URL.
     */
    public static function redirect(string $url, int $code = 302): never
    {
        // Si la URL no es absoluta, construir con APP_URL
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = APP_URL . $url;
        }
        header('Location: ' . $url, true, $code);
        exit;
    }

    /**
     * Redirigir con mensaje flash de éxito.
     */
    public static function redirectSuccess(string $url, string $message): never
    {
        Session::flash('success', $message);
        self::redirect($url);
    }

    /**
     * Redirigir con mensaje flash de error.
     */
    public static function redirectError(string $url, string $message): never
    {
        Session::flash('error', $message);
        self::redirect($url);
    }

    /**
     * Redirigir de vuelta (referer o URL por defecto).
     */
    public static function back(string $default = '/inicio'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer && str_contains($referer, APP_URL)) {
            header('Location: ' . $referer, true, 302);
        } else {
            self::redirect($default);
        }
        exit;
    }

    /**
     * Respuesta JSON.
     */
    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Respuesta de descarga de archivo.
     */
    public static function download(string $filePath, string $fileName): never
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('Archivo no encontrado.');
        }

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }

    /**
     * Abortar con código HTTP y mensaje.
     */
    public static function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        $messages = [
            400 => 'Solicitud incorrecta',
            403 => 'Acceso denegado',
            404 => 'Página no encontrada',
            419 => 'Token CSRF expirado',
            500 => 'Error interno del servidor',
        ];
        $msg = $message ?: ($messages[$code] ?? 'Error');
        echo '<h1>' . $code . ' — ' . htmlspecialchars($msg) . '</h1>';
        exit;
    }
}
