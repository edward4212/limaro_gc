<div class="page-header">
    <div>
        <h2><i class="bi bi-lightbulb me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 10.3</small>
    </div>
    <a href="<?= e(APP_URL) ?>/mejora/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Proponer Mejora
    </a>
</div>

<?php
$porEstado = array_column($resumenEstado, 'total', 'estado');
$totalMejoras = array_sum(array_column($resumenEstado, 'total'));
$kpis = [
    ['label'=>'Propuestas',       'valor'=>$porEstado['PROPUESTA']         ?? 0, 'icono'=>'bi-hourglass-split',     'tipo'=>'kpi-blue',  'filtro'=>'PROPUESTA'],
    ['label'=>'Aprobadas',        'valor'=>$porEstado['APROBADA']          ?? 0, 'icono'=>'bi-check-circle-fill',   'tipo'=>'kpi-green', 'filtro'=>'APROBADA'],
    ['label'=>'En Implementación','valor'=>$porEstado['EN_IMPLEMENTACION'] ?? 0, 'icono'=>'bi-gear-fill',           'tipo'=>'kpi-amber', 'filtro'=>'EN_IMPLEMENTACION'],
    ['label'=>'Rechazadas',       'valor'=>$porEstado['RECHAZADA']         ?? 0, 'icono'=>'bi-x-circle-fill',       'tipo'=>'kpi-rose',  'filtro'=>'RECHAZADA'],
];
$kpiTotal = ['label'=>'Total Propuestas', 'valor'=>$totalMejoras];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-0 small">Proceso</label>
                <select class="form-select form-select-sm" name="id_proceso" onchange="this.form.submit()">
                    <option value="">Todos los procesos</option>
                    <?php foreach ($procesos as $p): ?>
                    <option value="<?= (int)$p['id_proceso'] ?>" <?= ($filtros['id_proceso'] ?? '') == $p['id_proceso'] ? 'selected' : '' ?>>
                        <?= e($p['proceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small">Estado</label>
                <select class="form-select form-select-sm" name="estado" id="selEstado" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <?php foreach (['PROPUESTA','APROBADA','RECHAZADA','EN_IMPLEMENTACION','IMPLEMENTADA','CERRADA'] as $est): ?>
                    <option value="<?= $est ?>" <?= ($filtros['estado'] ?? '') === $est ? 'selected' : '' ?>>
                        <?= str_replace('_',' ',$est) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 form-check d-flex align-items-center mt-4">
                <input class="form-check-input" type="checkbox" name="solo_mias" id="soloMias" value="1"
                       <?= !empty($filtros['solo_mias']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label ms-2" for="soloMias">Solo mis propuestas</label>
            </div>
            <div class="col-md-2">
                <a href="<?= e(APP_URL) ?>/mejora" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th><th>Título</th><th>Proceso</th>
                    <th>Propone</th><th>Estado</th><th>Fecha</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $colorEstado = [
                    'PROPUESTA'=>'secondary','APROBADA'=>'success','RECHAZADA'=>'danger',
                    'EN_IMPLEMENTACION'=>'primary','IMPLEMENTADA'=>'info','CERRADA'=>'dark',
                ];
                ?>
                <?php foreach ($items as $m): ?>
                <tr>
                    <td><strong><?= e($m['codigo']) ?></strong></td>
                    <td style="max-width:280px;white-space:normal;"><?= e($m['titulo']) ?></td>
                    <td><?= e($m['proceso_nombre'] ?? '—') ?></td>
                    <td><?= e($m['propone_nombre'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorEstado[$m['estado']] ?? 'secondary' ?>">
                            <?= str_replace('_',' ',$m['estado']) ?>
                        </span>
                    </td>
                    <td><?= fechaEs($m['fecha_registro']) ?></td>
                    <td class="text-center">
                        <a href="<?= e(APP_URL) ?>/mejora/ver/<?= (int)$m['id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-1" title="Ver">
                            <i class="bi bi-eye" style="font-size:11px;"></i>
                        </a>
                        <?php if ($m['estado'] === 'PROPUESTA' && Auth::hasRole([1,2])): ?>
                        <a href="<?= e(APP_URL) ?>/mejora/<?= (int)$m['id'] ?>/evaluar" class="btn btn-xs btn-outline-success py-0 px-1" title="Evaluar">
                            <i class="bi bi-clipboard-check" style="font-size:11px;"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay oportunidades de mejora registradas con estos filtros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla(estado) {
    document.getElementById('selEstado').value = estado;
    document.getElementById('selEstado').form.submit();
}
</script>
