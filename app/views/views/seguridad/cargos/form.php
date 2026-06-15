<?php
$isEdit = !is_null($item);
$action = $isEdit ? e(APP_URL) . '/cargos/editar/' . $item['id_cargo'] : e(APP_URL) . '/cargos/crear';
?>
<div class="page-header">
    <div><h2><i class="bi bi-person-badge me-2"></i><?= e($pageTitle) ?></h2></div>
</div>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Nombre del Cargo <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" name="cargo"
                       value="<?= $isEdit ? e($item['cargo']) : old('cargo') ?>" maxlength="150" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" name="descripcion" rows="3"><?= $isEdit ? e($item['descripcion'] ?? '') : '' ?></textarea>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Manual de Funciones (PDF)</label>
                    <input type="file" class="form-control" name="manual_funciones" accept=".pdf">
                    <div class="form-text">Máx. 20 MB.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="ACTIVO" <?= (!$isEdit || $item['estado'] === 'ACTIVO') ? 'selected' : '' ?>>Activo</option>
                        <option value="INACTIVO" <?= ($isEdit && $item['estado'] === 'INACTIVO') ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                <a href="<?= e(APP_URL) ?>/cargos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div></div>
