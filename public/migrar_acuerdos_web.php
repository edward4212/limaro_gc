<?php
/**
 * migrar_acuerdos_web.php — Versión para hosting compartido (sin SSH)
 * Acceder en: https://limaro.limaro.cloud/migrar_acuerdos_web.php?tok=limaro2026
 * BORRAR después de usar
 */

// ── SEGURIDAD ────────────────────────────────────────────────────────
if (($_GET['tok'] ?? '') !== 'limaro2026') {
    http_response_code(403);
    die('Acceso denegado');
}

$dryRun = isset($_GET['dry']);

// ── CONFIGURACIÓN ────────────────────────────────────────────────────
// Cargar configuración del sistema
require_once '/home/limarocloud/limaro.limaro.cloud/config/config.php';
$_db = require '/home/limarocloud/limaro.limaro.cloud/config/database.php';
define('DB_HOST', $_db['host']);
define('DB_NAME', $_db['database']);
define('DB_USER', $_db['username']);
define('DB_PASS', $_db['password']);  // carga la BD del sistema

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración Archivos Acuerdos</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        h2 { color: #569cd6; }
        .ok   { color: #4ec9b0; }
        .warn { color: #ce9178; }
        .err  { color: #f44747; }
        .info { color: #9cdcfe; }
        .box  { background: #252526; padding: 15px; border-radius: 6px; margin: 10px 0; }
        a.btn { display:inline-block; padding:8px 16px; background:#0e639c; color:#fff;
                text-decoration:none; border-radius:4px; margin:4px; font-size:13px; }
        a.btn.green { background:#16825d; }
        a.btn.red   { background:#a1260d; }
    </style>
</head>
<body>
<h2>🗂️ Migración Archivos Acuerdos — Limaro SGC</h2>

<?php if ($dryRun): ?>
<p class="warn">⚠️ MODO SIMULACIÓN — no registra nada en BD</p>
<a class="btn green" href="?tok=limaro2026">▶ Ejecutar para registrar en BD</a>
<?php else: ?>
<p class="ok">✅ MODO REAL — registrando en tabla archivo</p>
<?php endif; ?>

<a class="btn" href="?tok=limaro2026&dry">🔍 Simular (dry-run)</a>
<hr>

<?php
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('<p class="err">Error BD: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

$STORAGE = '/home/limarocloud/limaro.limaro.cloud/public/storage/acuerdos/';

// La columna 'documento' ya no existe en producción
// Buscamos acuerdos SIN archivo en tabla archivo (los que faltan vincular)
$acuerdos = $pdo->query("
    SELECT a.id_acuerdo, a.año_acuerdo, a.numero_acuerdo, a.nombre_acuerdo,
           ar.id_archivo
    FROM acuerdos a
    LEFT JOIN archivo ar ON ar.modulo = 'ACUERDO' AND ar.id_referencia = a.id_acuerdo
    ORDER BY a.año_acuerdo, a.id_acuerdo
")->fetchAll(PDO::FETCH_ASSOC);

$registrados    = 0;
$ya_existe      = 0;
$no_encontrado  = 0;
$errores        = 0;
$no_encontrados = [];

echo '<div class="box">';

foreach ($acuerdos as $ac) {
    if ($ac['id_archivo']) {
        $ya_existe++;
        continue;
    }

    // Si ya tiene archivo vinculado → saltar
    if ($ac['id_archivo']) {
        $ya_existe++;
        continue;
    }

    $anio = $ac['año_acuerdo'];

    // Buscar archivos en la carpeta del año (cualquier archivo)
    $dirAnio = $STORAGE . $anio . '/';
    if (!is_dir($dirAnio)) {
        $no_encontrado++;
        echo "<span class='warn'>⚠️ Sin carpeta: $anio/{$ac['nombre_acuerdo']}</span><br>";
        continue;
    }

    $archivosEnDir = array_filter(
        scandir($dirAnio),
        fn($f) => $f !== '.' && $f !== '..' && is_file($dirAnio . $f)
    );

    if (empty($archivosEnDir)) {
        $no_encontrado++;
        echo "<span class='warn'>⚠️ Carpeta vacía: $anio/{$ac['nombre_acuerdo']}</span><br>";
        continue;
    }

    // Buscar el archivo que más se parece al nombre del acuerdo
    $nombreNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', $ac['nombre_acuerdo']));
    $mejorArchivo = null;
    $mejorScore   = 0;
    foreach ($archivosEnDir as $f) {
        $fNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', $f));
        similar_text($nombreNorm, $fNorm, $pct);
        if ($pct > $mejorScore) {
            $mejorScore   = $pct;
            $mejorArchivo = $f;
        }
    }
    $archivo = $mejorArchivo;
    $posibles = [$dirAnio . $archivo];

    $rutaReal = null;
    foreach ($posibles as $r) {
        if (file_exists($r)) { $rutaReal = $r; break; }
    }

    if (!$rutaReal) {
        $no_encontrado++;
        $no_encontrados[] = "$anio / $archivo";
        echo "<span class='warn'>⚠️ NO EN DISCO: [{$ac['id_acuerdo']}] $anio/$archivo</span><br>";
        continue;
    }

    $rutaRel  = '/storage/acuerdos/' . $anio . '/' . $archivo;
    $mime     = mime_content_type($rutaReal) ?: 'application/octet-stream';
    $tamano   = filesize($rutaReal);
    $hash     = hash_file('sha256', $rutaReal);

    if ($dryRun) {
        echo "<span class='ok'>🔄 [{$ac['id_acuerdo']}] $anio/$archivo — " . number_format($tamano/1024,1) . " KB</span><br>";
        $registrados++;
        continue;
    }

    try {
        $pdo->prepare("
            INSERT IGNORE INTO archivo
                (modulo, id_referencia, nombre_original, nombre_storage,
                 ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por, id_usuario)
            VALUES ('ACUERDO',?,?,?,?,?,?,?,'admin',1)
        ")->execute([
            $ac['id_acuerdo'], $archivo, $archivo,
            $rutaRel, $mime, $tamano, $hash
        ]);
        echo "<span class='ok'>✅ [{$ac['id_acuerdo']}] $anio/{$ac['nombre_acuerdo']}</span><br>";
        $registrados++;
    } catch (PDOException $e) {
        echo "<span class='err'>❌ [{$ac['id_acuerdo']}] " . htmlspecialchars($e->getMessage()) . "</span><br>";
        $errores++;
    }
}

echo '</div>';
?>

<div class="box">
    <h3 class="info">📊 Resumen</h3>
    <p class="ok">✅ Registrados: <strong><?= $registrados ?></strong></p>
    <p class="info">ℹ️ Ya tenían registro: <strong><?= $ya_existe ?></strong></p>
    <p class="warn">⚠️ No encontrados en disco: <strong><?= $no_encontrado ?></strong></p>
    <p class="err">❌ Errores: <strong><?= $errores ?></strong></p>
</div>

<?php if ($no_encontrados): ?>
<div class="box">
    <h3 class="warn">📁 Archivos que no están en disco</h3>
    <p>Copiar desde el servidor antiguo a: <code>public/storage/acuerdos/AÑO/</code></p>
    <?php foreach ($no_encontrados as $f): ?>
    <div class="warn">→ <?= htmlspecialchars($f) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$dryRun && $registrados > 0): ?>
<div class="box">
    <p class="ok">✅ Migración completada. <strong>Borrar este archivo del servidor.</strong></p>
</div>
<?php endif; ?>

</body>
</html>
