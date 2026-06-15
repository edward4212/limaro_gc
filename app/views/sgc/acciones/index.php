<div class="page-header">
    <div>
        <h2><i class="bi bi-tools me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 10.2</small>
    </div>
    <?php if (Auth::puede('acciones_correctivas','crear')): ?>
    <a href="<?= e(APP_URL) ?>/acciones-correctivas/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nueva AC
    </a>
    <?php endif; ?>
</div>

<!-- KPIs -->
<?php
$totales  = array_column($resumen,'total','estado');
$totalAC  = array_sum(array_column($resumen,'total'));
$kpis = [
    ['label'=>'Abiertas',       'valor'=>$totales['ABIERTA']??0,        'icono'=>'bi-exclamation-circle','tipo'=>'kpi-rose',   'filtro'=>'ABIERTA'],
    ['label'=>'En Tratamiento', 'valor'=>$totales['EN_TRATAMIENTO']??0, 'icono'=>'bi-hourglass-split',  'tipo'=>'kpi-amber',  'filtro'=>'EN_TRATAMIENTO'],
    ['label'=>'Verificación',   'valor'=>$totales['VERIFICACION']??0,   'icono'=>'bi-search',           'tipo'=>'kpi-teal',   'filtro'=>'VERIFICACION'],
    ['label'=>'Cerradas',       'valor'=>$totales['CERRADA']??0,        'icono'=>'bi-check-circle',     'tipo'=>'kpi-green',  'filtro'=>'CERRADA'],
    ['label'=>'Canceladas',     'valor'=>$totales['CANCELADA']??0,      'icono'=>'bi-x-circle',         'tipo'=>'kpi-blue',   'filtro'=>'CANCELADA'],
];
$kpiTotal = ['label'=>'Total AC', 'valor'=>$totalAC];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>
<div class="row g-3 mb-4 d-none">
    <?php foreach ([] as $est => [$col, $ico]): ?>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <i class="bi bi-<?= $ico ?> text-<?= $col ?> fs-2"></i>
            <div class="fw-bold"><?= $totales[$est] ?? 0 ?></div>
            <div class="text-muted" style="font-size:11px;"><?= str_replace('_',' ',$est) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Código</th><th>Origen</th><th>Descripción NC</th><th>Responsable</th><th>F. Planificada</th><th>Vencida</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($acciones as $a): ?>
                <?php
                $vencida = ($a['dias_vencida'] ?? 0) > 0 && !in_array($a['estado'],['CERRADA','CANCELADA']);
                ?>
                <tr class="<?= $vencida ? 'table-danger' : '' ?>">
                    <td><code><?= e($a['codigo']) ?></code></td>
                    <td><span class="badge bg-secondary" style="font-size:10px;"><?= e($a['origen']) ?></span></td>
                    <td style="font-size:12px;"><?= e(truncar($a['descripcion_nc'],65)) ?></td>
                    <td style="font-size:12px;"><?= e($a['responsable_nombre']??$a['responsable']??'—') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($a['fecha_planificada']??null) ?></td>
                    <td class="text-center">
                        <?php if ($vencida): ?>
                        <span class="badge bg-danger"><?= (int)$a['dias_vencida'] ?>d</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= badgeEstado($a['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('acciones_correctivas','editar')): ?>
                        <?php if (in_array($a['estado'] ?? '', ['CERRADA','CANCELADA'])): ?>
                        <a href="<?= e(APP_URL) ?>/acciones-correctivas/ver/<?= (int)$a['id'] ?>"
                           class="btn btn-sm btn-outline-secondary py-0" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php else: ?>
                        <a href="<?= e(APP_URL) ?>/acciones-correctivas/editar/<?= (int)$a['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla(val) {
    if ($.fn.DataTable.isDataTable('table.datatable')) {
        $('table.datatable').DataTable().search(val).draw();
    }
}
</script>
