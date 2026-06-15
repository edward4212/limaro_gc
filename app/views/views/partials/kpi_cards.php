<?php
/**
 * Partial: Panel de KPI Cards responsive
 *
 * Uso:
 *   $kpis = [
 *     ['label'=>'Texto', 'valor'=>5, 'icono'=>'bi-check', 'tipo'=>'kpi-blue', 'filtro'=>'ESTADO'],
 *     ...
 *   ];
 *   $kpiTotal = ['label'=>'Total', 'valor'=>12]; // opcional
 *   include kpi_cards.php
 *
 * tipos disponibles: kpi-blue kpi-orange kpi-green kpi-teal kpi-amber kpi-rose kpi-red
 */
$kpiCols = count($kpis ?? []) + (!empty($kpiTotal) ? 1 : 0);
$colClass = match(true) {
    $kpiCols <= 3 => 'col-6 col-md-4',
    $kpiCols <= 4 => 'col-6 col-md-3',
    $kpiCols <= 6 => 'col-6 col-md-4 col-xl-2',
    default       => 'col-6 col-md-3 col-xl-2',
};
?>
<div class="row g-2 mb-4">
    <?php foreach ($kpis ?? [] as $k):
        $clickable = !empty($k['filtro']) ? "style='cursor:pointer;' onclick=\"filtrarTabla('{$k['filtro']}')\"" : '';
    ?>
    <div class="<?= $colClass ?>">
        <div class="kpi-card <?= $k['tipo'] ?? 'kpi-blue' ?>" <?= $clickable ?>>
            <div class="kpi-num"><?= (int)($k['valor'] ?? 0) ?></div>
            <div class="kpi-label"><?= e($k['label']) ?></div>
            <i class="bi <?= $k['icono'] ?? 'bi-circle' ?> kpi-icon"></i>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!empty($kpiTotal)): ?>
    <div class="<?= $colClass ?>">
        <div class="kpi-card" style="background:var(--lim-blue);cursor:pointer;" onclick="filtrarTabla('')">
            <div class="kpi-num"><?= (int)($kpiTotal['valor'] ?? 0) ?></div>
            <div class="kpi-label"><?= e($kpiTotal['label'] ?? 'Total') ?></div>
            <i class="bi bi-list-task kpi-icon"></i>
        </div>
    </div>
    <?php endif; ?>
</div>
