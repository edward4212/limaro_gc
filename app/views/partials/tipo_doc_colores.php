<?php
if (!isset($_colorTipoMap)):
$_colorPool = [
    ['bg'=>'#EDE7F6','tx'=>'#4527A0'],
    ['bg'=>'#E3F2FD','tx'=>'#0D47A1'],
    ['bg'=>'#E0F7FA','tx'=>'#006064'],
    ['bg'=>'#E8F5E9','tx'=>'#1B5E20'],
    ['bg'=>'#FFF3E0','tx'=>'#E65100'],
    ['bg'=>'#F3E5F5','tx'=>'#6A1B9A'],
    ['bg'=>'#FCE4EC','tx'=>'#880E4F'],
    ['bg'=>'#E8EAF6','tx'=>'#1A237E'],
    ['bg'=>'#FFF8E1','tx'=>'#F57F17'],
    ['bg'=>'#E0F2F1','tx'=>'#004D40'],
    ['bg'=>'#FFEBEE','tx'=>'#B71C1C'],
    ['bg'=>'#F9FBE7','tx'=>'#558B2F'],
    ['bg'=>'#FBE9E7','tx'=>'#BF360C'],
    ['bg'=>'#E8F4FD','tx'=>'#1565C0'],
    ['bg'=>'#F0F4C3','tx'=>'#827717'],
    ['bg'=>'#FCEBD5','tx'=>'#6D4C41'],
    ['bg'=>'#E1F8EE','tx'=>'#1B6B3A'],
    ['bg'=>'#FAE8FF','tx'=>'#7B1FA2'],
    ['bg'=>'#FEF3E2','tx'=>'#C17B00'],
    ['bg'=>'#EDEFF7','tx'=>'#3949AB'],
];
$_siglas = (new \App\Models\TipoDocumentoModel())->getSiglas();
$_colorTipoMap = [];
foreach ($_siglas as $_i => $_sig) {
    $_colorTipoMap[$_sig] = $_colorPool[$_i % count($_colorPool)];
}
function tipoDocStyle(string $sigla): string {
    global $_colorTipoMap;
    if (!isset($_colorTipoMap[$sigla])) return '';
    return "background-color:{$_colorTipoMap[$sigla]['bg']};";
}
function tipoDocBg(string $sigla): string {
    global $_colorTipoMap;
    return $_colorTipoMap[$sigla]['bg'] ?? 'transparent';
}
endif;
?>
<style>
<?php foreach (($_colorTipoMap ?? []) as $_sig => $_c): ?>
tr.tipo-doc-<?= e($_sig) ?> { background-color: <?= $_c['bg'] ?> !important; }
tr.tipo-doc-<?= e($_sig) ?> td { background-color: <?= $_c['bg'] ?> !important; }
tr.tipo-doc-<?= e($_sig) ?>:hover td { background-color: <?= $_c['bg'] ?> !important; filter: brightness(0.96); }
<?php endforeach; ?>
</style>
