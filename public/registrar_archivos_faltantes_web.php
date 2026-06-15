<?php
/**
 * registrar_archivos_faltantes_web.php
 * Registra en tabla archivo los 4 documentos encontrados en disco
 * con su ruta real (diferente a la esperada)
 * Acceder: https://limaro.limaro.cloud/registrar_archivos_faltantes_web.php?tok=limaro2026&dry
 */
if (($_GET['tok'] ?? '') !== 'limaro2026') { die('Acceso denegado'); }
$dryRun = isset($_GET['dry']);

require_once '/home/limarocloud/limaro.limaro.cloud/config/config.php';
$_db = require '/home/limarocloud/limaro.limaro.cloud/config/database.php';
$pdo = new PDO('mysql:host='.$_db['host'].';dbname='.$_db['database'].';charset=utf8mb4',
               $_db['username'], $_db['password'],
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Registrar Archivos Faltantes</title>
<style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}
h2{color:#569cd6;} .ok{color:#4ec9b0;} .warn{color:#ce9178;}
.err{color:#f44747;} .info{color:#9cdcfe;}
.box{background:#252526;padding:15px;border-radius:6px;margin:10px 0;}
a.btn{display:inline-block;padding:8px 16px;background:#0e639c;color:#fff;
      text-decoration:none;border-radius:4px;margin:4px;}
a.btn.green{background:#16825d;}
</style></head><body>
<h2>📄 Registrar Archivos Encontrados en Rutas Alternativas</h2>
<a class="btn" href="?tok=limaro2026&dry">🔍 Simular</a>
<a class="btn green" href="?tok=limaro2026">▶ Ejecutar</a>
<p class="<?= $dryRun?'warn':'ok' ?>"><?= $dryRun?'⚠️ SIMULACIÓN':'✅ MODO REAL' ?></p>
<hr>

<?php
// Archivos encontrados con su ruta REAL en disco
$encontrados = [
    [
        'id_versionamiento' => 900,
        'nombre_original'   => '103 ACUERDO MANUAL DEL SARC 15 AGOSTO DE 2025.pdf',
        'ruta_relativa'     => '/storage/documentos/ESTRATEGICOS/SR-SISTEMA DE ADMINISTRACION DE RIESGO/MA-MANUAL/SR-MA-004-MANUAL SARC/2/103 ACUERDO MANUAL DEL SARC 15 AGOSTO DE 2025.pdf',
    ],
    [
        'id_versionamiento' => 281,
        'nombre_original'   => 'AHFO3 ARQUEO SEMANAL LIBRETAS DE AHORRO V1.xlsx',
        'ruta_relativa'     => '/storage/documentos/MISIONALES/AH-GESTION DE AHORROS/FO-FORMATO/AH-FO-003-ARQUEO SEMANAL LIBRETAS DE AHORRO/1/AHFO3 ARQUEO SEMANAL LIBRETAS DE AHORRO V1.xlsx',
    ],
    [
        'id_versionamiento' => 946,
        'nombre_original'   => 'CJPO1 POLITICA COBRO DIARIO EXTERNO V1.docx',
        'ruta_relativa'     => '/storage/documentos/MISIONALES/CJ/PO/CJ-PO-1/1/CJPO1 POLITICA COBRO DIARIO EXTERNO V1.docx',
    ],
    [
        'id_versionamiento' => 914,
        'nombre_original'   => '77. ACUERDO REGLAMENTO DE CREDITO 2024 (1).pdf',
        'ruta_relativa'     => '/storage/documentos/MISIONALES/CC-GESTION DE CREDITOS/RG-REGLAMENTO/CC-RG-001-REGLAMENTO DE CREDITO/1/77. ACUERDO REGLAMENTO DE CREDITO 2024 (1).pdf',
    ],
];

$BASE_PUBLIC = '/home/limarocloud/limaro.limaro.cloud/public';
$ok = 0; $err = 0;

echo '<div class="box">';
foreach ($encontrados as $f) {
    $absPath = $BASE_PUBLIC . $f['ruta_relativa'];

    // Verificar que el archivo sigue existiendo
    if (!file_exists($absPath)) {
        echo "<span class='err'>❌ Ya no existe en disco: {$f['ruta_relativa']}</span><br>";
        $err++;
        continue;
    }

    // Verificar que no está ya registrado
    $existe = $pdo->prepare("SELECT id_archivo FROM archivo WHERE modulo='VERSIONAMIENTO' AND id_referencia=?");
    $existe->execute([$f['id_versionamiento']]);
    if ($existe->fetch()) {
        echo "<span class='info'>ℹ️ Ya registrado: id_versionamiento={$f['id_versionamiento']}</span><br>";
        continue;
    }

    $mime   = mime_content_type($absPath) ?: 'application/octet-stream';
    $tamano = filesize($absPath);
    $hash   = hash_file('sha256', $absPath);
    $nombre = basename($absPath);

    if ($dryRun) {
        echo "<span class='ok'>🔄 id_ver={$f['id_versionamiento']} | {$f['nombre_original']}</span><br>";
        echo "<span style='color:#555;font-size:11px'>   → {$f['ruta_relativa']}</span><br>";
        $ok++;
        continue;
    }

    try {
        $pdo->prepare("
            INSERT IGNORE INTO archivo
                (modulo, id_referencia, nombre_original, nombre_storage,
                 ruta_relativa, mime_type, tamano_bytes, hash_sha256, subido_por, id_usuario)
            VALUES ('VERSIONAMIENTO',?,?,?,?,?,?,?,'admin',1)
        ")->execute([
            $f['id_versionamiento'],
            $f['nombre_original'],
            $nombre,
            $f['ruta_relativa'],
            $mime, $tamano, $hash
        ]);
        echo "<span class='ok'>✅ Registrado: {$f['nombre_original']}</span><br>";
        $ok++;
    } catch (PDOException $e) {
        echo "<span class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        $err++;
    }
}
echo '</div>';
?>

<div class="box">
    <p class="ok">✅ <?= $dryRun?'Se registrarían':'Registrados' ?>: <strong><?= $ok ?></strong></p>
    <p class="err">❌ Errores: <strong><?= $err ?></strong></p>
</div>

<div class="box">
    <h3 class="warn">📋 20 Archivos NO encontrados — copiar del servidor antiguo</h3>
    <p class="info">Estos archivos deben copiarse manualmente desde el servidor anterior
    a las carpetas correspondientes en el nuevo servidor:</p>
    <table style="width:100%;font-size:11px;">
        <tr><th>Código</th><th>Archivo en BD</th><th>Carpeta destino en nuevo servidor</th></tr>
        <tr><td>AD-PR-004</td><td>ADPR4 ACTIVOS FIJOS V1.docx</td><td>APOYO/AD-GESTION ADMINISTRATIVA/PR-PROCEDIMIENTO/AD-PR-004-ACTIVOS FIJOS/1/</td></tr>
        <tr><td>TH-FO-002</td><td>THFO2 POSTULACION A VACANTE PERSONAL INTERNO V1</td><td>APOYO/TH-GESTION TALENTO HUMANO/FO-FORMATO/TH-FO-002-.../1/</td></tr>
        <tr><td>TH-FO-031</td><td>THFO31 FORMATO UNICO DE HOJA DE VIDA FUNCIONARIO V1.xlsx</td><td>APOYO/TH-GESTION TALENTO HUMANO/FO-FORMATO/TH-FO-031-.../1/</td></tr>
        <tr><td>TH-FO-038</td><td>THFO37 AUTORIZACION DISFRUTE DE VACACIONES V1.docx</td><td>APOYO/TH-GESTION TALENTO HUMANO/FO-FORMATO/TH-FO-038-.../1/</td></tr>
        <tr><td>AC-RG-002 al AC-RG-020</td><td>ACRG2...ACRG12, ACUERDO 121...</td><td>ESTRATEGICOS/AC-ORGANISMOS.../RG-REGLAMENTO/AC-RG-00X-.../1/</td></tr>
        <tr><td>SR-PR-018</td><td>ADPR2 PROCEDIMIENTO DEL SARLAFT V1.docx</td><td>ESTRATEGICOS/SR-SISTEMA.../PR-PROCEDIMIENTO/SR-PR-018-.../1/</td></tr>
        <tr><td>AS-FO-001</td><td>ASFO1 SOLICITUD DE VINCULACION ADULTO V2.xlsx</td><td>MISIONALES/AS-GESTION DE ASOCIADOS/FO-FORMATO/AS-FO-001-.../1/ o 2/</td></tr>
        <tr><td>AS-FO-021</td><td>ASFO21 SOLICITUD INFANTIL V2.xlsx</td><td>MISIONALES/AS-GESTION DE ASOCIADOS/FO-FORMATO/AS-FO-021-.../1/</td></tr>
        <tr><td>CJ-DT-001</td><td>CJDT3 MANUAL OPERATIVO GESTOR RECAUDO V1.docx</td><td>MISIONALES/CJ-GESTION DE CAJA/DT-DOCUMENTO TECNICO/CJ-DT-001-.../1/</td></tr>
        <tr><td>CN-FO-023</td><td>CNFO23 CONVENIO DE RECAUDO V1.docx</td><td>MISIONALES/CN-GESTION DE CONVENIOS/FO-FORMATO/CN-FO-023-.../1/</td></tr>
    </table>
    <p class="warn mt-2">⚠️ El nombre del archivo en el nuevo servidor puede ser diferente —
    lo que importa es que esté en la carpeta correcta. Después de copiarlo,
    vuelve a ejecutar <span>migrar_archivos_versionamiento_web.php</span>.</p>
</div>

</body></html>
