<?php
/**
 * renombrar_carpetas_web.php — Versión para hosting compartido
 * Acceder en: https://limaro.limaro.cloud/renombrar_carpetas_web.php?tok=limaro2026
 * Primero: ?tok=limaro2026&dry  (simulación)
 * Luego:   ?tok=limaro2026      (ejecutar)
 * BORRAR después de usar
 */
if (($_GET['tok'] ?? '') !== 'limaro2026') {
    http_response_code(403); die('Acceso denegado');
}
$dryRun = isset($_GET['dry']);
// Cargar configuración del sistema
require_once '/home/limarocloud/limaro.limaro.cloud/config/config.php';
$_db = require '/home/limarocloud/limaro.limaro.cloud/config/database.php';
define('DB_HOST', $_db['host']);
define('DB_NAME', $_db['database']);
define('DB_USER', $_db['username']);
define('DB_PASS', $_db['password']);
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Renombrar Carpetas Documentos</title>
    <style>
        body { font-family:monospace; background:#1e1e1e; color:#d4d4d4; padding:20px; }
        h2   { color:#569cd6; }
        .ok  { color:#4ec9b0; } .warn { color:#ce9178; }
        .err { color:#f44747; } .info { color:#9cdcfe; }
        .box { background:#252526; padding:15px; border-radius:6px; margin:10px 0; }
        a.btn { display:inline-block; padding:8px 16px; background:#0e639c; color:#fff;
                text-decoration:none; border-radius:4px; margin:4px; }
        a.btn.green { background:#16825d; }
    </style>
</head>
<body>
<h2>📁 Renombrar Carpetas Documentos — Limaro SGC</h2>
<a class="btn" href="?tok=limaro2026&dry">🔍 Simular</a>
<a class="btn green" href="?tok=limaro2026">▶ Ejecutar</a>
<p class="<?= $dryRun ? 'warn' : 'ok' ?>"><?= $dryRun ? '⚠️ SIMULACIÓN' : '✅ MODO REAL' ?></p>
<hr>
<?php
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
                   DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die('<p class="err">'.htmlspecialchars($e->getMessage()).'</p>'); }

$BASE = '/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/';

function san(string $s): string {
    $s = mb_strtoupper(trim($s),'UTF-8');
    $s = strtr($s,['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
                    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
    $s = preg_replace('/[\/\\\\:*?"<>|]/','', $s);
    return trim(preg_replace('/\s+/',' ', $s));
}

$macros   = $pdo->query("SELECT id_macroproceso,macroproceso FROM macroproceso")
               ->fetchAll(PDO::FETCH_KEY_PAIR);
$procesos = $pdo->query("SELECT id_proceso,id_macroproceso,proceso,sigla_proceso FROM proceso")
               ->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
$tipos    = $pdo->query("SELECT id_tipo_documento,tipo_documento,sigla_tipo_documento FROM tipo_documento")
               ->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

$docs = $pdo->query("SELECT d.*,GROUP_CONCAT(DISTINCT v.numero_version) AS versiones
                     FROM documento d
                     LEFT JOIN versionamiento v ON v.id_documento=d.id_documento
                     GROUP BY d.id_documento")->fetchAll(PDO::FETCH_ASSOC);

$ok=$warn=$skip=$err=0;
echo '<div class="box" style="max-height:500px;overflow-y:auto;">';

foreach ($docs as $doc) {
    $idProc = $doc['id_proceso'];
    $idTipo = $doc['id_tipo_documento'];
    if (!isset($procesos[$idProc])) continue;
    $proc  = $procesos[$idProc];
    $idMac = $proc['id_macroproceso'];
    $tipo  = $tipos[$idTipo] ?? null;

    $segMacro = san($macros[$idMac] ?? '');
    $segProc  = san($proc['sigla_proceso']).'-'.san($proc['proceso']);
    $segTipo  = $tipo ? san($tipo['sigla_tipo_documento']).'-'.san($tipo['tipo_documento']) : 'SIN-TIPO';
    $segDoc   = san($doc['codigo']).'-'.san($doc['nombre_documento']);
    $rutaNueva = implode(DIRECTORY_SEPARATOR,[$segMacro,$segProc,$segTipo,$segDoc]);
    $destAbs  = $BASE.$rutaNueva;

    if (is_dir($destAbs)) { $skip++; continue; }

    // Buscar ruta antigua: MACRO/SIGPROC/SIGTIPO/CODIGO
    $sigMac  = san($macros[$idMac] ?? '');
    $sigProc = san($proc['sigla_proceso']);
    $sigTipo = $tipo ? san($tipo['sigla_tipo_documento']) : '';
    $codigo  = san($doc['codigo']);

    $posibles = [
        $BASE.implode(DIRECTORY_SEPARATOR,[$sigMac,$sigProc,$sigTipo,$codigo]),
        $BASE.implode(DIRECTORY_SEPARATOR,[$sigMac,$sigProc,$codigo]),
        $BASE.$codigo,
    ];
    $origen = null;
    foreach ($posibles as $p) { if (is_dir($p)) { $origen=$p; break; } }

    if (!$origen) { $warn++; continue; }

    if ($dryRun) {
        echo "<span class='info'>🔄 {$doc['codigo']}</span><br>";
        echo "<span style='color:#666;font-size:11px'>   DE: ".htmlspecialchars(str_replace($BASE,'',$origen))."</span><br>";
        echo "<span style='color:#666;font-size:11px'>   A:  ".htmlspecialchars($rutaNueva)."</span><br>";
        $ok++; continue;
    }

    $padre = dirname($destAbs);
    if (!is_dir($padre)) mkdir($padre, 0755, true);
    if (rename($origen, $destAbs)) {
        $pdo->prepare("UPDATE documento SET ruta_carpeta=? WHERE id_documento=?")
            ->execute([$rutaNueva,$doc['id_documento']]);
        echo "<span class='ok'>✅ {$doc['codigo']}</span><br>";
        $ok++;
    } else {
        echo "<span class='err'>❌ {$doc['codigo']}</span><br>";
        $err++;
    }
}
echo '</div>';
?>
<div class="box">
    <p class="ok">✅ Renombradas: <strong><?=$ok?></strong></p>
    <p class="info">ℹ️ Ya con nombre nuevo: <strong><?=$skip?></strong></p>
    <p class="warn">⚠️ No encontradas en disco: <strong><?=$warn?></strong></p>
    <p class="err">❌ Errores: <strong><?=$err?></strong></p>
</div>
<?php
// ── LIMPIAR CARPETAS VACÍAS ──────────────────────────────────────────
if (!$dryRun && $ok > 0):

function eliminarCarpetasVacias(string $dir): int {
    $eliminadas = 0;
    if (!is_dir($dir)) return 0;
    $items = array_diff(scandir($dir), ['.','..']);
    foreach ($items as $item) {
        $ruta = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($ruta)) {
            $eliminadas += eliminarCarpetasVacias($ruta);
            // Si después de limpiar hijos queda vacía, eliminar
            if (count(array_diff(scandir($ruta), ['.','..'])) === 0) {
                rmdir($ruta);
                $eliminadas++;
            }
        }
    }
    return $eliminadas;
}

$eliminadas = eliminarCarpetasVacias($BASE);
echo '<div class="box"><p class="ok">🗑️ Carpetas vacías eliminadas: <strong>' . $eliminadas . '</strong></p></div>';
endif;
?>
</body></html>
