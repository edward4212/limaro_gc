<?php
/**
 * cron_vencimiento_usuarios.php
 * Limaro SGC — HU-E05: Vencimiento automático de cuentas de usuario
 *
 * Tareas (en este orden):
 *   1. Inactivar usuarios cuya fecha_vencimiento ya pasó (CA-4).
 *   2. Enviar alertas de vencimiento próximo a 30, 15 y 5 días (CA-3).
 *
 * USO EN cPanel > Cron Jobs:
 *   /usr/local/bin/php /home/limarocloud/limaro.limaro.cloud/scripts/cron_vencimiento_usuarios.php
 *   Frecuencia recomendada: una vez al día (ej. 0 6 * * *).
 *
 * IMPORTANTE: ejecutar como máximo una vez por día. La comparación de "días
 * restantes" es por fecha calendario (no por hora exacta); si el cron se
 * ejecuta más de una vez el mismo día, se enviarían alertas duplicadas.
 *
 * Este script es de solo lectura/escritura sobre la BD vía los modelos existentes;
 * no toca rutas HTTP, sesión, ni el router. Seguro de ejecutar por CLI o cron.
 *
 * SALIDA: imprime un resumen por línea de comandos y registra en error_log
 * cualquier fallo individual sin detener el procesamiento de los demás usuarios.
 */

declare(strict_types=1);

// No ejecutar si se invoca por HTTP (defensa en profundidad — este script es solo para CLI/cron)
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la línea de comandos.');
}

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_ROOT . '/app/helpers/format.php';
require_once APP_ROOT . '/app/helpers/notificaciones.php';

use App\Models\UsuarioModel;

$model  = new UsuarioModel();
$inicio = microtime(true);

echo "=== Limaro SGC — Cron de vencimiento de usuarios — " . date('Y-m-d H:i:s') . " ===\n";

// ── 1. Inactivación por vencimiento (CA-4) ────────────────────────────
$vencidos = [];
try {
    $vencidos = $model->usuariosVencidos();
} catch (\Throwable $e) {
    error_log('[cron_vencimiento_usuarios] Error obteniendo usuarios vencidos: ' . $e->getMessage());
}

$inactivados = 0;
foreach ($vencidos as $u) {
    try {
        $model->inactivarPorVencimiento((int) $u['id_usuario']);
        $resultado = notifCuentaInactivadaPorVencimiento($u);
        $inactivados++;
        echo "  [INACTIVADO] {$u['usuario']} (venció {$u['fecha_vencimiento']}) — correo: "
           . ($resultado['enviados'] > 0 ? 'enviado' : 'fallido') . "\n";
    } catch (\Throwable $e) {
        error_log("[cron_vencimiento_usuarios] Error inactivando usuario id={$u['id_usuario']}: " . $e->getMessage());
        echo "  [ERROR] No se pudo inactivar {$u['usuario']}: " . $e->getMessage() . "\n";
    }
}
echo "Usuarios inactivados por vencimiento: {$inactivados}\n";

// ── 2. Alertas de vencimiento próximo: 30, 15 y 5 días (CA-3) ─────────
$admins = [];
try {
    $admins = $model->soloAdministradores();
} catch (\Throwable $e) {
    error_log('[cron_vencimiento_usuarios] Error obteniendo administradores: ' . $e->getMessage());
}

$totalAlertas = 0;
foreach ([30, 15, 5] as $dias) {
    $proximos = [];
    try {
        $proximos = $model->usuariosPorVencerEn($dias);
    } catch (\Throwable $e) {
        error_log("[cron_vencimiento_usuarios] Error obteniendo usuarios a {$dias} días: " . $e->getMessage());
        continue;
    }

    foreach ($proximos as $u) {
        try {
            $resultado = notifCuentaPorVencer($u, $admins, $dias);
            $totalAlertas++;
            echo "  [ALERTA {$dias}d] {$u['usuario']} — enviados: {$resultado['enviados']}, fallidos: {$resultado['fallidos']}\n";
        } catch (\Throwable $e) {
            error_log("[cron_vencimiento_usuarios] Error notificando usuario id={$u['id_usuario']} ({$dias}d): " . $e->getMessage());
            echo "  [ERROR] No se pudo notificar a {$u['usuario']}: " . $e->getMessage() . "\n";
        }
    }
}
echo "Alertas de vencimiento próximo enviadas: {$totalAlertas}\n";

$duracion = round(microtime(true) - $inicio, 2);
echo "=== Finalizado en {$duracion}s ===\n";
