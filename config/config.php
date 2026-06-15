<?php
/**
 * Configuración global de la aplicación Limaro SGC
 * 
 * Carga variables desde .env o usa los valores por defecto.
 * Establece constantes globales y configuración PHP.
 */

// Cargar .env si existe
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Helper para leer env
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return match (strtolower($value)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $value,
    };
}

// -------------------------------------------------------------------
// Constantes principales
// -------------------------------------------------------------------
define('APP_ROOT',     dirname(__DIR__));
define('APP_URL',      rtrim(env('APP_URL', 'http://localhost/limaro_sgc/public'), '/'));
define('APP_ENV',      env('APP_ENV', 'production'));
define('APP_DEBUG',    env('APP_DEBUG', false));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Bogota'));
define('AES_KEY',      env('AES_KEY', ''));  // Legacy: solo para migración de contraseñas antiguas
define('SESSION_NAME', env('SESSION_NAME', 'LIMARO_SGC'));
define('APP_VERSION',  'V3.0');   // ← Actualizar con cada release

// Configuración PHP
date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Autoloader PSR-4 simple
spl_autoload_register(function (string $class): void {
    $namespaces = [
        'App\\Controllers\\' => APP_ROOT . '/app/controllers/',
        'App\\Models\\'      => APP_ROOT . '/app/models/',
        'App\\Core\\'        => APP_ROOT . '/app/core/',
        'App\\Middlewares\\' => APP_ROOT . '/app/middlewares/',
        'App\\Services\\'    => APP_ROOT . '/app/services/',
    ];

    foreach ($namespaces as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file     = $dir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});
