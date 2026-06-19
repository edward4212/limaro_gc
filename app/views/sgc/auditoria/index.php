<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i><?= e($pageTitle) ?></h2>
        <!--<small class="text-muted">ISO 9001:2015 — Cláusula 9.2</small>-->
    </div>
    <?php if (Auth::puede('auditoria_interna','crear')): ?>
    <a href="<?= e(APP_URL) ?>/auditoria-interna/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Programa
    </a>
    <?php endif; ?>
</div>

<!-- KPIs -->
<?php
$totAud  = array_column($resumen,'total','estado');
$totalP  = array_sum(array_column($resumen,'total'));
$kpis = [
    ['label'=>'Programadas', 'valor'=>$totAud['PROGRAMADA']??0, 'icono'=>'bi-calendar-event','tipo'=>'kpi-blue',  'filtro'=>'PROGRAMADA'],
    ['label'=>'En Curso',    'valor'=>$totAud['EN_CURSO']??0,   'icono'=>'bi-hourglass-split','tipo'=>'kpi-amber', 'filtro'=>'EN_CURSO'],
    ['label'=>'Finalizadas', 'valor'=>$totAud['FINALIZADA']??0, 'icono'=>'bi-check-circle',  'tipo'=>'kpi-green', 'filtro'=>'FINALIZADA'],
    ['label'=>'Canceladas',  'valor'=>$totAud['CANCELADA']??0,  'icono'=>'bi-x-circle',      'tipo'=>'kpi-rose',  'filtro'=>'CANCELADA'],
];
$kpiTotal = ['label'=>'Total Programas', 'valor'=>$totalP];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Año</th><th>Descripción</th><th>Auditor Líder</th><th>Período</th><th>NC</th><th>Cerrados</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($programas as $p): ?>
                <tr>
                    <td><strong><?= e($p['anio']) ?></strong></td>
                    <td class="col-objetivo"><?= e(truncar($p['descripcion'],500)) ?></td>
                    <td><?= e($p['auditor_nombre']??$p['auditor_lider_nombre']??$p['auditor_lider']??'—') ?></td>
                    <td style="font-size:12px;">
                        <?= $p['fecha_inicio'] ? fechaEs($p['fecha_inicio']) : '—' ?>
                        <?= $p['fecha_fin'] ? ' → ' . fechaEs($p['fecha_fin']) : '' ?>
                    </td>
                    <td class="text-center"><span class="badge bg-danger"><?= (int)$p['nc'] ?></span></td>
                    <td class="text-center"><span class="badge bg-success"><?= (int)$p['cerrados'] ?>/<?= (int)$p['total_hallazgos'] ?></span></td>
                    <td><?= badgeEstado($p['estado']) ?></td>
                    <td class="text-center" style="white-space:normal;">
                        <?php if (($p['estado'] ?? '') === 'FINALIZADA'): ?>
                        <!-- CA-1: auditorías finalizadas → solo Ver detalle -->
                        <a href="<?= e(APP_URL) ?>/auditoria-interna/ver/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-success py-0 px-2" title="Ver detalle (solo lectura)">
                            <i class="bi bi-eye me-1"></i>Ver
                        </a>
                        <?php elseif (Auth::puede('auditoria_interna','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/auditoria-interna/editar/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
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
