<?php
/**
 * Script de un solo uso para resetear la contraseña del administrador
 * después de migrar de AES_ENCRYPT a password_hash().
 *
 * INSTRUCCIONES:
 * 1. Ejecutar la migración 11_password_hash_migration.sql
 * 2. Editar $adminUser y $nuevaClave abajo
 * 3. Subir este archivo a /public/tools/reset_admin.php temporalmente
 * 4. Abrir https://limaro.limaro.cloud/tools/reset_admin.php
 * 5. ELIMINAR este archivo inmediatamente después
 */

// ── Seguridad: solo desde IP del servidor o localhost ──────────
$ipCliente = $_SERVER['REMOTE_ADDR'] ?? '';
$ipsPermitidas = ['127.0.0.1', '::1'];

if (!in_array($ipCliente, $ipsPermitidas, true)) {
    http_response_code(403);
    die('Acceso denegado. Solo desde localhost.');
}

// ── Configuración ──────────────────────────────────────────────
$adminUser  = 'admin';           // ← Cambiar al usuario administrador real
$nuevaClave = 'Admin2026*';      // ← CAMBIAR ESTO por una clave segura
// ──────────────────────────────────────────────────────────────

require_once dirname(__DIR__) . '/../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Verificar si Argon2ID está disponible (PHP 7.2+)
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    $hash = password_hash($nuevaClave, $algo);

    $stmt = $pdo->prepare(
        "UPDATE usuario SET clave = ?, clave_requiere_reset = 0 WHERE usuario = ?"
    );
    $stmt->execute([$hash, $adminUser]);

    if ($stmt->rowCount() === 0) {
        die("Usuario '$adminUser' no encontrado.");
    }

    echo "<pre>✅ Contraseña del admin '$adminUser' actualizada correctamente.\n";
    echo "Algoritmo usado: " . ($algo === PASSWORD_ARGON2ID ? 'Argon2ID' : 'bcrypt') . "\n";
    echo "Hash generado: " . substr($hash, 0, 30) . "...\n\n";
    echo "⚠️  ELIMINA ESTE ARCHIVO AHORA.\n</pre>";

} catch (Throwable $e) {
    die('Error: ' . $e->getMessage());
}
