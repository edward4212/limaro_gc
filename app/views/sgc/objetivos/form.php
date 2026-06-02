<?php $isEdit = !is_null($item); ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-bullseye me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/objetivos-calidad">Objetivos</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<div class="row g-4">
<div class="col-lg-7">
<div class="card mb-4">
    <div class="card-header">Datos del Objetivo</div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/objetivos-calidad/<?= $isEdit ? 'editar/'.$item['id'] : 'crear' ?>" method="POST">
            <?= csrfField() ?>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control <?= $isEdit ? 'bg-light' : '' ?>"
                           name="codigo" value="<?= e($isEdit ? $item['codigo'] : $codigo) ?>"
                           <?= $isEdit ? 'readonly' : '' ?> required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Proceso Asociado</label>
                    <select class="form-select" name="id_proceso">
                        <option value="">— General —</option>
                        <?php foreach ($procesos as $p): ?>
                        <option value="<?= e($p['id_proceso']) ?>"
                            <?= ($isEdit && ($item['id_proceso']??'') == $p['id_proceso']) ? 'selected' : '' ?>>
                            <?= e($p['proceso']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Frecuencia</label>
                    <select class="form-select" name="frecuencia">
                        <?php foreach (['MENSUAL','TRIMESTRAL','SEMESTRAL','ANUAL'] as $f): ?>
                        <option value="<?= $f ?>" <?= ($isEdit && ($item['frecuencia']??'')===$f)?'selected':'' ?>><?= ucfirst(strtolower($f)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Objetivo <span class="text-danger">*</span></label>
                <textarea class="form-control" name="objetivo" rows="2" required><?= $isEdit ? e($item['objetivo']) : '' ?></textarea>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Meta</label>
                    <input type="text" class="form-control" name="meta" placeholder="Ej: ≥ 90%"
                           value="<?= $isEdit ? e($item['meta']??'') : '' ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Responsable</label>
                    <input type="text" class="form-control" name="responsable"
                           value="<?= $isEdit ? e($item['responsable']??'') : '' ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Indicador</label>
                <input type="text" class="form-control" name="indicador"
                       value="<?= $isEdit ? e($item['indicador']??'') : '' ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Fórmula de Cálculo</label>
                <input type="text" class="form-control" name="formula"
                       placeholder="Ej: (Satisfechos / Total) × 100"
                       value="<?= $isEdit ? e($item['formula']??'') : '' ?>">
            </div>
            <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
            <a href="<?= e(APP_URL) ?>/objetivos-calidad" class="btn btn-secondary ms-2">Cancelar</a>
        </form>
    </div>
</div>
</div>

<?php if ($isEdit): ?>
<div class="col-lg-5">
<!-- Registrar medición -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-graph-up me-1 text-success"></i>Registrar Medición</div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/objetivos-calidad/medicion/<?= (int)$item['id'] ?>" method="POST">
            <?= csrfField() ?>
            <div class="row mb-2">
                <div class="col-md-5">
                    <label class="form-label" style="font-size:12px;">Período <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" name="periodo"
                           placeholder="Ej: 2025-Q1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:12px;">Obtenido</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" name="valor_obtenido">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-size:12px;">Meta numérica</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" name="valor_meta"
                           value="<?= e(preg_replace('/[^0-9.]/','',$item['meta']??'')) ?>">
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label" style="font-size:12px;">Observación</label>
                <textarea class="form-control form-control-sm" name="observacion" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-sm w-100">
                <i class="bi bi-plus me-1"></i>Registrar
            </button>
        </form>
    </div>
</div>

<!-- Historial mediciones -->
<?php if (!empty($mediciones)): ?>
<div class="card">
    <div class="card-header">Historial de Mediciones</div>
    <div class="list-group list-group-flush">
        <?php foreach ($mediciones as $m): ?>
        <div class="list-group-item py-2">
            <div class="d-flex justify-content-between">
                <strong style="font-size:12px;"><?= e($m['periodo']) ?></strong>
                <?php if ($m['cumple'] !== null): ?>
                <span class="badge bg-<?= $m['cumple'] ? 'success' : 'danger' ?>">
                    <?= $m['cumple'] ? '✓ Cumple' : '✗ No cumple' ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if ($m['valor_obtenido'] !== null): ?>
            <div style="font-size:12px;" class="text-muted">
                Obtenido: <strong><?= number_format((float)$m['valor_obtenido'],2) ?></strong>
                <?= $m['valor_meta'] ? ' / Meta: ' . number_format((float)$m['valor_meta'],2) : '' ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
