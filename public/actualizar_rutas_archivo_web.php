<?php
/**
 * actualizar_rutas_archivo_web.php
 * Actualiza rutas en tabla `archivo` para que coincidan
 * con las carpetas renombradas (códigos con ceros)
 * Acceder: https://limaro.limaro.cloud/actualizar_rutas_archivo_web.php?tok=limaro2026&dry
 */
if (($_GET['tok'] ?? '') !== 'limaro2026') { die('Acceso denegado'); }
$dryRun = isset($_GET['dry']);

require_once '/home/limarocloud/limaro.limaro.cloud/config/config.php';
$_db = require '/home/limarocloud/limaro.limaro.cloud/config/database.php';
$pdo = new PDO('mysql:host='.$_db['host'].';dbname='.$_db['database'].';charset=utf8mb4',
               $_db['username'], $_db['password'],
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$BASE_PUBLIC = '/home/limarocloud/limaro.limaro.cloud/public';
$BASE_STORAGE = $BASE_PUBLIC . '/storage/documentos/';

?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Actualizar Rutas Archivo</title>
<style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}
h2{color:#569cd6;} .ok{color:#4ec9b0;} .warn{color:#ce9178;}
.err{color:#f44747;} .info{color:#9cdcfe;}
.box{background:#252526;padding:15px;border-radius:6px;margin:10px 0;
     max-height:600px;overflow-y:auto;}
a.btn{display:inline-block;padding:8px 16px;background:#0e639c;color:#fff;
      text-decoration:none;border-radius:4px;margin:4px;}
a.btn.green{background:#16825d;}
</style></head><body>
<h2>🔧 Actualizar Rutas en Tabla Archivo</h2>
<a class="btn" href="?tok=limaro2026&dry">🔍 Simular</a>
<a class="btn green" href="?tok=limaro2026">▶ Ejecutar</a>
<p class="<?= $dryRun?'warn':'ok' ?>"><?= $dryRun?'⚠️ SIMULACIÓN':'✅ MODO REAL' ?></p>
<hr>

<?php
// Función para convertir código sin ceros a con ceros en una ruta
// Ej: AD-DT-1-NOMBRE → AD-DT-001-NOMBRE
function normalizarCodigoEnRuta(string $ruta): string {
    return preg_replace_callback(
        '/([A-Z]{2}-[A-Z]{2,3}-)(\d{1,2})(-[^\/]+)/',
        function($m) {
            return $m[1] . str_pad($m[2], 3, '0', STR_PAD_LEFT) . $m[3];
        },
        $ruta
    );
}

// Obtener todos los archivos de VERSIONAMIENTO
$archivos = $pdo->query("
    SELECT id_archivo, ruta_relativa, nombre_original
    FROM archivo
    WHERE modulo = 'VERSIONAMIENTO'
    ORDER BY id_archivo
")->fetchAll(PDO::FETCH_ASSOC);

$actualizados = 0;
$ya_ok        = 0;
$no_existe     = 0;
$errores      = 0;

echo '<div class="box">';

foreach ($archivos as $ar) {
    $rutaVieja   = $ar['ruta_relativa'];
    $rutaNueva   = normalizarCodigoEnRuta($rutaVieja);

    // Verificar si la ruta ya está bien (el archivo existe en disco)
    $absVieja = $BASE_PUBLIC . $rutaVieja;
    $absNueva = $BASE_PUBLIC . $rutaNueva;

    if (file_exists($absVieja)) {
        // Ruta vieja funciona — no necesita actualización
        $ya_ok++;
        continue;
    }

    if (file_exists($absNueva)) {
        // Ruta nueva existe — actualizar en BD
        if ($dryRun) {
            echo "<span class='ok'>🔄 [{$ar['id_archivo']}] {$ar['nombre_original']}</span><br>";
            echo "<span style='color:#555;font-size:10px'>   DE: $rutaVieja</span><br>";
            echo "<span style='color:#4ec9b0;font-size:10px'>   A:  $rutaNueva</span><br>";
            $actualizados++;
        } else {
            try {
                $pdo->prepare("UPDATE archivo SET ruta_relativa = ? WHERE id_archivo = ?")
                    ->execute([$rutaNueva, $ar['id_archivo']]);
                echo "<span class='ok'>✅ [{$ar['id_archivo']}] {$ar['nombre_original']}</span><br>";
                $actualizados++;
            } catch (PDOException $e) {
                echo "<span class='err'>❌ [{$ar['id_archivo']}] " . htmlspecialchars($e->getMessage()) . "</span><br>";
                $errores++;
            }
        }
    } else {
        // Buscar el archivo recursivamente por nombre original
        $nombreBase = basename($rutaVieja);
        $encontrado = null;

        // Buscar en la carpeta del macroproceso
        foreach (['APOYO','MISIONALES','ESTRATEGICOS'] as $macro) {
            $dirMacro = $BASE_STORAGE . $macro . '/';
            if (!is_dir($dirMacro)) continue;
            $encontrado = buscarRecursivo($dirMacro, $nombreBase);
            if ($encontrado) break;
        }

        if ($encontrado) {
            $rutaEncontrada = str_replace($BASE_PUBLIC, '', $encontrado);
            if ($dryRun) {
                echo "<span class='warn'>🔍 [{$ar['id_archivo']}] Encontrado en nueva ubicación:<br>";
                echo "<span style='font-size:10px'>   $rutaEncontrada</span></span><br>";
                $actualizados++;
            } else {
                try {
                    $pdo->prepare("UPDATE archivo SET ruta_relativa = ? WHERE id_archivo = ?")
                        ->execute([$rutaEncontrada, $ar['id_archivo']]);
                    echo "<span class='ok'>✅ [{$ar['id_archivo']}] {$ar['nombre_original']}</span><br>";
                    $actualizados++;
                } catch (PDOException $e) {
                    $errores++;
                }
            }
        } else {
            // Archivo no encontrado en ningún lado
            echo "<span class='err'>⚠️ [{$ar['id_archivo']}] NO en disco: {$ar['nombre_original']}</span><br>";
            $no_existe++;
        }
    }
}

echo '</div>';

function buscarRecursivo(string $dir, string $nombre): ?string {
    if (!is_dir($dir)) return null;
    $items = @scandir($dir);
    if (!$items) return null;
    foreach (array_diff($items, ['.','..']) as $item) {
        $ruta = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($ruta) && $item === $nombre) return $ruta;
        if (is_dir($ruta)) {
            $r = buscarRecursivo($ruta, $nombre);
            if ($r) return $r;
        }
    }
    return null;
}
?>

<div class="box">
    <p class="ok">✅ <?= $dryRun?'Se actualizarían':'Actualizados' ?>: <strong><?= $actualizados ?></strong></p>
    <p class="info">ℹ️ Ya correctos (archivo existe): <strong><?= $ya_ok ?></strong></p>
    <p class="warn">⚠️ No encontrados en disco: <strong><?= $no_existe ?></strong></p>
    <p class="err">❌ Errores BD: <strong><?= $errores ?></strong></p>
</div>

<?php if (!$dryRun && $actualizados > 0): ?>
<div class="box">
    <p class="ok">✅ Rutas actualizadas. Los documentos ya deberían verse en el visor.</p>
    <p class="warn">⚠️ Borrar este archivo después de verificar.</p>
</div>
<?php endif; ?>
</body></html>
