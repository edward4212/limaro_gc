<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL) . '/procesos/editar/' . $item['id_proceso']
    : e(APP_URL) . '/procesos/crear';
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-diagram-2 me-2"></i><?= e($pageTitle) ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/procesos">Procesos</a></li>
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
                <label class="form-label">Macroproceso <span class="text-danger">*</span></label>
                <select class="form-select" id="id_macroproceso" name="id_macroproceso" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($macroprocesos as $mp): ?>
                    <option value="<?= e($mp['id_macroproceso']) ?>"
                        <?= (($isEdit && $item['id_macroproceso'] == $mp['id_macroproceso']) || old('id_macroproceso') == $mp['id_macroproceso']) ? 'selected' : '' ?>>
                        <?= e($mp['macroproceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Nombre del Proceso <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" name="proceso"
                       value="<?= $isEdit ? e($item['proceso']) : old('proceso') ?>"
                       maxlength="200" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Sigla <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="sigla_proceso"
                           value="<?= $isEdit ? e($item['sigla_proceso']) : old('sigla_proceso') ?>"
                           maxlength="2" required placeholder="Ej: GC"
                           style="text-transform:uppercase;max-width:100px;">
                    <div class="form-text">Máximo 2 caracteres. Ej: <strong>GF</strong>, <strong>TH</strong>, <strong>GC</strong></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="ACTIVO" <?= (!$isEdit || $item['estado'] === 'ACTIVO') ? 'selected' : '' ?>>Activo</option>
                        <option value="INACTIVO" <?= ($isEdit && $item['estado'] === 'INACTIVO') ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Objetivo</label>
                <textarea class="form-control" name="objetivo" rows="3"><?= $isEdit ? e($item['objetivo']) : old('objetivo') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                <a href="<?= e(APP_URL) ?>/procesos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
