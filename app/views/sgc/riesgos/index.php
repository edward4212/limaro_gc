<div class="page-header">
    <div>
        <h2><i class="bi bi-shield-exclamation me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 6.1</small>
    </div>
    <?php if (Auth::puede('gestion_riesgos','crear')): ?>
    <a href="<?= e(APP_URL) ?>/riesgos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Identificar Riesgo
    </a>
    <?php endif; ?>
</div>

<!-- KPIs por nivel de riesgo inherente -->
<?php
$porNivel = array_column($resumenNivel, 'total', 'nivel');
$totalRiesgos = array_sum(array_column($resumenNivel, 'total'));
$kpis = [
    ['label'=>'Riesgo Alto',  'valor'=>$porNivel['ALTO']  ?? 0, 'icono'=>'bi-exclamation-triangle-fill', 'tipo'=>'kpi-rose',  'filtro'=>'ALTO'],
    ['label'=>'Riesgo Medio', 'valor'=>$porNivel['MEDIO'] ?? 0, 'icono'=>'bi-dash-circle-fill',           'tipo'=>'kpi-amber', 'filtro'=>'MEDIO'],
    ['label'=>'Riesgo Bajo',  'valor'=>$porNivel['BAJO']  ?? 0, 'icono'=>'bi-check-circle-fill',          'tipo'=>'kpi-green', 'filtro'=>'BAJO'],
];
$kpiTotal = ['label'=>'Total Riesgos', 'valor'=>$totalRiesgos];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<!-- Filtros -->
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
                <select class="form-select form-select-sm" name="estado" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <?php foreach (['IDENTIFICADO','EN_TRATAMIENTO','CONTROLADO','CERRADO'] as $est): ?>
                    <option value="<?= $est ?>" <?= ($filtros['estado'] ?? '') === $est ? 'selected' : '' ?>>
                        <?= str_replace('_',' ',$est) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small">Nivel de riesgo</label>
                <select class="form-select form-select-sm" name="nivel" id="selNivel" onchange="this.form.submit()">
                    <option value="">Todos los niveles</option>
                    <?php foreach (['ALTO','MEDIO','BAJO'] as $n): ?>
                    <option value="<?= $n ?>" <?= ($filtros['nivel_riesgo_inherente'] ?? '') === $n ? 'selected' : '' ?>>
                        <?= $n ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <a href="<?= e(APP_URL) ?>/riesgos" class="btn btn-outline-secondary btn-sm w-100">
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
                    <th>Código</th><th>Proceso</th><th>Descripción</th>
                    <th>Probabilidad</th><th>Impacto</th><th>Nivel Inherente</th>
                    <th>Responsable</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $colorNivel = ['ALTO'=>'danger','MEDIO'=>'warning','BAJO'=>'success'];
                $colorEstado = ['IDENTIFICADO'=>'secondary','EN_TRATAMIENTO'=>'primary','CONTROLADO'=>'info','CERRADO'=>'success'];
                ?>
                <?php foreach ($items as $r): ?>
                <tr>
                    <td><strong><?= e($r['codigo']) ?></strong></td>
                    <td><?= e($r['proceso_nombre']) ?></td>
                    <td style="max-width:280px;white-space:normal;"><?= e(mb_strimwidth($r['descripcion'], 0, 120, '…')) ?></td>
                    <td class="text-center"><?= e($r['probabilidad_inherente']) ?></td>
                    <td class="text-center"><?= e($r['impacto_inherente']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorNivel[$r['nivel_riesgo_inherente']] ?? 'secondary' ?>">
                            <?= e($r['nivel_riesgo_inherente']) ?>
                        </span>
                    </td>
                    <td><?= e($r['responsable_nombre'] ?? '— Sin asignar —') ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorEstado[$r['estado']] ?? 'secondary' ?>">
                            <?= str_replace('_',' ',$r['estado']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="<?= e(APP_URL) ?>/riesgos/ver/<?= (int)$r['id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-1" title="Ver">
                            <i class="bi bi-eye" style="font-size:11px;"></i>
                        </a>
                        <?php if (Auth::puede('gestion_riesgos','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/riesgos/editar/<?= (int)$r['id'] ?>" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Editar">
                            <i class="bi bi-pencil" style="font-size:11px;"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No hay riesgos identificados con estos filtros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla(nivel) {
    document.getElementById('selNivel').value = nivel;
    document.getElementById('selNivel').form.submit();
}
</script>
