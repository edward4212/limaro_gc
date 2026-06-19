<div class="page-header">
    <div>
        <h2><i class="bi bi-lightbulb me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/mejora">Oportunidades de Mejora</a></li>
            <li class="breadcrumb-item active">Proponer</li>
        </ol></nav>
    </div>
</div>

<div class="alert alert-light border d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-info-circle fs-5 text-primary"></i>
    <div>¿Tienes una idea para mejorar algo en tu proceso, aunque no haya ningún problema todavía?
        Esta es la forma de proponerla. El Coordinador de Calidad la revisará y decidirá si se implementa.</div>
</div>

<form action="<?= e(APP_URL) ?>/mejora/crear" method="POST">
    <?= csrfField() ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" class="form-control bg-light" value="<?= e($codigo) ?>" readonly>
            </div>
            <div class="col-md-9">
                <label class="form-label">Proceso relacionado <small class="text-muted">(opcional)</small></label>
                <select class="form-select" name="id_proceso">
                    <option value="">— No aplica a un proceso específico —</option>
                    <?php foreach ($procesos as $p): ?>
                    <option value="<?= (int)$p['id_proceso'] ?>"><?= e($p['proceso']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Título de la propuesta <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="titulo" maxlength="255" required
                   placeholder="Ej: Automatizar el envío de recordatorios de pago">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción <span class="text-danger">*</span></label>
            <textarea class="form-control" name="descripcion" rows="4" required
                placeholder="¿Qué propones mejorar y cómo lo harías?"></textarea>
        </div>
        <div class="mb-0">
            <label class="form-label">Beneficio esperado</label>
            <textarea class="form-control" name="beneficio_esperado" rows="3"
                placeholder="¿Qué se lograría con esto? (tiempo, calidad, satisfacción del asociado, costo, etc.)"></textarea>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/mejora" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-send me-1"></i>Enviar Propuesta</button>
</div>
</form>
