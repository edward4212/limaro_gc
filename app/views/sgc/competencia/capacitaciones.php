<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/competencia">Competencia y Capacitación</a></li>
            <li class="breadcrumb-item active">Capacitaciones</li>
        </ol></nav>
    </div>
    <?php if (Auth::puede('competencia_capacitaciones','crear')): ?>
    <a href="<?= e(APP_URL) ?>/competencia/capacitaciones/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Registrar Capacitación
    </a>
    <?php endif; ?>
</div>

<?php
$porResultado = array_column($resumen, 'total', 'resultado');
$totalCap = array_sum(array_column($resumen, 'total'));
$kpis = [
    ['label'=>'Aprobadas',   'valor'=>$porResultado['APROBADO']    ?? 0, 'icono'=>'bi-check-circle-fill', 'tipo'=>'kpi-green', 'filtro'=>'APROBADO'],
    ['label'=>'En Curso',    'valor'=>$porResultado['EN_CURSO']    ?? 0, 'icono'=>'bi-hourglass-split',    'tipo'=>'kpi-blue',  'filtro'=>'EN_CURSO'],
    ['label'=>'No Aprobadas','valor'=>$porResultado['NO_APROBADO'] ?? 0, 'icono'=>'bi-x-circle-fill',      'tipo'=>'kpi-rose',  'filtro'=>'NO_APROBADO'],
];
$kpiTotal = ['label'=>'Total Capacitaciones', 'valor'=>$totalCap];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-0 small">Tipo</label>
                <select class="form-select form-select-sm" name="tipo" id="selTipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="INTERNA" <?= ($filtros['tipo'] ?? '') === 'INTERNA' ? 'selected' : '' ?>>Interna (Moodle)</option>
                    <option value="EXTERNA" <?= ($filtros['tipo'] ?? '') === 'EXTERNA' ? 'selected' : '' ?>>Externa</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small">Resultado</label>
                <select class="form-select form-select-sm" name="resultado" id="selResultado" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach (['APROBADO','EN_CURSO','NO_APROBADO'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($filtros['resultado'] ?? '') === $r ? 'selected' : '' ?>><?= str_replace('_',' ',$r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <a href="<?= e(APP_URL) ?>/competencia/capacitaciones" class="btn btn-outline-secondary btn-sm w-100">
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
                <tr><th>Empleado</th><th>Cargo</th><th>Curso</th><th>Tipo</th><th>Fecha</th><th>Resultado</th><th>Certificado</th></tr>
            </thead>
            <tbody>
                <?php $colorResultado = ['APROBADO'=>'success','EN_CURSO'=>'primary','NO_APROBADO'=>'danger']; ?>
                <?php foreach ($items as $c): ?>
                <tr>
                    <td><?= e($c['empleado_nombre']) ?></td>
                    <td><?= e($c['cargo_nombre'] ?? '—') ?></td>
                    <td><?= e($c['nombre_curso']) ?></td>
                    <td><span class="badge bg-light text-dark"><?= $c['tipo'] === 'INTERNA' ? '🎓 Interna' : '🌐 Externa' ?></span></td>
                    <td><?= fechaEs($c['fecha_finalizacion']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorResultado[$c['resultado']] ?? 'secondary' ?>"><?= str_replace('_',' ',$c['resultado']) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($c['certificado_archivo'])): ?>
                        <i class="bi bi-file-earmark-pdf text-danger" title="Certificado adjunto"></i>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay capacitaciones registradas con estos filtros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla(valor) {
    document.getElementById('selResultado').value = valor;
    document.getElementById('selResultado').form.submit();
}
</script>
