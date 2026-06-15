<div class="page-header">
    <div>
        <h2><i class="bi bi-arrow-left-right me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/documentos">Documentos</a></li>
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/documentos/editar/<?= (int)$item['id_documento'] ?>">
                    <?= e($item['codigo'] ?? '') ?>
                </a>
            </li>
            <li class="breadcrumb-item active">Reasignar</li>
        </ol></nav>
    </div>
</div>

<!-- Info documento -->
<div class="alert alert-secondary d-flex gap-3 align-items-center py-2 mb-4">
    <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
    <div>
        <span class="fs-6"><?= e($item['codigo'] ?? '') ?></span>
        <span class="ms-2 fw-semibold"><?= e($item['nombre_documento']) ?></span>
        <div class="text-muted" style="font-size:12px;">Proceso actual: <?= e($item['proceso'] ?? '—') ?></div>
    </div>
</div>

<?php if ($tieneActivas): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>No se puede reasignar.</strong>
    Este documento tiene solicitudes en proceso (no finalizadas).
    Finalice o cancele las solicitudes activas antes de reasignar.
</div>
<?php else: ?>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Atención:</strong> Esta operación cambiará el
    <strong>código</strong> del documento y moverá la carpeta física en el servidor.
    No se puede deshacer.
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header">
        <i class="bi bi-arrow-left-right me-1 text-warning"></i>
        Seleccionar proceso destino
    </div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/documentos/reasignar/<?= (int)$item['id_documento'] ?>"
              method="POST" novalidate>
            <?= csrfField() ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Macroproceso destino</label>
                    <select class="form-select" id="reas_macro">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($macroprocesos as $mp): ?>
                        <option value="<?= e($mp['id_macroproceso']) ?>">
                            <?= e($mp['macroproceso']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Proceso destino <span class="text-danger">*</span></label>
                    <select class="form-select" id="reas_proceso" name="id_proceso_nuevo" required disabled>
                        <option value="">-- Seleccione macroproceso --</option>
                        <?php foreach ($procesos as $p): ?>
                        <option value="<?= e($p['id_proceso']) ?>"
                                data-macro="<?= e($p['id_macroproceso']) ?>">
                            <?= e($p['proceso']) ?> (<?= e($p['sigla_proceso']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Subproceso destino
                        <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <select class="form-select" id="reas_subproceso" name="id_subproceso_nuevo" disabled>
                        <option value="">-- Sin subproceso --</option>
                        <?php foreach ($subprocesos as $sp): ?>
                        <option value="<?= e($sp['id_subproceso']) ?>"
                                data-proceso="<?= e($sp['id_proceso']) ?>">
                            <?= e($sp['subproceso']) ?>
                            <?php if ($sp['sigla_subproceso']): ?>
                            (<?= e($sp['sigla_subproceso']) ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nuevo código (vista previa)</label>
                    <input type="text" class="form-control bg-light" id="preview_codigo"
                           value="Se genera al guardar" readonly tabindex="-1">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Motivo del cambio <span class="text-danger">*</span></label>
                <textarea class="form-control" name="observacion_reasignacion" rows="2" required
                          placeholder="Ej: Documento reclasificado por ajuste organizacional"></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning"
                        onclick="swalConfirm(event, '¿Confirma reasignar el documento? Esta acción cambiará el código y moverá la carpeta.')">
                    <i class="bi bi-arrow-left-right me-1"></i>Confirmar Reasignación
                </button>
                <a href="<?= e(APP_URL) ?>/documentos/editar/<?= (int)$item['id_documento'] ?>"
                   class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
// CA-1: Filtrar y habilitar Proceso destino al elegir macroproceso
document.getElementById('reas_macro')?.addEventListener('change', function () {
    const macro     = this.value;
    const selProc   = document.getElementById('reas_proceso');
    const selSub    = document.getElementById('reas_subproceso');

    // Habilitar/deshabilitar según si hay macroproceso seleccionado
    selProc.disabled = !macro;
    selSub.disabled  = true;
    selProc.value    = '';
    selSub.value     = '';

    // Filtrar opciones de proceso
    selProc.querySelectorAll('option[data-macro]').forEach(opt => {
        opt.hidden   = !(!macro || opt.dataset.macro === macro);
        opt.disabled = opt.hidden;
    });
    // Limpiar subprocesos
    selSub.querySelectorAll('option[data-proceso]').forEach(opt => {
        opt.hidden   = true;
        opt.disabled = true;
    });
});

// CA-2: Filtrar y habilitar Subproceso destino al elegir proceso
document.getElementById('reas_proceso')?.addEventListener('change', function () {
    const proceso = this.value;
    const selSub  = document.getElementById('reas_subproceso');

    selSub.disabled = !proceso;
    selSub.value    = '';

    selSub.querySelectorAll('option[data-proceso]').forEach(opt => {
        opt.hidden   = !(!proceso || opt.dataset.proceso === proceso);
        opt.disabled = opt.hidden;
    });
});
</script>

<?php endif; ?>
