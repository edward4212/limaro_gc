<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL) . '/documentos/editar/' . $item['id_documento']
    : e(APP_URL) . '/documentos/crear';

// Al editar: macroproceso actual y subproceso para preselección
$macroPresel = '';
$subPresel   = (int)($item['id_subproceso'] ?? 0);
if ($isEdit) {
    foreach ($procesos as $p) {
        if ((int)$p['id_proceso'] === (int)$item['id_proceso']) {
            $macroPresel = (string)$p['id_macroproceso'];
            break;
        }
    }
}
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-file-earmark-plus me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/documentos">Documentos</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">

        <?php if ($isEdit): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Código actual: <strong><?= e($item['codigo'] ?? $item['codigo_documento'] ?? '') ?></strong>
            <!--<?php if (!empty($item['codigo_anterior'])): ?>-->
            <!--— Código anterior: <span class="text-muted"><?= e($item['codigo_anterior']) ?></span>-->
            <!--<?php endif; ?>-->
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            El código se genera automáticamente: <em>SIGLA_PROCESO-SIGLA_TIPO-001</em>
        </div>
        <?php endif; ?>

        <form action="<?= $action ?>" method="POST" novalidate>
            <?= csrfField() ?>

            <?php if (!$isEdit): ?>
            <!-- ── CREAR: Tipo y Macroproceso ────────────────────── -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_tipo_documento" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= e($t['id_tipo_documento']) ?>">
                            <?= e($t['tipo_documento']) ?> (<?= e($t['sigla_tipo_documento']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Macroproceso</label>
                    <select class="form-select" id="doc_macroproceso">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($macroprocesos as $mp): ?>
                        <option value="<?= e($mp['id_macroproceso']) ?>">
                            <?= e($mp['macroproceso']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Proceso <span class="text-danger">*</span></label>
                <select class="form-select" id="doc_proceso" name="id_proceso" required>
                    <option value="">-- Seleccione macroproceso primero --</option>
                    <?php foreach ($procesos as $p): ?>
                    <option value="<?= e($p['id_proceso']) ?>"
                            data-macro="<?= e($p['id_macroproceso']) ?>">
                        <?= e($p['proceso']) ?> (<?= e($p['sigla_proceso']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- ── Subproceso (crear y editar sin reasignación) ───── -->
            <?php if (!$isEdit): ?>
            <div class="mb-3">
                <label class="form-label">Subproceso <span class="text-muted fw-normal">(opcional)</span></label>
                <select class="form-select" id="doc_subproceso" name="id_subproceso">
                    <option value="">-- Sin subproceso --</option>
                    <?php foreach ($subprocesos as $sp): ?>
                    <option value="<?= e($sp['id_subproceso']) ?>"
                            data-proceso="<?= e($sp['id_proceso']) ?>">
                        <?= e($sp['nombre_proceso'] ?? '') ?><?= !empty($sp['nombre_proceso']) ? ' › ' : '' ?><?= e($sp['subproceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="mb-3">
                <label class="form-label">Subproceso actual <span class="text-muted fw-normal">(opcional)</span></label>
                <select class="form-select" name="id_subproceso">
                    <option value="">-- Sin subproceso --</option>
                    <?php foreach ($subprocesos as $sp): ?>
                    <option value="<?= e($sp['id_subproceso']) ?>"
                        <?= ($subPresel && $subPresel === (int)$sp['id_subproceso']) ? 'selected' : '' ?>>
                        <?= e($sp['nombre_proceso'] ?? '') ?><?= !empty($sp['nombre_proceso']) ? ' › ' : '' ?><?= e($sp['subproceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Para cambiar de proceso use la sección de reasignación más abajo.</div>
            </div>
            <?php endif; ?>

            <!-- ── Nombre ────────────────────────────────────────── -->
            <div class="mb-3">
                <label class="form-label">Nombre del Documento <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre_documento"
                       value="<?= $isEdit ? e($item['nombre_documento']) : old('nombre_documento') ?>"
                       maxlength="300" required>
            </div>

            <!-- ── Descripción ────────────────────────────────────── -->
            <div class="mb-3">
                <label class="form-label">Descripción / Objetivo</label>
                <textarea class="form-control" name="descripcion" rows="3"><?= $isEdit
                    ? e($item['objetivo_documento'] ?? '')
                    : old('descripcion') ?></textarea>
            </div>

            <!-- ── Estado (solo editar) ───────────────────────────── -->
            <?php if ($isEdit): ?>
            <div class="mb-3 col-md-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="ACTIVO"   <?= ($item['estado'] ?? '') === 'ACTIVO'   ? 'selected' : '' ?>>Activo</option>
                    <option value="INACTIVO" <?= ($item['estado'] ?? '') === 'INACTIVO' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 mb-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
                <a href="<?= e(APP_URL) ?>/documentos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
        <?php if ($isEdit): ?>
        <hr class="my-3">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-arrow-left-right text-warning fs-5"></i>
            <div>
                <div class="fw-semibold" style="font-size:13px;">¿Necesita cambiar el proceso de este documento?</div>
                <div class="text-muted" style="font-size:12px;">La reasignación cambia el código y mueve la carpeta. Es una operación separada.</div>
            </div>
            <?php if (Auth::puede('documentos_registrados', 'editar')): ?>
            <a href="<?= e(APP_URL) ?>/documentos/reasignar/<?= (int)$item['id_documento'] ?>"
               class="btn btn-outline-warning btn-sm ms-auto">
                <i class="bi bi-arrow-left-right me-1"></i>Reasignar proceso
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /card-body -->
</div><!-- /card -->
</div>
</div>

<script>
(function () {
    // ── Selects dependientes para CREAR ─────────────────────────
    const selMacroCrear   = document.getElementById('doc_macroproceso');
    const selProcesoCrear = document.getElementById('doc_proceso');
    const selSubCrear     = document.getElementById('doc_subproceso');

    if (selMacroCrear && selProcesoCrear) {
        const snapProc = Array.from(selProcesoCrear.options).filter(o => o.value !== '');
        selMacroCrear.addEventListener('change', function () {
            selProcesoCrear.innerHTML = '<option value="">-- Seleccione --</option>';
            (this.value ? snapProc.filter(o => o.dataset.macro === this.value) : snapProc)
                .forEach(o => selProcesoCrear.appendChild(o.cloneNode(true)));
            if (selSubCrear) selSubCrear.innerHTML = '<option value="">-- Sin subproceso --</option>';
        });
        if (selSubCrear) {
            const snapSub = Array.from(selSubCrear.options).filter(o => o.value !== '');
            selProcesoCrear.addEventListener('change', function () {
                selSubCrear.innerHTML = '<option value="">-- Sin subproceso --</option>';
                (this.value ? snapSub.filter(o => o.dataset.proceso === this.value) : snapSub)
                    .forEach(o => selSubCrear.appendChild(o.cloneNode(true)));
            });
        }
    }

    // ── Selects dependientes para REASIGNAR ─────────────────────
    const reasMacro   = document.getElementById('reas_macro');
    const reasProceso = document.getElementById('reas_proceso');
    const reasSubp    = document.getElementById('reas_subproceso');
    const prevCodigo  = document.getElementById('preview_codigo');

    if (reasMacro && reasProceso) {
        const snapRP = Array.from(reasProceso.options).filter(o => o.value !== '');
        reasMacro.addEventListener('change', function () {
            reasProceso.innerHTML = '<option value="">-- Seleccione --</option>';
            (this.value ? snapRP.filter(o => o.dataset.macro === this.value) : snapRP)
                .forEach(o => reasProceso.appendChild(o.cloneNode(true)));
            if (reasSubp) reasSubp.innerHTML = '<option value="">-- Sin subproceso --</option>';
            if (prevCodigo) prevCodigo.value = 'Se genera al guardar';
        });
    }

    if (reasProceso && reasSubp) {
        const snapRS = Array.from(reasSubp.options).filter(o => o.value !== '');
        reasProceso.addEventListener('change', function () {
            reasSubp.innerHTML = '<option value="">-- Sin subproceso --</option>';
            (this.value ? snapRS.filter(o => o.dataset.proceso === this.value) : snapRS)
                .forEach(o => reasSubp.appendChild(o.cloneNode(true)));
        });
    }
})();
</script>
