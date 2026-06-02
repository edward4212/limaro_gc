<?php
/**
 * Script de un solo uso: crear carpetas personales para usuarios YA existentes.
 * Ejecutar UNA VEZ después de subir los archivos nuevos.
 *
 * URL: https://limaro.limaro.cloud/tools/crear_carpetas_usuarios.php
 * Eliminar después de ejecutar.
 */

// Solo desde localhost
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1','::1'], true)) {
    http_response_code(403); die('Acceso denegado.');
}

require_once dirname(__DIR__) . '/../config/config.php';
require_once APP_ROOT . '/app/helpers/carpeta_documento.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $usuarios = $pdo->query("SELECT id_usuario, usuario FROM usuario ORDER BY id_usuario")->fetchAll(PDO::FETCH_ASSOC);
    $creados   = 0;
    $errores   = [];

    echo "<pre>Creando carpetas para " . count($usuarios) . " usuario(s)...\n\n";

    foreach ($usuarios as $u) {
        try {
            carpetaUsuario((int)$u['id_usuario'], 'foto');
            carpetaUsuario((int)$u['id_usuario'], 'firmas');
            carpetaUsuario((int)$u['id_usuario'], 'documentos');
            echo "✅ Usuario {$u['id_usuario']} ({$u['usuario']})\n";
            $creados++;
        } catch (Throwable $e) {
            $errores[] = "❌ Usuario {$u['id_usuario']}: " . $e->getMessage();
            echo end($errores) . "\n";
        }
    }

    echo "\n─── Resultado ─────────────────────────────\n";
    echo "Carpetas creadas: $creados\n";
    echo "Errores:          " . count($errores) . "\n";
    echo "\n⚠️  ELIMINA ESTE ARCHIVO AHORA.\n</pre>";

} catch (Throwable $e) {
    die('Error de BD: ' . $e->getMessage());
}
