<?php
$colorEstado = [
    'PROPUESTA'=>'secondary','APROBADA'=>'success','RECHAZADA'=>'danger',
    'EN_IMPLEMENTACION'=>'primary','IMPLEMENTADA'=>'info','CERRADA'=>'dark',
];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-lightbulb me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/mejora">Oportunidades de Mejora</a></li>
            <li class="breadcrumb-item active"><?= e($item['codigo']) ?></li>
        </ol></nav>
    </div>
    <?php if ($item['estado'] === 'PROPUESTA' && Auth::hasRole([1,2])): ?>
    <a href="<?= e(APP_URL) ?>/mejora/<?= (int)$item['id'] ?>/evaluar" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-clipboard-check me-1"></i>Evaluar
    </a>
    <?php endif; ?>
</div>

<div class="row g-4">
<div class="col-lg-7">
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Propuesta</span>
        <span class="badge bg-<?= $colorEstado[$item['estado']] ?? 'secondary' ?>">
            <?= str_replace('_',' ',$item['estado']) ?>
        </span>
    </div>
    <div class="card-body">
        <h5><?= e($item['titulo']) ?></h5>
        <table class="table table-sm table-borderless mb-3">
            <tr><td class="text-muted" style="width:160px;">Proceso</td><td><?= e($item['proceso_nombre'] ?? '— No aplica a un proceso específico —') ?></td></tr>
            <tr><td class="text-muted">Propuesta por</td><td><?= e($item['propone_nombre'] ?? '—') ?></td></tr>
            <tr><td class="text-muted">Fecha</td><td><?= fechaEs($item['fecha_registro']) ?></td></tr>
        </table>
        <p class="mb-3"><?= nl2br(e($item['descripcion'])) ?></p>
        <?php if (!empty($item['beneficio_esperado'])): ?>
        <div class="alert alert-light border mb-0">
            <strong>Beneficio esperado:</strong><br><?= nl2br(e($item['beneficio_esperado'])) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($item['id_usuario_evalua'])): ?>
<div class="card mb-4">
    <div class="card-header">Evaluación</div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-2">
            <tr><td class="text-muted" style="width:160px;">Evaluado por</td><td><?= e($item['evalua_nombre'] ?? '—') ?></td></tr>
            <tr><td class="text-muted">Fecha</td><td><?= fechaEs($item['fecha_evaluacion']) ?></td></tr>
        </table>
        <?php if (!empty($item['comentario_evaluacion'])): ?>
        <p class="mb-0"><?= nl2br(e($item['comentario_evaluacion'])) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>

<div class="col-lg-5">
<div class="card mb-4">
    <div class="card-header">Implementación</div>
    <div class="card-body">
        <?php if ($item['id_accion_correctiva']): ?>
        <p class="mb-2">Esta mejora tiene una Acción Correctiva vinculada para su implementación:</p>
        <a href="<?= e(APP_URL) ?>/acciones-correctivas/editar/<?= (int)$item['id_accion_correctiva'] ?>"
           class="badge bg-primary text-decoration-none" style="font-size:13px;">
            <?= e($item['ac_codigo']) ?> — <?= str_replace('_',' ',$item['ac_estado']) ?>
        </a>
        <?php elseif ($item['estado'] === 'APROBADA' && Auth::hasRole([1,2])): ?>
        <p class="text-muted mb-3">Esta propuesta fue aprobada. Genera una Acción Correctiva para darle seguimiento formal a su implementación.</p>
        <form method="POST" action="<?= e(APP_URL) ?>/mejora/<?= (int)$item['id'] ?>/generar-ac">
            <?= csrfField() ?>
            <button type="button" class="btn btn-lim-primary btn-sm"
                    onclick="swalConfirmForm(event, 'Se creará una Acción Correctiva para implementar esta mejora.', 'Generar AC')">
                <i class="bi bi-plus-circle me-1"></i>Generar Acción Correctiva
            </button>
        </form>
        <?php elseif ($item['estado'] === 'RECHAZADA'): ?>
        <p class="text-muted small mb-0">Esta propuesta fue rechazada, no se implementará.</p>
        <?php else: ?>
        <p class="text-muted small mb-0">Esta propuesta está pendiente de evaluación.</p>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
