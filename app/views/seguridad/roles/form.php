<?php
$isEdit = !is_null($item);
$action = $isEdit ? e(APP_URL) . '/roles/editar/' . $item['id_rol'] : e(APP_URL) . '/roles/crear';
?>
<div class="page-header">
    <div><h2><i class="bi bi-shield-check me-2"></i><?= e($pageTitle) ?></h2></div>
</div>
<div class="row justify-content-center"><div class="col-lg-5">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Nombre del Rol <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" name="rol"
                       value="<?= $isEdit ? e($item['rol']) : old('rol') ?>" maxlength="100" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="ACTIVO" <?= (!$isEdit || $item['estado'] === 'ACTIVO') ? 'selected' : '' ?>>Activo</option>
                    <option value="INACTIVO" <?= ($isEdit && $item['estado'] === 'INACTIVO') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                <a href="<?= e(APP_URL) ?>/roles" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div></div>
