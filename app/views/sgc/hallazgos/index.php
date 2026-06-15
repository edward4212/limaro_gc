<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-exclamation-triangle me-2"></i>Gestión de Hallazgos</h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 9.2</small>
    </div>
    <?php if (Auth::puede('gestion_hallazgos','crear')): ?>
    <a href="<?= e(APP_URL) ?>/hallazgos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Hallazgo
    </a>
    <?php endif; ?>
</div>

<!-- KPIs -->
<?php
$kpis = [
    ['label'=>'Abiertos',       'valor'=>$resumen['ABIERTO']??0,        'icono'=>'bi-exclamation-circle','tipo'=>'kpi-rose',  'filtro'=>'ABIERTO'],
    ['label'=>'En Tratamiento', 'valor'=>$resumen['EN_TRATAMIENTO']??0, 'icono'=>'bi-hourglass-split',  'tipo'=>'kpi-amber', 'filtro'=>'EN_TRATAMIENTO'],
    ['label'=>'Cerrados',       'valor'=>$resumen['CERRADO']??0,        'icono'=>'bi-check-circle',     'tipo'=>'kpi-green', 'filtro'=>'CERRADO'],
];
$kpiTotal = ['label'=>'Total Hallazgos', 'valor'=>$resumen['total']??0];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Estado</label>
                <select class="form-select form-select-sm" name="estado" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach (['ABIERTO','EN_TRATAMIENTO','CERRADO'] as $e): ?>
                    <option value="<?= $e ?>" <?= ($filtroEst??'') === $e ? 'selected':'' ?>>
                        <?= ucfirst(strtolower(str_replace('_',' ',$e))) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Tipo</label>
                <select class="form-select form-select-sm" name="tipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach (['NO_CONFORMIDAD','OBSERVACION','OPORTUNIDAD'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($filtroTipo??'') === $t ? 'selected':'' ?>>
                        <?= ucfirst(strtolower(str_replace('_',' ',$t))) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (($filtroEst??'') || ($filtroTipo??'')): ?>
            <a href="/hallazgos" class="btn btn-sm btn-outline-secondary btn-limpiar-filtros">
                <i class="bi bi-x me-1"></i>Limpiar
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm datatable datatable-export mb-0"
               id="tablaHallazgos" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th style="width:110px">Tipo</th>
                    <th style="width:130px">Auditoría</th>
                    <th style="width:120px">Responsable AC</th>
                    <th style="width:100px" class="text-center">Estado</th>
                    <th style="width:90px">Vencimiento</th>
                    <th style="width:80px" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hallazgos as $h):
                $badge = match($h['estado'] ?? '') {
                    'ABIERTO'        => 'bg-danger',
                    'EN_TRATAMIENTO' => 'bg-warning text-dark',
                    'CERRADO'        => 'bg-success',
                    default          => 'bg-secondary',
                };
                $tipoBadge = match($h['tipo'] ?? '') {
                    'NO_CONFORMIDAD' => 'bg-danger',
                    'OBSERVACION'    => 'bg-info text-dark',
                    'OPORTUNIDAD'    => 'bg-success',
                    default          => 'bg-secondary',
                };
                $vencido = !empty($h['fecha_limite'])
                    && $h['estado'] !== 'CERRADO'
                    && strtotime($h['fecha_limite']) < time();
            ?>
            <tr class="<?= $vencido ? 'table-danger' : '' ?>">
                <td style="font-size:12px;"><strong>#<?= (int)$h['id'] ?></strong></td>
                <td style="font-size:12px;max-width:220px;">
                    <?= e(truncar($h['descripcion'] ?? '', 70)) ?>
                    <?php if (!empty($h['id_ac'])): ?>
                    <br><span class="badge bg-primary" style="font-size:9px;">AC vinculada</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $tipoBadge ?>" style="font-size:10px;">
                        <?= str_replace('_',' ', $h['tipo'] ?? '—') ?>
                    </span>
                </td>
                <td style="font-size:11px;"><?= e(truncar($h['programa_descripcion'] ?? '—', 35)) ?></td>
                <td style="font-size:11px;"><?= e($h['responsable_ac'] ?? '—') ?></td>
                <td class="text-center">
                    <span class="badge <?= $badge ?>" style="font-size:10px;">
                        <?= str_replace('_',' ', $h['estado'] ?? '—') ?>
                    </span>
                    <?php if ($vencido): ?>
                    <br><span class="badge bg-danger" style="font-size:9px;">VENCIDO</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px;<?= $vencido ? 'color:#dc2626;font-weight:600;' : '' ?>">
                    <?= !empty($h['fecha_limite'])
                        ? date('d/m/Y', strtotime($h['fecha_limite']))
                        : '—' ?>
                </td>
                <td class="text-center">
                    <a href="<?= e(APP_URL) ?>/hallazgos/<?= (int)$h['id'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Ver detalle">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($hallazgos)): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-clipboard-check fs-3 d-block mb-2"></i>
                No hay hallazgos registrados.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
function filtrarTabla(val) {
    if ($.fn.DataTable.isDataTable('#tablaHallazgos')) {
        $('#tablaHallazgos').DataTable().column(5).search(val).draw();
    }
}
</script>
