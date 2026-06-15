<?php
/**
 * limpiar_carpetas_vacias_web.php
 * Elimina carpetas vacías en storage/documentos/
 * Acceder: https://limaro.limaro.cloud/limpiar_carpetas_vacias_web.php?tok=limaro2026
 * Simular: ?tok=limaro2026&dry
 * BORRAR después de usar
 */
if (($_GET['tok'] ?? '') !== 'limaro2026') {
    http_response_code(403); die('Acceso denegado');
}
$dryRun = isset($_GET['dry']);
$BASE   = '/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Limpiar Carpetas Vacías</title>
    <style>
        body { font-family:monospace; background:#1e1e1e; color:#d4d4d4; padding:20px; }
        h2   { color:#569cd6; }
        .ok  { color:#4ec9b0; } .warn { color:#ce9178; }
        .err { color:#f44747; } .info { color:#9cdcfe; }
        .box { background:#252526; padding:15px; border-radius:6px; margin:10px 0; }
        a.btn { display:inline-block; padding:8px 16px; background:#0e639c; color:#fff;
                text-decoration:none; border-radius:4px; margin:4px; }
        a.btn.red { background:#a1260d; }
    </style>
</head>
<body>
<h2>🗑️ Limpiar Carpetas Vacías — storage/documentos/</h2>
<a class="btn" href="?tok=limaro2026&dry">🔍 Simular</a>
<a class="btn red" href="?tok=limaro2026">🗑️ Eliminar vacías</a>
<p class="<?= $dryRun ? 'warn':'ok' ?>"><?= $dryRun ? '⚠️ SIMULACIÓN':'✅ MODO REAL' ?></p>
<hr>

<?php
$eliminadas = 0;
$conservadas = 0;
$log = [];

function procesarDir(string $dir, bool $dry, array &$log, int &$elim, int &$cons): void {
    if (!is_dir($dir)) return;
    $items = array_diff(scandir($dir), ['.','..']);

    // Primero procesar subdirectorios recursivamente
    foreach ($items as $item) {
        $ruta = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($ruta)) {
            procesarDir($ruta, $dry, $log, $elim, $cons);
        }
    }

    // Re-leer después de procesar hijos
    $items = array_diff(scandir($dir), ['.','..']);
    if (empty($items)) {
        // Carpeta vacía
        $rel = str_replace('/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/', '', $dir);
        if ($dry) {
            $log[] = "<span class='warn'>🗑️ ELIMINARÍA: $rel</span>";
        } else {
            if (rmdir($dir)) {
                $log[] = "<span class='ok'>✅ ELIMINADA: $rel</span>";
                $elim++;
            } else {
                $log[] = "<span class='err'>❌ ERROR al eliminar: $rel</span>";
            }
        }
        if ($dry) $elim++;
    } else {
        $cons++;
    }
}

procesarDir($BASE, $dryRun, $log, $eliminadas, $conservadas);

echo '<div class="box" style="max-height:500px;overflow-y:auto;">';
foreach ($log as $l) echo $l . '<br>';
if (empty($log)) echo '<span class="info">ℹ️ No se encontraron carpetas vacías</span>';
echo '</div>';
?>

<div class="box">
    <p class="<?= $dryRun?'warn':'ok' ?>">
        <?= $dryRun ? '⚠️ Se eliminarían' : '✅ Eliminadas' ?>: <strong><?= $eliminadas ?></strong>
    </p>
    <p class="info">📁 Con contenido (conservadas): <strong><?= $conservadas ?></strong></p>
</div>

<?php if ($dryRun): ?>
<div class="box">
    <p class="warn">👆 Para eliminar realmente: quita el <span>&dry</span> de la URL</p>
</div>
<?php elseif ($eliminadas > 0): ?>
<div class="box">
    <p class="ok">✅ Listo. Borrar este archivo del servidor cuando termines.</p>
</div>
<?php endif; ?>
</body></html>
