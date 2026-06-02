<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL).'/acciones-correctivas/editar/'.$item['id']
    : e(APP_URL).'/acciones-correctivas/crear';
$origenes = ['AUDITORIA','QUEJA','RECLAMO','INDICADOR','PROCESO','OTRO'];
$estados_ac = ['ABIERTA','EN_TRATAMIENTO','VERIFICACION','CERRADA','CANCELADA'];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-tools me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/acciones-correctivas">Acciones Correctivas</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<form action="<?= $action ?>" method="POST">
    <?= csrfField() ?>
<div class="row g-4">
<div class="col-lg-6">
<div class="card mb-4">
    <div class="card-header">Identificación</div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Código</label>
                <input type="text" class="form-control <?= $isEdit ? 'bg-light' : '' ?>"
                       name="codigo" value="<?= e($codigo) ?>" <?= $isEdit ? 'readonly' : '' ?> required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Origen</label>
                <select class="form-select" name="origen">
                    <?php foreach ($origenes as $o): ?>
                    <option value="<?= $o ?>" <?= ($isEdit && ($item['origen']??'')===$o)?'selected':'' ?>>
                        <?= str_replace('_',' ',$o) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($isEdit): ?>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <?php foreach ($estados_ac as $est): ?>
                    <option value="<?= $est ?>" <?= (($item['estado']??'')===$est)?'selected':'' ?>>
                        <?= str_replace('_',' ',$est) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción de la No Conformidad <span class="text-danger">*</span></label>
            <textarea class="form-control" name="descripcion_nc" rows="3" required><?= $isEdit ? e($item['descripcion_nc']) : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Acción Inmediata (Contención)</label>
            <textarea class="form-control" name="accion_inmediata" rows="2"><?= $isEdit ? e($item['accion_inmediata']??'') : '' ?></textarea>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Responsable</label>
                <input type="text" class="form-control" name="responsable" value="<?= $isEdit ? e($item['responsable']??'') : '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">F. Planificada</label>
                <input type="date" class="form-control" name="fecha_planificada" value="<?= $isEdit ? e($item['fecha_planificada']??'') : '' ?>">
            </div>
            <?php if ($isEdit): ?>
            <div class="col-md-3">
                <label class="form-label">F. Cierre</label>
                <input type="date" class="form-control" name="fecha_cierre" value="<?= e($item['fecha_cierre']??'') ?>">
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<div class="col-lg-6">
<div class="card mb-4">
    <div class="card-header">Análisis y Tratamiento</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Análisis de Causa Raíz</label>
            <div class="form-text mb-1">Usar 5 Porqués, espina de pescado u otra metodología.</div>
            <textarea class="form-control" name="causa_raiz" rows="4"><?= $isEdit ? e($item['causa_raiz']??'') : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Acción Correctiva Planificada</label>
            <textarea class="form-control" name="accion_correctiva" rows="3"><?= $isEdit ? e($item['accion_correctiva']??'') : '' ?></textarea>
        </div>
        <?php if ($isEdit): ?>
        <div class="mb-3">
            <label class="form-label">Evaluación de Eficacia</label>
            <textarea class="form-control" name="eficacia" rows="3"><?= e($item['eficacia']??'') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">¿La acción fue eficaz?</label>
            <select class="form-select" name="eficaz">
                <option value="">— Pendiente evaluación —</option>
                <option value="1" <?= ($item['eficaz']??null)==='1'?'selected':'' ?>>Sí, fue eficaz</option>
                <option value="0" <?= ($item['eficaz']??null)==='0'?'selected':'' ?>>No, requiere nueva acción</option>
            </select>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<div class="d-flex gap-2 mb-5">
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
    <a href="<?= e(APP_URL) ?>/acciones-correctivas" class="btn btn-secondary">Cancelar</a>
</div>
</form>
