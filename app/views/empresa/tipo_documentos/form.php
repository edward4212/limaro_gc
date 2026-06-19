<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL) . '/tipos-documento/editar/' . $item['id_tipo_documento']
    : e(APP_URL) . '/tipos-documento/crear';
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-file-earmark-text me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/tipos-documento">Tipos de Documento</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="tipo_documento"
                       value="<?= $isEdit ? e($item['tipo_documento']) : old('tipo_documento') ?>"
                       maxlength="100" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Sigla <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sigla_tipo_documento"
                           value="<?= $isEdit ? e($item['sigla_tipo_documento']) : old('sigla_tipo_documento') ?>"
                           maxlength="2" required placeholder="Ej: PR"
                           style="max-width:100px;text-transform:uppercase;">
                    <div class="form-text">Máximo 2 caracteres. Ej: <strong>PR</strong>, <strong>MN</strong>, <strong>PL</strong></div>
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
                <a href="<?= e(APP_URL) ?>/tipos-documento" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
