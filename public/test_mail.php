<?php
/**
 * test_mail.php v2 — BORRAR después de usar
 * https://limaro.limaro.cloud/test_mail.php?tok=limaro2026test&to=tucorreo@ejemplo.com
 */
if (($_GET['tok'] ?? '') !== 'limaro2026test') die('Acceso denegado.');

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_ROOT . '/app/helpers/format.php';
require_once APP_ROOT . '/app/helpers/notificaciones.php';

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;">';
echo "=== DIAGNÓSTICO CORREO v2 ===\n\n";

echo "── Variables (getenv) ──\n";
foreach (['MAIL_FROM','MAIL_FROM_NAME','MAIL_SMTP_HOST','MAIL_SMTP_PORT','MAIL_SMTP_ENC','MAIL_SMTP_USER'] as $k) {
    echo "  $k = " . (getenv($k) ?: '(vacía)') . "\n";
}
echo "  MAIL_SMTP_PASS = " . (getenv('MAIL_SMTP_PASS') ? '(definida ✅)' : '(vacía ❌)') . "\n";

echo "\n── Constantes PHP ──\n";
foreach (['MAIL_FROM','MAIL_SMTP_HOST','MAIL_SMTP_PORT','MAIL_SMTP_ENC','MAIL_SMTP_USER'] as $k) {
    echo "  $k = " . (defined($k) ? constant($k) : '❌ NO DEFINIDA') . "\n";
}
echo "  MAIL_SMTP_PASS = " . (defined('MAIL_SMTP_PASS') && MAIL_SMTP_PASS ? '(definida ✅)' : '❌ VACÍA') . "\n";

echo "\n── PHPMailer ──\n";
echo "  Disponible: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'SÍ ✅' : 'NO ❌') . "\n";

echo "\n── Conexión SMTP ──\n";
$conn = @fsockopen(MAIL_SMTP_HOST ?: 'mail.limaro.cloud', (int)(MAIL_SMTP_PORT ?: 465), $e, $es, 5);
echo "  " . (MAIL_SMTP_HOST ?: '?') . ":" . (MAIL_SMTP_PORT ?: '?') . " → ";
if ($conn) { echo "CONECTADO ✅\n"; fclose($conn); } else { echo "FALLO ❌ ($e: $es)\n"; }

echo "\n── Prueba de envío ──\n";
$to = $_GET['to'] ?? '';
if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $ok = enviarCorreo([$to => 'Test SGC'], '[SGC] Prueba ' . date('H:i:s'),
        '<h2 style="color:#1B3A6B;">✅ Correo funcionando</h2>
         <p>Si recibes este mensaje, el sistema de correo del SGC está operativo.</p>
         <p><small>Servidor: ' . MAIL_SMTP_HOST . ':' . MAIL_SMTP_PORT . ' (' . MAIL_SMTP_ENC . ')</small></p>');
    echo "  Envío a $to: " . ($ok ? 'ÉXITO ✅' : 'FALLÓ ❌') . "\n";
    if (!$ok) echo "  Revisa: /var/log/php_errors.log o error_log del hosting\n";
} else {
    echo "  Agrega &to=tucorreo@dominio.com para probar\n";
}

echo "\n  variables_order: " . ini_get('variables_order') . "\n";
echo "  PHP: " . PHP_VERSION . "\n";
echo '</pre>';

