<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL) . '/subprocesos/editar/' . $item['id_subproceso']
    : e(APP_URL) . '/subprocesos/crear';

// Al editar: encontrar id_macroproceso del proceso guardado
$macroPresel  = '';
$siglaPresel  = '';
if ($isEdit) {
    foreach ($procesos as $p) {
        if ((int)$p['id_proceso'] === (int)$item['id_proceso']) {
            $macroPresel = (string)$p['id_macroproceso'];
            $siglaPresel = $p['sigla_proceso'];
            break;
        }
    }
}

// Serializar procesos para JS (id → sigla)
$procesosJson = json_encode(array_column($procesos, 'sigla_proceso', 'id_proceso'));
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-diagram-3 me-2"></i><?= e($pageTitle) ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/subprocesos">Subprocesos</a></li>
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

            <!-- Macroproceso (filtro visual, no se guarda) -->
            <div class="mb-3">
                <label class="form-label">Macroproceso</label>
                <select class="form-select" id="sel_macro">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($macroprocesos as $mp): ?>
                    <option value="<?= e($mp['id_macroproceso']) ?>"
                        <?= $macroPresel === (string)$mp['id_macroproceso'] ? 'selected' : '' ?>>
                        <?= e($mp['macroproceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Proceso padre -->
            <div class="mb-3">
                <label class="form-label">Proceso <span class="text-danger">*</span></label>
                <select class="form-select" id="sel_proceso" name="id_proceso" required
                       <?= !$macroPresel ? 'disabled' : '' ?>>
                    <option value=""><?= !$macroPresel ? '— Primero seleccione el macroproceso —' : '— Seleccione proceso —' ?></option>
                    <?php foreach ($procesos as $p): ?>
                    <option value="<?= e($p['id_proceso']) ?>"
                            data-macro="<?= e($p['id_macroproceso']) ?>"
                            data-sigla="<?= e($p['sigla_proceso']) ?>"
                        <?= ($isEdit && (int)$item['id_proceso'] === (int)$p['id_proceso']) ? 'selected' : '' ?>>
                        <?= e($p['proceso']) ?> (<?= e($p['sigla_proceso']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sigla: se llena automáticamente del proceso, no editable -->
            <div class="mb-3 ">
                <label class="form-label">Sigla</label>
                <input type="text" class="form-control bg-light fw-bold text-uppercase"
                       id="txt_sigla"
                       name="sigla_subproceso"
                       value="<?= e($siglaPresel) ?>"
                       maxlength="10"
                       readonly tabindex="-1">
                <div class="form-text">
                    <i class="bi bi-info-circle"></i>
                    Se asigna automáticamente al seleccionar el proceso.
                </div>
                <!--<div class="form-text"><i class="bi bi-info-circle"></i> Se toma automáticamente del proceso seleccionado.</div>-->
            </div>

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre del Subproceso <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-uppercase" name="subproceso"
                       value="<?= $isEdit ? e($item['subproceso']) : old('subproceso') ?>"
                       maxlength="200" required>
            </div>

            <!-- Estado -->
            <div class="mb-4 col-md-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="ACTIVO"   <?= (!$isEdit || $item['estado'] === 'ACTIVO')   ? 'selected' : '' ?>>Activo</option>
                    <option value="INACTIVO" <?= ($isEdit  && $item['estado'] === 'INACTIVO') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>

            <!-- Objetivo -->
            <div class="mb-4">
                <label class="form-label">Objetivo</label>
                <textarea class="form-control" name="objetivo" rows="3"><?= $isEdit ? e($item['objetivo']) : old('objetivo') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
                <a href="<?= e(APP_URL) ?>/subprocesos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
(function () {
    const selMacro    = document.getElementById('sel_macro');
    const selProceso  = document.getElementById('sel_proceso');
    const txtSigla    = document.getElementById('txt_sigla');

    // Snapshot de todas las opciones de proceso (sin placeholder)
    const snapshot = Array.from(selProceso.options).filter(o => o.value !== '');

    function filtrarProcesos(idMacro) {
        const valorActual = selProceso.value;
        selProceso.innerHTML = '<option value="">-- Seleccione --</option>';

        const visibles = idMacro
            ? snapshot.filter(o => o.dataset.macro === String(idMacro))
            : snapshot;

        visibles.forEach(o => selProceso.appendChild(o.cloneNode(true)));

        if (valorActual) selProceso.value = valorActual;
        if (visibles.length === 1) selProceso.value = visibles[0].value;

        mostrarSigla();
    }

    function mostrarSigla() {
        const opcion = selProceso.options[selProceso.selectedIndex];
        txtSigla.value = (opcion && opcion.value && opcion.dataset.sigla)
            ? opcion.dataset.sigla
            : '';
    }

    selMacro.addEventListener('change', function () {
        selProceso.disabled = !this.value;
        filtrarProcesos(this.value);
    });

    selProceso.addEventListener('change', mostrarSigla);

    // Al cargar en modo editar: aplicar filtro con macroproceso preseleccionado
    if (selMacro.value) {
        filtrarProcesos(selMacro.value);
    }
})();
</script>
