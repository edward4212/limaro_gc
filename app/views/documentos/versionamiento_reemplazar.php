<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-arrow-repeat me-2"></i>Reemplazar Archivo</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/versionamiento">Versionamiento</a></li>
                <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$documento['id_documento'] ?>">
                    <?= e($documento['codigo']) ?></a></li>
                <li class="breadcrumb-item active">Reemplazar Archivo</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">

<!-- Info del documento -->
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            <div>
                <code class="fs-6"><?= e($documento['codigo']) ?></code>
                <div class="fw-500" style="font-size:15px;"><?= e($documento['nombre_documento']) ?></div>
                <small class="text-muted">
                    Versión:
                    <span class="badge bg-primary ms-1">V<?= e($version['numero_version']) ?></span>
                    <span class="badge <?= $version['estado_version'] === 'VIGENTE' ? 'bg-success' : 'bg-secondary' ?> ms-1">
                        <?= e($version['estado_version']) ?>
                    </span>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Archivo actual -->
<?php if ($archivoActual): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="font-size:13px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong>Archivo actual:</strong> <?= e($archivoActual['nombre_original']) ?>
        <span class="text-muted ms-2">(<?= number_format($archivoActual['tamano_bytes'] / 1024, 1) ?> KB)</span>
        <br><small class="text-muted">Este archivo será reemplazado permanentemente. El número de versión no cambiará.</small>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info mb-3" style="font-size:13px;">
    <i class="bi bi-info-circle me-2"></i>Esta versión no tiene archivo registrado. Se asignará el nuevo archivo a esta versión.
</div>
<?php endif; ?>

<!-- Formulario -->
<div class="card">
    <div class="card-header">
        <strong>Nuevo archivo para V<?= e($version['numero_version']) ?></strong>
    </div>
    <div class="card-body">
        <form method="POST"
              action="<?= e(APP_URL) ?>/versionamiento/reemplazar/<?= (int)$version['id_versionamiento'] ?>"
              enctype="multipart/form-data"
              novalidate>
            <input type="hidden" name="_csrf_token" value="<?= e(\App\Core\Csrf::token()) ?>">

            <div class="mb-3">
                <label class="form-label fw-500">
                    Seleccionar nuevo archivo <span class="text-danger">*</span>
                </label>
                <!-- Campos ocultos Base64 (igual que versionamiento_form) -->
                <input type="hidden" name="archivo_nuevo_b64"    id="archivo_nuevo_b64">
                <input type="hidden" name="archivo_nuevo_mime"   id="archivo_nuevo_mime">
                <input type="hidden" name="archivo_nuevo_nombre" id="archivo_nuevo_nombre">
                <input type="file" id="archivo_nuevo_picker"
                       class="form-control"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp">
                <div class="form-text">
                    Formatos aceptados: PDF, Word, Excel, PowerPoint. Máx. 20 MB.
                </div>
            </div>

            <div class="mb-3" id="previewArchivo" style="display:none;">
                <div class="alert alert-success py-2 px-3" style="font-size:13px;">
                    <i class="bi bi-file-earmark-check me-2"></i>
                    <span id="nombreArchivo"></span>
                    <span class="text-muted ms-2" id="tamanoArchivo"></span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Motivo del reemplazo <span class="text-muted">(opcional)</span></label>
                <textarea name="motivo" class="form-control" rows="2"
                          placeholder="Ej: Corrección de errores tipográficos, actualización de datos..."
                          maxlength="500"></textarea>
            </div>

            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:12px;">
                <i class="bi bi-shield-exclamation me-2"></i>
                <strong>Acción irreversible.</strong> El archivo anterior será eliminado permanentemente.
                Esta operación queda registrada en la auditoría del sistema.
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$documento['id_documento'] ?>"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-lim-primary" id="btnGuardar" disabled>
                    <i class="bi bi-arrow-repeat me-1"></i>Reemplazar archivo
                </button>
            </div>
        </form>
    </div>
</div>

</div>
</div>

<script>
document.getElementById('archivo_nuevo_picker').addEventListener('change', function() {
    const file = this.files[0];
    const preview    = document.getElementById('previewArchivo');
    const btnGuardar = document.getElementById('btnGuardar');

    if (!file) {
        preview.style.display = 'none';
        btnGuardar.disabled = true;
        return;
    }

    // Mostrar preview
    document.getElementById('nombreArchivo').textContent  = file.name;
    document.getElementById('tamanoArchivo').textContent  = '(' + (file.size / 1024).toFixed(1) + ' KB)';
    preview.style.display = 'block';
    btnGuardar.disabled   = true;
    btnGuardar.textContent = 'Procesando archivo…';

    // Convertir a Base64
    const reader = new FileReader();
    reader.onload = function(e) {
        const b64 = e.target.result.split(',')[1];
        document.getElementById('archivo_nuevo_b64').value    = b64;
        document.getElementById('archivo_nuevo_mime').value   = file.type || 'application/octet-stream';
        document.getElementById('archivo_nuevo_nombre').value = file.name;
        btnGuardar.disabled   = false;
        btnGuardar.innerHTML  = '<i class="bi bi-arrow-repeat me-1"></i>Reemplazar archivo';
    };
    reader.onerror = function() {
        alert('Error al procesar el archivo. Intente de nuevo.');
        btnGuardar.disabled = true;
    };
    reader.readAsDataURL(file);
});
</script>
