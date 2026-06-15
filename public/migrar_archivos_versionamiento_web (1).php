<?php
/**
 * migrar_archivos_versionamiento_web.php
 * Registra en tabla `archivo` los archivos físicos de versionamiento
 * buscándolos por el campo `documento` (nombre del archivo) en las
 * carpetas de storage/documentos/
 *
 * Acceder: https://limaro.limaro.cloud/migrar_archivos_versionamiento_web.php?tok=limaro2026&dry
 * Ejecutar: ?tok=limaro2026
 * BORRAR después de usar
 */
if (($_GET['tok'] ?? '') !== 'limaro2026') {
    http_response_code(403); die('Acceso denegado');
}
$dryRun = isset($_GET['dry']);

require_once '/home/limarocloud/limaro.limaro.cloud/config/config.php';
$_db = require '/home/limarocloud/limaro.limaro.cloud/config/database.php';

try {
    $pdo = new PDO(
        'mysql:host='.$_db['host'].';dbname='.$_db['database'].';charset=utf8mb4',
        $_db['username'], $_db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Error BD: ' . htmlspecialchars($e->getMessage()));
}

$STORAGE = '/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/';

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migrar Archivos Versionamiento</title>
    <style>
        body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}
        h2{color:#569cd6;} .ok{color:#4ec9b0;} .warn{color:#ce9178;}
        .err{color:#f44747;} .info{color:#9cdcfe;}
        .box{background:#252526;padding:15px;border-radius:6px;margin:10px 0;
             max-height:500px;overflow-y:auto;}
        a.btn{display:inline-block;padding:8px 16px;background:#0e639c;color:#fff;
              text-decoration:none;border-radius:4px;margin:4px;}
        a.btn.green{background:#16825d;}
    </style>
</head>
<body>
<h2>📄 Migrar Archivos de Versionamiento</h2>
<a class="btn" href="?tok=limaro2026&dry">🔍 Simular</a>
<a class="btn green" href="?tok=limaro2026">▶ Ejecutar</a>
<p class="<?= $dryRun?'warn':'ok' ?>"><?= $dryRun?'⚠️ SIMULACIÓN':'✅ MODO REAL' ?></p>
<hr>

<?php
// Obtener versiones con campo documento pero sin registro en tabla archivo
$versiones = $pdo->query("
    SELECT v.id_versionamiento, v.id_documento, v.numero_version,
           v.documento AS nombre_archivo,
           d.codigo, d.nombre_documento,
           p.sigla_proceso, p.proceso,
           m.macroproceso,
           td.sigla_tipo_documento, td.tipo_documento
    FROM versionamiento v
    INNER JOIN documento      d  ON d.id_documento       = v.id_documento
    INNER JOIN proceso        p  ON p.id_proceso         = d.id_proceso
    INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
    INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
    LEFT  JOIN archivo ar ON ar.modulo = 'VERSIONAMIENTO'
                         AND ar.id_referencia = v.id_versionamiento
    WHERE v.documento IS NOT NULL
      AND v.documento <> ''
      AND ar.id_archivo IS NULL
    ORDER BY v.id_documento, v.numero_version
")->fetchAll(PDO::FETCH_ASSOC);

echo "<p class='info'>Versiones sin archivo registrado: <strong>" . count($versiones) . "</strong></p>";

function sanDir(string $s): string {
    $s = mb_strtoupper(trim($s), 'UTF-8');
    $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
                    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
    $s = preg_replace('/[\/\\\\:*?"<>|]/', '', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

// Construir posibles rutas donde puede estar el archivo
function buscarArchivo(array $v, string $BASE): ?string {
    $macro   = sanDir($v['macroproceso']);
    $sigProc = sanDir($v['sigla_proceso']);
    $proc    = sanDir($v['proceso']);
    $sigTipo = sanDir($v['sigla_tipo_documento']);
    $tipo    = sanDir($v['tipo_documento']);
    $codigo  = sanDir($v['codigo']);
    $nombre  = sanDir($v['nombre_documento']);
    $archivo = $v['nombre_archivo'];
    $ver     = (string)$v['numero_version'];

    // Ruta nueva con subcarpeta de versión numérica (estructura actual en servidor)
    // APOYO/AD-GESTION ADMINISTRATIVA/DT-DOCUMENTO TECNICO/AD-DT-1-NOMBRE/1/archivo.pdf
    $carpetaDoc = implode(DIRECTORY_SEPARATOR, [
        $macro,
        "$sigProc-$proc",
        "$sigTipo-$tipo",
        "$codigo-$nombre"
    ]);

    // Ruta antigua con subcarpeta de versión
    $carpetaAntigua = implode(DIRECTORY_SEPARATOR, [$macro, $sigProc, $sigTipo, $codigo]);

    $posibles = [
        // ── Con subcarpeta de versión numérica (estructura real del servidor) ──
        $BASE . $carpetaDoc . DIRECTORY_SEPARATOR . $ver . DIRECTORY_SEPARATOR . $archivo,
        // ── Sin subcarpeta de versión (directo en carpeta doc) ──
        $BASE . $carpetaDoc . DIRECTORY_SEPARATOR . $archivo,
        // ── Ruta antigua con versión ──
        $BASE . $carpetaAntigua . DIRECTORY_SEPARATOR . $ver . DIRECTORY_SEPARATOR . $archivo,
        // ── Ruta antigua sin versión ──
        $BASE . $carpetaAntigua . DIRECTORY_SEPARATOR . $archivo,
        // ── Solo código/versión ──
        $BASE . $codigo . DIRECTORY_SEPARATOR . $ver . DIRECTORY_SEPARATOR . $archivo,
        $BASE . $codigo . DIRECTORY_SEPARATOR . $archivo,
    ];

    foreach ($posibles as $ruta) {
        if (file_exists($ruta)) return $ruta;
    }
    return null;
}

$registrados   = 0;
$no_encontrado = 0;
$errores       = 0;
$lista_no_enc  = [];

echo '<div class="box">';

foreach ($versiones as $v) {
    $rutaReal = buscarArchivo($v, $STORAGE);

    if (!$rutaReal) {
        $no_encontrado++;
        $lista_no_enc[] = $v['codigo'] . ' V' . $v['numero_version'] . ': ' . $v['nombre_archivo'];
        continue;
    }

    // Construir ruta relativa para BD
    $rutaRel = '/storage/documentos/' . str_replace(
        [DIRECTORY_SEPARATOR, $STORAGE],
        ['/', ''],
        $rutaReal
    );
    $rutaRel = str_replace('/home/limarocloud/limaro.limaro.cloud/public', '', $rutaRel);

    $mime   = mime_content_type($rutaReal) ?: 'application/octet-stream';
    $tamano = filesize($rutaReal);
    $hash   = hash_file('sha256', $rutaReal);

    if ($dryRun) {
        echo "<span class='ok'>🔄 [{$v['codigo']} V{$v['numero_version']}] {$v['nombre_archivo']}</span><br>";
        echo "<span style='color:#555;font-size:11px'>   → " . htmlspecialchars($rutaRel) . "</span><br>";
        $registrados++;
        continue;
    }

    try {
        $pdo->prepare("
            INSERT IGNORE INTO archivo
                (modulo, id_referencia, nombre_original, nombre_storage,
                 ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por, id_usuario)
            VALUES ('VERSIONAMIENTO', ?, ?, ?, ?, ?, ?, ?, 'admin', 1)
        ")->execute([
            $v['id_versionamiento'],
            $v['nombre_archivo'],
            basename($rutaReal),
            $rutaRel,
            $mime,
            $tamano,
            $hash,
        ]);
        echo "<span class='ok'>✅ [{$v['codigo']} V{$v['numero_version']}]</span><br>";
        $registrados++;
    } catch (PDOException $e) {
        echo "<span class='err'>❌ [{$v['codigo']}]: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        $errores++;
    }
}

echo '</div>';
?>

<div class="box">
    <p class="ok">✅ <?= $dryRun?'Se registrarían':'Registrados' ?>: <strong><?= $registrados ?></strong></p>
    <p class="warn">⚠️ Archivo no encontrado en disco: <strong><?= $no_encontrado ?></strong></p>
    <p class="err">❌ Errores: <strong><?= $errores ?></strong></p>
</div>

<?php if ($no_encontrado > 0): ?>
<div class="box">
    <h3 class="warn">📁 Archivos no encontrados (<?= $no_encontrado ?>)</h3>
    <p class="info">Estos archivos están en BD pero no en disco — deben copiarse desde el servidor antiguo.</p>
    <?php foreach (array_slice($lista_no_enc, 0, 50) as $item): ?>
    <div class="warn" style="font-size:12px">→ <?= htmlspecialchars($item) ?></div>
    <?php endforeach; ?>
    <?php if (count($lista_no_enc) > 50): ?>
    <p class="info">... y <?= count($lista_no_enc)-50 ?> más</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$dryRun && $registrados > 0): ?>
<div class="box">
    <p class="ok">✅ Listo. Verifica que los documentos se puedan descargar en el sistema.</p>
    <p class="warn">⚠️ Borrar este archivo del servidor después de verificar.</p>
</div>
<?php endif; ?>

</body></html>
