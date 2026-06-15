<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<?php
$necesitaDocumento = in_array($tipoSolicitud, ['ACTUALIZACION', 'ELIMINACION']);
$iconos = ['CREACION' => 'bi-file-plus', 'ACTUALIZACION' => 'bi-file-arrow-up', 'ELIMINACION' => 'bi-file-x'];
$icono  = $iconos[$tipoSolicitud] ?? 'bi-inbox';
?>
<div class="page-header">
    <div>
        <h2><i class="bi <?= $icono ?> me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/solicitudes/mis-radicadas">Mis Solicitudes</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-10">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <?php
        $actionUrl = [
            'CREACION'    => '/solicitudes/crear',
            'ACTUALIZACION' => '/solicitudes/actualizar',
            'ELIMINACION' => '/solicitudes/eliminar',
        ];
        $action = e(APP_URL) . ($actionUrl[$tipoSolicitud] ?? '/solicitudes/crear');
        ?>
        <form action="<?= $action ?>" method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="tipo_solicitud" value="<?= e($tipoSolicitud) ?>">

            <?php if ($necesitaDocumento): ?>
            <div class="mb-3 position-relative">
                <label class="form-label">
                    Documento a <?= $tipoSolicitud === 'ACTUALIZACION' ? 'Actualizar' : 'Inactivar' ?>
                    <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="doc_search"
                           placeholder="Buscar por código o nombre..."
                           autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="doc_clear"
                            style="display:none;" title="Limpiar">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <input type="hidden" name="id_documento" id="id_documento">
                <input type="hidden" id="hid_tipo_documento" name="id_tipo_documento_hidden">
                <ul id="doc_list" class="list-group shadow mt-1 position-absolute"
                    style="z-index:1050;width:100%;display:none;max-height:240px;overflow-y:auto;"></ul>
                <!-- HU-025: campo código readonly -->
                <?php if (in_array($tipoSolicitud, ['ACTUALIZACION', 'ELIMINACION'])): ?>
                <div id="div_codigo_doc" class="mt-2" style="display:none;">
                    <label class="form-label mb-1" style="font-size:12px;">
                        <i class="bi bi-lock me-1 text-warning"></i>Código del Documento
                        <span class="badge bg-secondary ms-1" style="font-size:9px;">Solo lectura</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-upc"></i></span>
                        <input type="text" id="campo_codigo_doc" class="form-control bg-light fw-bold"
                               readonly placeholder="— Se llenará al seleccionar el documento —"
                               style="font-family:monospace;color:var(--lim-blue);">
                    </div>
                </div>
                <?php endif; ?>
                <div id="doc_selected" class="alert alert-info py-1 px-2 mt-2"
                     style="font-size:12px;display:none;"></div>
                <div class="form-text">Escriba al menos 2 caracteres — el Tipo de Documento se llenará automáticamente.</div>
            </div>
            <?php else: ?>
            <input type="hidden" name="id_documento" value="">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                    <select class="form-select" id="sel_tipo_documento" name="id_tipo_documento" required
                           <?= $necesitaDocumento ? 'disabled title="Se llena automáticamente al seleccionar el documento"' : '' ?>>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= e($t['id_tipo_documento']) ?>"><?= e($t['tipo_documento']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($necesitaDocumento): ?>
                    <div class="form-text"><i class="bi bi-lock-fill me-1 text-warning"></i>Se llena automáticamente al seleccionar el documento.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prioridad</label>
                    <select class="form-select" name="prioridad">
                        <?php foreach ($prioridades as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>



            <div class="mb-3">
                <label class="form-label">Justificación de la solicitud <span class="text-danger">*</span></label>
                <textarea class="form-control" name="descripcion" rows="4" required
                          maxlength="1000" placeholder="Describa detalladamente la solicitud..."><?= old('descripcion') ?></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label">Archivo Adjunto <small class="text-muted">(opcional, máx. 20MB)</small></label>
                <input type="file" class="form-control" name="adjunto"
                       accept=".pdf,.doc,.docx,.jpg,.png">
            </div>

            <div class="d-flex gap-2">
                <?php if ($tipoSolicitud === 'ELIMINACION'): ?>
                <button type="button" class="btn btn-danger"
                        onclick="swalConfirmForm(event,
                            'Se solicitará la inactivación del documento seleccionado.',
                            '¿Confirma solicitud de Inactivación?')">
                    <i class="bi bi-file-x me-1"></i>Solicitar Inactivación
                </button>
                <?php else: ?>
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-send me-1"></i>Radicar Solicitud
                </button>
                <?php endif; ?>
                <a href="<?= e(APP_URL) ?>/solicitudes/mis-radicadas" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

</div>
</div>
</div>

<script>
// Limpiar campos JS al cargar (evita que el navegador restaure valores al presionar Atrás)
window.addEventListener('pageshow', function (e) {
    if (e.persisted) { // página restaurada desde caché del navegador
        const inp    = document.getElementById('doc_search');
        const hidden = document.getElementById('id_documento');
        const selT   = document.getElementById('sel_tipo_documento');
        const hidT   = document.getElementById('hid_tipo_documento');
        const info   = document.getElementById('doc_selected');
        const btnC   = document.getElementById('doc_clear');
        if (inp)    inp.value    = '';
        if (hidden) hidden.value = '';
        if (selT)   selT.value   = '';
        if (hidT)   hidT.value   = '';
        if (info)   info.style.display = 'none';
        if (btnC)   btnC.style.display = 'none';
    }
});
</script>

