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
<?php $bloqueada = ($soloLectura ?? false) || ($isEdit && in_array($item['estado'] ?? '', ['CERRADA','CANCELADA'])); ?>
<?php if ($bloqueada): ?>
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-lock-fill fs-5"></i>
    <div><?php if ($soloLectura ?? false): ?>Modo <strong>solo lectura</strong>.<?php else: ?>La AC está <strong><?= e($item['estado']) ?></strong> — modo solo lectura.<?php endif; ?></div>
</div>
<?php endif; ?>
<fieldset <?= $bloqueada ? 'disabled' : '' ?>>
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
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Cláusula ISO / Criterio</label>
                <input type="text" class="form-control" name="clausula_iso"
                       placeholder="Ej: 7.5.3, 8.2.1"
                       value="<?= $isEdit ? e($item['clausula_iso'] ?? '') : '' ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Proceso Asociado</label>
                <select class="form-select" name="id_proceso">
                    <option value="">— Sin proceso —</option>
                    <?php foreach ($procesos ?? [] as $p): ?>
                    <option value="<?= (int)$p['id_proceso'] ?>"
                        <?= ($isEdit && (int)($item['id_proceso'] ?? 0) === (int)$p['id_proceso']) ? 'selected' : '' ?>>
                        <?= e($p['proceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                <select class="form-select" name="id_responsable"
                        id="selResponsable" onchange="actualizarResponsable()" required>
                    <option value="">-- Seleccione responsable --</option>
                    <?php foreach ($empleados ?? [] as $u): ?>
                    <option value="<?= (int)$u['id_empleado'] ?>"
                            data-nombre="<?= e($u['nombre_completo']) ?>"
                            <?= ($isEdit && (int)($item['id_responsable'] ?? 0) === (int)$u['id_empleado']) ? 'selected' : '' ?>>
                        <?= e($u['nombre_completo']) ?><?= !empty($u['cargo']) ? ' — '.e($u['cargo']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="responsable" id="hidResponsable"
                       value="<?= $isEdit ? e($item['responsable'] ?? '') : '' ?>">
                <script>
                function actualizarResponsable() {
                    var sel = document.getElementById('selResponsable');
                    var opt = sel.options[sel.selectedIndex];
                    document.getElementById('hidResponsable').value = opt ? (opt.dataset.nombre || '') : '';
                }
                document.addEventListener('DOMContentLoaded', actualizarResponsable);
                </script>
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
    <?php if (!$bloqueada): ?><button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button><?php endif; ?>
    <a href="<?= e(APP_URL) ?>/acciones-correctivas" class="btn btn-secondary">Cancelar</a>
</div>
</fieldset>
</form>
