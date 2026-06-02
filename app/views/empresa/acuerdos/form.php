<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL) . '/acuerdos/editar/' . $item['id_acuerdo']
    : e(APP_URL) . '/acuerdos/crear';
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/acuerdos">Acuerdos</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Año <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="año_acuerdo"
                           value="<?= $isEdit ? e($item['año_acuerdo']) : old('año_acuerdo', date('Y')) ?>"
                           min="2000" max="2100" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Número <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="numero_acuerdo"
                           value="<?= $isEdit ? e($item['numero_acuerdo']) : old('numero_acuerdo') ?>"
                           maxlength="20" required placeholder="Ej: 001">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipo de Documento</label>
                    <?php if (!empty($tipoAcuerdo)): ?>
                        <input type="hidden" name="id_tipo_documento" value="<?= e($tipoAcuerdo['id_tipo_documento']) ?>">
                        <input type="text" class="form-control bg-light fw-bold" value="<?= e($tipoAcuerdo['tipo_documento']) ?>" readonly tabindex="-1">
                    <?php else: ?>
                        <select class="form-select" name="id_tipo_documento" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($tipos as $t): ?>
                            <option value="<?= e($t['id_tipo_documento']) ?>"
                                <?= ($isEdit && $item['id_tipo_documento'] == $t['id_tipo_documento']) ? 'selected' : '' ?>>
                                <?= e($t['tipo_documento']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre del Acuerdo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre_acuerdo"
                       value="<?= $isEdit ? e($item['nombre_acuerdo']) : old('nombre_acuerdo') ?>"
                       maxlength="300" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Fecha de Aprobación</label>
                    <input type="date" class="form-control" name="fecha_aprobacion"
                           value="<?= $isEdit ? e($item['fecha_aprobacion'] ?? '') : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nº Acta de Aprobación <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="acta_aprobacion"
                           value="<?= $isEdit ? e($item['acta_aprobacion'] ?? '') : old('acta_aprobacion') ?>"
                           min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Archivo PDF <?= $isEdit ? '(reemplazar)' : '' ?></label>
                    <input type="file" class="form-control" name="archivo_pdf" accept=".pdf">
                    <div class="form-text">Máx. 20 MB.</div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                <a href="<?= e(APP_URL) ?>/acuerdos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
