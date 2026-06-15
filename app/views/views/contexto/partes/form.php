<?php $isEdit = $item !== null; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-people me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/contexto/partes-interesadas">§4.2 Partes Interesadas</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nueva' ?></li>
        </ol></nav>
    </div>
</div>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-body">
    <form action="<?= e(APP_URL) ?>/contexto/partes-interesadas/<?= $isEdit ? 'editar/'.$item['id'] : 'crear' ?>"
          method="POST">
        <?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre" required
                       value="<?= e($item['nombre'] ?? '') ?>"
                       placeholder="Ej: Empleados, Clientes, Proveedores, Entes reguladores...">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Tipo</label>
                <select class="form-select" name="tipo">
                    <option value="INTERNA" <?= ($item['tipo'] ?? 'EXTERNA') === 'INTERNA' ? 'selected' : '' ?>>🏢 Interna</option>
                    <option value="EXTERNA" <?= ($item['tipo'] ?? 'EXTERNA') === 'EXTERNA' ? 'selected' : '' ?>>🌐 Externa</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Necesidades</label>
                <textarea class="form-control" name="necesidades" rows="2"
                          placeholder="¿Qué necesita esta parte interesada de la organización?"><?= e($item['necesidades'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Expectativas</label>
                <textarea class="form-control" name="expectativas" rows="2"
                          placeholder="¿Qué espera esta parte interesada?"><?= e($item['expectativas'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nivel de Influencia</label>
                <select class="form-select" name="nivel_influencia">
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($item['nivel_influencia'] ?? 'MEDIO') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Capacidad de influir en las decisiones de la organización.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nivel de Interés</label>
                <select class="form-select" name="nivel_interes">
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($item['nivel_interes'] ?? 'MEDIO') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Grado de interés en las actividades de la organización.</div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-lim-primary">
                <i class="bi bi-save me-1"></i><?= $isEdit ? 'Guardar Cambios' : 'Registrar' ?>
            </button>
            <a href="<?= e(APP_URL) ?>/contexto/partes-interesadas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
</div></div>
