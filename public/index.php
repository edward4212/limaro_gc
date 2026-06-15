<?php
/**
 * Front Controller — Limaro SGC
 * Punto de entrada único para todas las peticiones HTTP.
 *
 * Orden de arranque:
 *  1. Cargar configuración (autoloader, constantes, .env)
 *  2. Iniciar sesión segura
 *  3. Aplicar headers de seguridad
 *  4. Cargar helpers
 *  5. Registrar rutas
 *  6. Despachar
 */

declare(strict_types=1);

// Manejo temprano de errores antes de cargar config
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$logPath = dirname(__DIR__) . '/storage/php_errors.log';

if (is_writable(dirname($logPath)) || @touch($logPath)) {
    ini_set('error_log', $logPath);
}

set_exception_handler(function (\Throwable $e): void {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === 'true' || (defined('APP_DEBUG') && APP_DEBUG);
    if ($debug) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "Archivo: " . $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Error</title>';
        echo '<div style="font-family:system-ui;padding:40px;text-align:center">';
        echo '<h1>Error interno</h1><p>Ha ocurrido un error. Contacte al administrador.</p>';
        echo '</div>';
    }
    error_log('[Limaro SGC] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    exit;
});

// Bootstrap
require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Session;
use App\Core\Router;
use App\Core\View;
use App\Core\Auth;
use App\Core\Database;

// Alias globales para que las vistas (sin namespace) puedan usar 'Auth::' directamente
if (!class_exists('Auth', false)) {
    class_alias(\App\Core\Auth::class, 'Auth');
}

// Iniciar sesión
Session::start();

// Headers de seguridad HTTP
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=()');

if (APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Cargar helpers globales
require_once APP_ROOT . '/app/helpers/format.php';
require_once APP_ROOT . '/app/helpers/upload.php';
require_once APP_ROOT . '/app/helpers/audit.php';
require_once APP_ROOT . '/app/helpers/carpeta_documento.php';
require_once APP_ROOT . '/app/helpers/notificaciones.php';

// Compartir datos globales con todas las vistas
if (Auth::check()) {
    // Cargar módulos del usuario (solo si no están en sesión aún)
    if (empty(Auth::modulos())) {
        try {
            $db      = Database::getInstance();
            $rolId   = Auth::rolId();
            $stmt    = $db->prepare("
                SELECT m.*, rm.ver, rm.crear, rm.editar, rm.eliminar
                FROM modulo m
                INNER JOIN rol_modulo rm ON rm.id_modulo = m.id_modulo
                WHERE rm.id_rol = ?
                  AND m.estado = 'ACTIVO'
                ORDER BY m.id_padre, m.orden
            ");
            $stmt->execute([$rolId]);
            Auth::setModulos($stmt->fetchAll());
        } catch (Throwable $e) {
            // Registrar error pero permitir continuar sin módulos
            error_log('[Limaro SGC] Error cargando módulos del usuario: ' . $e->getMessage());
            Auth::setModulos([]); // Establecer array vacío en lugar de null
        }
    }

    View::share('authUser', Auth::user());
    View::share('modulos',  Auth::modulos());
}

// Registrar rutas
$router = new Router();
require_once APP_ROOT . '/config/routes.php';

// Despachar
$router->dispatch();
