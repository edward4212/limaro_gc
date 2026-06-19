<?php
$colorNivel = ['ALTO'=>'danger','MEDIO'=>'warning','BAJO'=>'success'];
$colorEstado = ['IDENTIFICADO'=>'secondary','EN_TRATAMIENTO'=>'primary','CONTROLADO'=>'info','CERRADO'=>'success'];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-shield-exclamation me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/riesgos">Gestión de Riesgos</a></li>
            <li class="breadcrumb-item active"><?= e($item['codigo']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <?php
        $cerrado = $item['estado'] === 'CERRADO';
        $puedeReabrir = $cerrado && Auth::hasRole([1, 2]);
        ?>
        <?php if (Auth::puede('gestion_riesgos','editar') && (!$cerrado || $puedeReabrir)): ?>
        <a href="<?= e(APP_URL) ?>/riesgos/editar/<?= (int)$item['id'] ?>" class="btn btn-<?= $cerrado ? 'warning' : 'secondary' ?> btn-sm">
            <i class="bi bi-<?= $cerrado ? 'unlock' : 'pencil' ?> me-1"></i><?= $cerrado ? 'Reabrir' : 'Editar' ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
<div class="col-lg-7">
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Identificación</span>
        <span class="badge bg-<?= $colorEstado[$item['estado']] ?? 'secondary' ?>">
            <?= str_replace('_',' ',$item['estado']) ?>
        </span>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:160px;">Proceso</td><td><?= e($item['proceso_nombre']) ?></td></tr>
            <tr><td class="text-muted">Descripción</td><td><?= nl2br(e($item['descripcion'])) ?></td></tr>
            <?php if (!empty($item['causa'])): ?>
            <tr><td class="text-muted">Causa</td><td><?= nl2br(e($item['causa'])) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($item['consecuencia'])): ?>
            <tr><td class="text-muted">Consecuencia</td><td><?= nl2br(e($item['consecuencia'])) ?></td></tr>
            <?php endif; ?>
            <tr><td class="text-muted">Identificado por</td><td><?= e($item['usuario_registro_nombre'] ?? '—') ?></td></tr>
            <tr><td class="text-muted">Fecha</td><td><?= fechaEs($item['fecha_identificacion']) ?></td></tr>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Tratamiento</div>
    <div class="card-body">
        <?php if (!empty($item['tratamiento'])): ?>
        <p><?= nl2br(e($item['tratamiento'])) ?></p>
        <?php else: ?>
        <p class="text-muted mb-3">Sin tratamiento definido todavía.</p>
        <?php endif; ?>
        <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:160px;">Responsable</td><td><?= e($item['responsable_nombre'] ?? '— Sin asignar —') ?></td></tr>
            <tr><td class="text-muted">F. Planificada</td><td><?= $item['fecha_tratamiento_planificada'] ? fechaEs($item['fecha_tratamiento_planificada']) : '—' ?></td></tr>
            <tr><td class="text-muted">F. Real</td><td><?= $item['fecha_tratamiento_real'] ? fechaEs($item['fecha_tratamiento_real']) : '—' ?></td></tr>
        </table>
    </div>
</div>
</div>

<div class="col-lg-5">
<div class="card mb-4">
    <div class="card-header">Evaluación de Riesgo</div>
    <div class="card-body">
        <div class="mb-3">
            <div class="text-muted small mb-1">Riesgo Inherente <span class="text-muted">(antes de controles)</span></div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-<?= $colorNivel[$item['nivel_riesgo_inherente']] ?? 'secondary' ?>" style="font-size:14px;">
                    <?= e($item['nivel_riesgo_inherente']) ?>
                </span>
                <small class="text-muted">Prob: <?= e($item['probabilidad_inherente']) ?> · Impacto: <?= e($item['impacto_inherente']) ?></small>
            </div>
        </div>
        <hr>
        <div>
            <div class="text-muted small mb-1">Riesgo Residual <span class="text-muted">(después del tratamiento)</span></div>
            <?php if (!empty($item['nivel_riesgo_residual'])): ?>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-<?= $colorNivel[$item['nivel_riesgo_residual']] ?? 'secondary' ?>" style="font-size:14px;">
                    <?= e($item['nivel_riesgo_residual']) ?>
                </span>
                <small class="text-muted">Prob: <?= e($item['probabilidad_residual']) ?> · Impacto: <?= e($item['impacto_residual']) ?></small>
            </div>
            <?php else: ?>
            <span class="text-muted small">Aún no evaluado.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Acción Correctiva Vinculada</div>
    <div class="card-body">
        <?php if ($acVinculada): ?>
        <p class="mb-2">Este riesgo tiene una AC vinculada:</p>
        <a href="<?= e(APP_URL) ?>/acciones-correctivas/editar/<?= (int)$acVinculada['id'] ?>"
           class="badge bg-success text-decoration-none" style="font-size:13px;">
            <?= e($acVinculada['codigo']) ?> — <?= str_replace('_',' ',$acVinculada['estado']) ?>
        </a>
        <?php elseif (Auth::puede('gestion_riesgos','crear')): ?>
        <p class="text-muted mb-3">Este riesgo no tiene ninguna Acción Correctiva vinculada todavía.</p>
        <form method="POST" action="<?= e(APP_URL) ?>/riesgos/<?= (int)$item['id'] ?>/generar-ac">
            <?= csrfField() ?>
            <button type="button" class="btn btn-lim-primary btn-sm"
                    onclick="swalConfirmForm(event, 'Se creará una Acción Correctiva para tratar este riesgo.', 'Generar AC')">
                <i class="bi bi-plus-circle me-1"></i>Generar Acción Correctiva
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
