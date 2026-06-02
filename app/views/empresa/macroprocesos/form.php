<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL) . '/macroprocesos/editar/' . $item['id_macroproceso']
    : e(APP_URL) . '/macroprocesos/crear';
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-diagram-3 me-2"></i><?= e($pageTitle) ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/macroprocesos">Macroprocesos</a></li>
                <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST" novalidate>
            <?= csrfField() ?>

            <div class="mb-3">
                <label for="macroproceso" class="form-label">Nombre del Macroproceso <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" id="macroproceso" name="macroproceso"
                       value="<?= $isEdit ? e($item['macroproceso']) : old('macroproceso') ?>"
                       maxlength="200" required>
            </div>

            <div class="mb-3">
                <label for="objetivo" class="form-label">Objetivo</label>
                <textarea class="form-control" id="objetivo" name="objetivo" rows="3"
                          maxlength="500"><?= $isEdit ? e($item['objetivo']) : old('objetivo') ?></textarea>
            </div>

            <div class="mb-4">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="ACTIVO" <?= (!$isEdit || $item['estado'] === 'ACTIVO') ? 'selected' : '' ?>>Activo</option>
                    <option value="INACTIVO" <?= ($isEdit && $item['estado'] === 'INACTIVO') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
                <a href="<?= e(APP_URL) ?>/macroprocesos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
