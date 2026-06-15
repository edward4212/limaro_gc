<?php
/**
 * diagnostico_carpetas_web.php
 * Muestra la estructura de carpetas en storage/documentos/
 * y busca los archivos de los 24 documentos sin registro
 * Acceder: https://limaro.limaro.cloud/diagnostico_carpetas_web.php?tok=limaro2026
 */
if (($_GET['tok'] ?? '') !== 'limaro2026') { die('Acceso denegado'); }

$BASE = '/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/';

require_once '/home/limarocloud/limaro.limaro.cloud/config/config.php';
$_db = require '/home/limarocloud/limaro.limaro.cloud/config/database.php';
$pdo = new PDO('mysql:host='.$_db['host'].';dbname='.$_db['database'].';charset=utf8mb4',
               $_db['username'], $_db['password']);

?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Diagnóstico Carpetas</title>
<style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}
h2,h3{color:#569cd6;} .ok{color:#4ec9b0;} .warn{color:#ce9178;}
.err{color:#f44747;} .info{color:#9cdcfe;}
.box{background:#252526;padding:12px;border-radius:6px;margin:8px 0;}
table{width:100%;border-collapse:collapse;font-size:12px;}
td,th{padding:4px 8px;border:1px solid #444;text-align:left;}
th{background:#0e639c;}
</style></head><body>
<h2>🔍 Diagnóstico — 24 Documentos Sin Archivo</h2>

<h3>1. Carpetas de primer nivel en ESTRATEGICOS/</h3>
<div class="box">
<?php
$estrategicos = $BASE . 'ESTRATEGICOS/';
if (is_dir($estrategicos)) {
    foreach (array_diff(scandir($estrategicos), ['.','..']) as $d) {
        echo "<span class='info'>📁 $d</span><br>";
        // Subcarpetas
        $sub = $estrategicos . $d . '/';
        if (is_dir($sub)) {
            foreach (array_diff(scandir($sub), ['.','..']) as $s) {
                echo "&nbsp;&nbsp;&nbsp;<span class='ok'>└─ $s</span><br>";
            }
        }
    }
} else { echo "<span class='err'>No existe $estrategicos</span>"; }
?>
</div>

<h3>2. Búsqueda de archivos de los 24 documentos</h3>
<div class="box">
<?php
// Los 24 documentos
$docs = [
    [963,'ADPR4 ACTIVOS FIJOS V1.docx','AD-PR-004','ACTIVOS FIJOS','Gestion Administrativa','APOYO','AD','PR'],
    [190,'THFO2 POSTULACION A VACANTE PERSONAL INTERNO V1','TH-FO-002','POSTULACION A VACANTE PERSONAL INTERNO','Gestion Talento Humano','APOYO','TH','FO'],
    [220,'THFO31 FORMATO UNICO DE HOJA DE VIDA FUNCIONARIO V1.xlsx','TH-FO-031','FORMATO UNICO DE HOJA DE VIDA FUNCIONARIO','Gestion Talento Humano','APOYO','TH','FO'],
    [957,'THFO37 AUTORIZACION DISFRUTE DE VACACIONES V1.docx','TH-FO-038','AUTORIZACION DISFRUTE DE VACACIONES','Gestion Talento Humano','APOYO','TH','FO'],
    [11,'ACRG2 REGLAMENTO RETRIBUCIONES A DIRECTIVOS V2.pdf','AC-RG-002','REGLAMENTO RETRIBUCIONES A DIRECTIVOS','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [12,'ACRG3 REGLAMENTO DEL FONDO PARA PROMOVER LOS DERECHOS V1.pdf','AC-RG-003','REGLAMENTO DEL FONDO PARA PROMOVER LOS DERECHOS Y DEBERES DE LOS ASOCIADOS','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [13,'ACRG4 REGLAMENTO DE FONDO DE EDUCACION V1.pdf','AC-RG-004','REGLAMENTO DE FONDO DE EDUCACION','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [15,'ACRG5 REGLAMENTO DE FONDO DE SOLIDARIDAD V1.pdf','AC-RG-005','REGLAMENTO DE FONDO DE SOLIDARIDAD','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [17,'ACRG6 REGLAMENTO INTERNO DE JUNTA DE VIGILANCIA V2.pdf','AC-RG-006','REGLAMENTO INTERNO DE JUNTA DE VIGILANCIA','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [19,'ACRG7 REGLAMENTO DE COMITE DE EDUCACION V2.pdf','AC-RG-007','REGLAMENTO DE COMITE DE EDUCACION','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [22,'ACRG9 REGLAMENTO DEL COMITE DE RIESGO DE LIQUIDEZ V1.pdf','AC-RG-009','REGLAMENTO DEL COMITE DE RIESGO DE LIQUIDEZ','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [23,'ACRG9 REGLAMENTO DE COMITE DE SOLIDARIDAD V1.pdf','AC-RG-010','REGLAMENTO DE COMITE DEL SOLIDARIDAD','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [24,'ACRG11 REGLAMENTO DE COMITE DE EVALUACION DE CARTERA V1.pdf','AC-RG-011','REGLAMENTO DEL COMITE DE EVALUACION DE CARTERA','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [25,'ACRG12 REGLAMENTO DE COMITE DE RIESGOS V1.pdf','AC-RG-012','REGLAMENTO DEL COMITE DE RIESGOS','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [960,'ACUERDO 121 DE 26 DE FEBRERO 2026 CONFIDENCIALIDAD.pdf','AC-RG-020','ACUERDO DE CONFIDENCIALIDAD','Organismos de Administracion, Control, Vigilancia y Comites','ESTRATEGICOS','AC','RG'],
    [900,'103 ACUERDO MANUAL DEL SARC 15 AGOSTO DE 2025.pdf','SR-MA-004','MANUAL SARC','Sistema De Administracion De Riesgo','ESTRATEGICOS','SR','MA'],
    [99,'ADPR2 PROCEDIMIENTO DEL SARLAFT V1.docx','SR-PR-018','PROCEDIMIENTO DEL SARLAFT','Sistema De Administracion De Riesgo','ESTRATEGICOS','SR','PR'],
    [281,'AHFO3 ARQUEO SEMANAL LIBRETAS DE AHORRO V1.xlsx','AH-FO-003','ARQUEO SEMANAL LIBRETAS DE AHORRO','Gestion De Ahorros','MISIONALES','AH','FO'],
    [959,'ASFO1 SOLICITUD DE VINCULACION DE ASOCIADO ADULTO V2.xlsx','AS-FO-001','SOLICITUD DE VINCULACION DE ASOCIADO','Gestion De Asociados','MISIONALES','AS','FO'],
    [958,'ASFO21 SOLICITUD DE VINCULACION DE ASOCIADO INFANTIL V2.xlsx','AS-FO-021','VINCULACION INFANTIL','Gestion De Asociados','MISIONALES','AS','FO'],
    [948,'CJDT3 MANUAL OPERATIVO DEL GESTOR DE RECAUDO DIARIO V1.docx','CJ-DT-001','MANUAL OPERATIVO DEL GESTOR DE RECAUDO DIARIO','Gestion De Caja','MISIONALES','CJ','DT'],
    [946,'CJPO1 POLITICA COBRO DIARIO EXTERNO V1.docx','CJ-PO-002','POLITICA COBRO DIARIO EXTERNO','Gestion De Caja','MISIONALES','CJ','PO'],
    [128,'CNFO23 CONVENIO DE RECAUDO V1.docx','CN-FO-023','CONVENIO DE RECAUDO','Gestion De Convenios','MISIONALES','CN','FO'],
    [914,'77. ACUERDO REGLAMENTO DE CREDITO 2024 (1).pdf','CC-RG-001','REGLAMENTO DE CREDITO','Gestion De Creditos','MISIONALES','CC','RG'],
];

function san(string $s): string {
    $s = mb_strtoupper(trim($s),'UTF-8');
    $map = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'];
    $s = strtr($s, $map);
    return trim(preg_replace('/\s+/',' ', preg_replace('/[\/\\\\:*?"<>|]/','', $s)));
}

// Buscar recursivamente un archivo por nombre
function buscarArchivo(string $dir, string $nombre): ?string {
    if (!is_dir($dir)) return null;
    $items = @scandir($dir);
    if (!$items) return null;
    foreach (array_diff($items,['.','..']) as $item) {
        $ruta = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($ruta) && $item === $nombre) return $ruta;
        if (is_dir($ruta)) {
            $r = buscarArchivo($ruta, $nombre);
            if ($r) return $r;
        }
    }
    return null;
}

echo '<table><tr><th>Código</th><th>Archivo</th><th>Estado</th><th>Ruta encontrada</th></tr>';
foreach ($docs as [$id, $archivo, $codigo, $nombre, $proceso, $macro, $sigProc, $sigTipo]) {
    $BASE_MACRO = '/home/limarocloud/limaro.limaro.cloud/public/storage/documentos/' . san($macro) . '/';
    // Buscar el archivo en todo el macroproceso
    $encontrado = buscarArchivo($BASE_MACRO, $archivo);
    if (!$encontrado) {
        // Buscar sin extensión (algunos no tienen)
        $nombreSinExt = pathinfo($archivo, PATHINFO_FILENAME);
        $encontrado = buscarArchivo($BASE_MACRO, $nombreSinExt);
    }
    if ($encontrado) {
        $rel = str_replace('/home/limarocloud/limaro.limaro.cloud/public', '', $encontrado);
        echo "<tr><td>$codigo</td><td style='font-size:10px;'>$archivo</td>
              <td class='ok'>✅ ENCONTRADO</td>
              <td style='font-size:10px;color:#ce9178;'>$rel</td></tr>";
    } else {
        echo "<tr><td>$codigo</td><td style='font-size:10px;'>$archivo</td>
              <td class='err'>❌ NO ENCONTRADO</td><td class='err'>Buscar en servidor antiguo</td></tr>";
    }
}
echo '</table>';
?>
</div>
</body></html>
