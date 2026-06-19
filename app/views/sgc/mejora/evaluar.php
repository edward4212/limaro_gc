<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/mejora">Oportunidades de Mejora</a></li>
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/mejora/ver/<?= (int)$item['id'] ?>"><?= e($item['codigo']) ?></a></li>
            <li class="breadcrumb-item active">Evaluar</li>
        </ol></nav>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Propuesta a Evaluar</div>
    <div class="card-body">
        <h5><?= e($item['titulo']) ?></h5>
        <p class="text-muted small mb-2">Propuesta por <?= e($item['propone_nombre'] ?? '—') ?> el <?= fechaEs($item['fecha_registro']) ?></p>
        <p class="mb-3"><?= nl2br(e($item['descripcion'])) ?></p>
        <?php if (!empty($item['beneficio_esperado'])): ?>
        <div class="alert alert-light border mb-0">
            <strong>Beneficio esperado:</strong><br><?= nl2br(e($item['beneficio_esperado'])) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<form action="<?= e(APP_URL) ?>/mejora/<?= (int)$item['id'] ?>/evaluar" method="POST">
    <?= csrfField() ?>
<div class="card mb-4">
    <div class="card-header">Decisión</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Comentario de evaluación</label>
            <textarea class="form-control" name="comentario_evaluacion" rows="3"
                placeholder="Justificación de la decisión (opcional)"></textarea>
        </div>
        <input type="hidden" name="decision" id="inputDecision">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-outline-danger" onclick="enviarDecision('RECHAZADA')">
                <i class="bi bi-x-circle me-1"></i>Rechazar
            </button>
            <button type="button" class="btn btn-lim-primary" onclick="enviarDecision('APROBADA')">
                <i class="bi bi-check-circle me-1"></i>Aprobar
            </button>
        </div>
    </div>
</div>
</form>

<script>
function enviarDecision(valor) {
    document.getElementById('inputDecision').value = valor;
    document.getElementById('inputDecision').closest('form').submit();
}
</script>
