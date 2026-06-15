<?php
/**
 * renombrar_codigos_carpetas_web.php
 * Renombra carpetas que tienen código sin ceros (AD-DT-1-NOMBRE)
 * a código con ceros (AD-DT-001-NOMBRE)
 *
 * Acceder: https://limaro.limaro.cloud/renombrar_codigos_carpetas_web.php?tok=limaro2026&dry
 * Ejecutar: ?tok=limaro2026
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
    <meta charset="UTF-8"><title>Renombrar Códigos Carpetas</title>
    <style>
        body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}
        h2{color:#569cd6;} .ok{color:#4ec9b0;} .warn{color:#ce9178;}
        .err{color:#f44747;} .info{color:#9cdcfe;}
        .box{background:#252526;padding:15px;border-radius:6px;margin:10px 0;
             max-height:600px;overflow-y:auto;}
        a.btn{display:inline-block;padding:8px 16px;background:#0e639c;color:#fff;
              text-decoration:none;border-radius:4px;margin:4px;}
        a.btn.green{background:#16825d;}
    </style>
</head>
<body>
<h2>📁 Renombrar Códigos en Carpetas de Documentos</h2>
<a class="btn" href="?tok=limaro2026&dry">🔍 Simular</a>
<a class="btn green" href="?tok=limaro2026">▶ Ejecutar</a>
<p class="<?= $dryRun?'warn':'ok' ?>"><?= $dryRun?'⚠️ SIMULACIÓN':'✅ MODO REAL' ?></p>
<p class="info">Convierte: <span>AD-DT-1-NOMBRE</span> → <span>AD-DT-001-NOMBRE</span></p>
<hr>

<?php
$renombradas = 0;
$errores     = 0;
$ya_ok       = 0;
$log         = [];

/**
 * Renombra el segmento de código en el nombre de carpeta
 * Patrón: SIGLA-SIGLA-NUM-RESTO → SIGLA-SIGLA-NUM_CON_CEROS-RESTO
 */
function renombrarCodigo(string $nombre): ?string {
    // Patrón: dos o más siglas separadas por guión, luego -NUMERO- sin ceros
    // Ej: AD-DT-1-CADENA → AD-DT-001-CADENA
    //     AH-FO-10-NOMBRE → AH-FO-010-NOMBRE
    if (preg_match('/^([A-Z]+-[A-Z]+-?)(\d+)(-.+)$/', $nombre, $m)) {
        $nuevo_num = str_pad($m[2], 3, '0', STR_PAD_LEFT);
        if ($nuevo_num !== $m[2]) {
            return $m[1] . $nuevo_num . $m[3];
        }
    }
    return null; // ya tiene el formato correcto
}

function procesarDirectorio(string $dir, bool $dry, array &$log,
                             int &$ren, int &$err, int &$ya): void {
    if (!is_dir($dir)) return;
    $items = array_diff(scandir($dir), ['.','..']);
    foreach ($items as $item) {
        $rutaAbs = $dir . DIRECTORY_SEPARATOR . $item;
        if (!is_dir($rutaAbs)) continue;

        // Intentar renombrar este directorio
        $nuevoNombre = renombrarCodigo($item);
        if ($nuevoNombre) {
            $nuevaRuta = $dir . DIRECTORY_SEPARATOR . $nuevoNombre;
            if ($dry) {
                $rel = str_replace('/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/', '', $dir);
                $log[] = "<span class='ok'>🔄 $rel/<strong>$item</strong> → <strong>$nuevoNombre</strong></span>";
                $ren++;
            } else {
                if (rename($rutaAbs, $nuevaRuta)) {
                    $rel = str_replace('/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/', '', $dir);
                    $log[] = "<span class='ok'>✅ $rel/$nuevoNombre</span>";
                    $ren++;
                    $rutaAbs = $nuevaRuta; // continuar con el nuevo nombre
                } else {
                    $log[] = "<span class='err'>❌ ERROR: $item</span>";
                    $err++;
                    continue;
                }
            }
        } else {
            $ya++;
        }

        // Procesar subdirectorios recursivamente
        procesarDirectorio($rutaAbs, $dry, $log, $ren, $err, $ya);
    }
}

procesarDirectorio($BASE, $dryRun, $log, $renombradas, $errores, $ya_ok);

echo '<div class="box">';
foreach ($log as $l) echo $l . '<br>';
if (empty($log)) echo '<span class="info">ℹ️ No se encontraron carpetas para renombrar</span>';
echo '</div>';
?>

<div class="box">
    <p class="ok">✅ <?= $dryRun?'Se renombrarían':'Renombradas' ?>: <strong><?= $renombradas ?></strong></p>
    <p class="info">ℹ️ Ya con formato correcto: <strong><?= $ya_ok ?></strong></p>
    <p class="err">❌ Errores: <strong><?= $errores ?></strong></p>
</div>

<?php if (!$dryRun && $renombradas > 0): ?>
<div class="box">
    <p class="ok">✅ Listo. Ahora ejecuta <span>migrar_archivos_versionamiento_web.php</span>
    para actualizar las rutas en BD.</p>
    <p class="warn">⚠️ Borrar este archivo después de verificar.</p>
</div>
<?php endif; ?>

</body></html>
