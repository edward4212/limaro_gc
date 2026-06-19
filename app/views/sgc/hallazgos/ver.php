<?php
$h = $hallazgo;
$badgeH = match($h['estado']) {
    'ABIERTO' => 'bg-danger', 'EN_TRATAMIENTO' => 'bg-warning text-dark', 'CERRADO' => 'bg-success', default => 'bg-secondary'
};
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-exclamation-triangle me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/hallazgos">Hallazgos</a></li>
            <li class="breadcrumb-item active">#<?= $h['id'] ?></li>
        </ol></nav>
    </div>
    <span class="badge <?= $badgeH ?> fs-6"><?= str_replace('_',' ',$h['estado']) ?></span>
</div>

<div class="row g-4">
<!-- ── Info del hallazgo ────────────────────────────────────────────── -->
<div class="col-lg-5">
    <div class="card mb-3">
        <div class="card-header py-2"><strong>Datos del Hallazgo</strong></div>
        <div class="card-body" style="font-size:13px;">
            <dl class="row mb-0">
                <dt class="col-5">Programa:</dt>
                <dd class="col-7"><?= e($h['programa']) ?> (<?= $h['anio'] ?>)</dd>
                <dt class="col-5">Auditor Líder:</dt>
                <dd class="col-7"><?= e($h['auditor_lider_nombre']??$h['auditor_lider']??'—') ?></dd>
                <dt class="col-5">Tipo:</dt>
                <dd class="col-7"><span class="badge bg-warning text-dark"><?= str_replace('_',' ',$h['tipo']) ?></span></dd>
                <dt class="col-5">Cláusula ISO:</dt>
                <dd class="col-7"><span><?= e($h['clausula_iso'] ?? '—') ?></span></dd>
                <dt class="col-5">Proceso:</dt>
                <dd class="col-7"><?= e($h['proceso_auditado'] ?? '—') ?></dd>
                <dt class="col-5">Descripción:</dt>
                <dd class="col-7"><?= nl2br(e($h['descripcion'])) ?></dd>
                <?php if ($h['evidencia']): ?>
                <dt class="col-5">Evidencia:</dt>
                <dd class="col-7"><?= nl2br(e($h['evidencia'])) ?></dd>
                <?php endif; ?>
                <dt class="col-5">Registrado:</dt>
                <dd class="col-7"><?= date('d/m/Y H:i', strtotime($h['fecha_registro'])) ?></dd>
            </dl>
        </div>
    </div>
    <?php if ($h['codigo_ac'] ?? null): ?>
    <div class="card border-info">
        <div class="card-header py-2 bg-info bg-opacity-10">
            <i class="bi bi-link-45deg me-1"></i>
            <strong>Acción Correctiva Vinculada</strong>
        </div>
        <div class="card-body" style="font-size:13px;">
            <strong><?= e($h['codigo_ac']) ?></strong> —
            Estado: <span class="badge bg-info text-dark"><?= $h['estado_ac'] ?></span>
            <p class="mb-0 mt-1 text-muted"><?= e(truncar($h['ac_descripcion'] ?? '', 100)) ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Formulario de gestión ───────────────────────────────────────── -->
<div class="col-lg-7">
<div class="card">
    <div class="card-header py-2"><strong><i class="bi bi-gear me-1"></i>Gestionar Hallazgo</strong></div>
    <div class="card-body">
    <?php if ($h['estado'] === 'CERRADO'): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i>
        Este hallazgo fue cerrado el
        <strong><?= $h['fecha_cierre'] ? date('d/m/Y', strtotime($h['fecha_cierre'])) : '—' ?></strong>.
    </div>
    <?php else: ?>
    <form action="<?= e(APP_URL) ?>/hallazgos/<?= $h['id'] ?>" method="POST">
        <?= csrfField() ?>

        <!-- CA-2: Actualizar estado -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                <select class="form-select" name="estado">
                    <?php foreach (['ABIERTO','EN_TRATAMIENTO','CERRADO'] as $est): ?>
                    <option value="<?= $est ?>" <?= $h['estado'] === $est ? 'selected' : '' ?>>
                        <?= str_replace('_',' ',$est) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Responsable</label>
                <input type="text" class="form-control" name="responsable"
                       value="<?= e($h['responsable_nombre']??$h['responsable']??'') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Acción Correctiva / Tratamiento</label>
            <textarea class="form-control" name="accion_correctiva" rows="3"><?= e($h['accion_correctiva'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Evidencia del Cierre</label>
            <textarea class="form-control" name="evidencia" rows="2"
                      placeholder="Describa la evidencia que soporta el cierre..."><?= e($h['evidencia'] ?? '') ?></textarea>
        </div>

        <!-- CA-3: Vincular AC -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-link-45deg me-1 text-info"></i>
                Vincular Acción Correctiva
                <small class="text-muted">(CA-3)</small>
            </label>
            <select class="form-select" name="id_accion_correctiva">
                <option value="">— Sin vincular —</option>
                <?php foreach ($acs as $ac): ?>
                <option value="<?= $ac['id'] ?>"
                        <?= (int)($h['id_accion_correctiva'] ?? 0) === (int)$ac['id'] ? 'selected' : '' ?>>
                    <?= e($ac['codigo']) ?> — <?= e(truncar($ac['descripcion_nc'] ?? '', 500)) ?>
                    (<?= $ac['estado'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">
                Al cerrar el hallazgo con AC vinculada, la AC pasará a estado
                <strong>VERIFICACION</strong> automáticamente.
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Fecha de Cierre</label>
            <input type="date" class="form-control" name="fecha_cierre"
                   value="<?= e($h['fecha_cierre'] ?? '') ?>">
            <div class="form-text">Se asigna automáticamente al cerrar.</div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-lim-primary">
                <i class="bi bi-save me-1"></i>Guardar Cambios
            </button>
            <button type="submit" name="estado" value="CERRADO"
                    class="btn btn-success"
                    onclick="swalConfirm(event, '¿Cerrar definitivamente este hallazgo?')">
                <i class="bi bi-check2-circle me-1"></i>Cerrar Hallazgo
            </button>
            <a href="<?= e(APP_URL) ?>/hallazgos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
    </div>
</div>
</div>
</div>
