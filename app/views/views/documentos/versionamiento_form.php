<?php
$ultimaVer = $ultimaVersion ? (int)$ultimaVersion['numero_version'] : null;
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-layers me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$documento['id_documento'] ?>">
                    Historial
                </a>
            </li>
            <li class="breadcrumb-item active">Nueva Versión</li>
        </ol></nav>
    </div>
</div>

<!-- Info documento -->
<div class="alert alert-info d-flex gap-3 align-items-center py-2 mb-4">
    <i class="bi bi-file-earmark-text fs-4"></i>
    <div>
        <strong><?= e($documento['codigo'] ?? $documento['codigo_documento'] ?? '') ?></strong>
        — <?= e($documento['nombre_documento']) ?>
        <?php if ($ultimaVersion): ?>
        <span class="ms-3 badge bg-secondary">
            Última versión: V<?= $ultimaVer ?> (<?= e($ultimaVersion['estado_version']) ?>)
        </span>
        <?php endif; ?>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header">
        <i class="bi bi-plus-circle me-1"></i>
        Crear Versión <span class="badge bg-primary ms-1">V<?= $siguiente ?></span>
    </div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/versionamiento/nueva/<?= (int)$documento['id_documento'] ?>"
              method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <!-- Número de versión (informativo) -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Número de Versión</label>
                    <input type="text" class="form-control bg-light fw-bold text-center"
                           value="V<?= $siguiente ?>" readonly tabindex="-1">
                    <div class="form-text">Asignado automáticamente.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <input type="hidden" name="estado_version" value="VIGENTE">
                    <input type="text" class="form-control bg-light fw-bold" value="VIGENTE" readonly tabindex="-1">
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i>
                        Siempre VIGENTE. Las versiones anteriores quedan OBSOLETO.
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Fecha de Aprobación</label>
                    <input type="date" class="form-control" name="fecha_aprobacion"
                           value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Descripción -->
            <div class="mb-3">
                <label class="form-label">Descripción del cambio <span class="text-danger">*</span></label>
                <textarea class="form-control" name="descripcion_version" rows="3" required
                          placeholder="Describa qué cambió en esta versión respecto a la anterior..."><?= old('descripcion_version') ?></textarea>
            </div>

            <!-- CA-3 HU-014: SELECTs con usuarios activos + FKs normalizadas -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Elaboró <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_usuario_creacion" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id_usuario'] ?>"
                                <?= (int)old('id_usuario_creacion', Auth::id() ?? 0) === (int)$u['id_usuario'] ? 'selected' : '' ?>>
                            <?= e($u['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Revisó <span class="text-muted fw-normal">(opcional)</span></label>
                    <select class="form-select" name="id_usuario_revision" required>
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id_usuario'] ?>"
                                <?= (int)old('id_usuario_revision') === (int)$u['id_usuario'] ? 'selected' : '' ?>>
                            <?= e($u['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Aprobó <span class="text-muted fw-normal">(opcional)</span></label>
                    <select class="form-select" name="id_usuario_aprobacion" required>
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id_usuario'] ?>"
                                <?= (int)old('id_usuario_aprobacion') === (int)$u['id_usuario'] ? 'selected' : '' ?>>
                            <?= e($u['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Archivo -->
            <div class="mb-4">
                <label class="form-label">Archivo del documento <span class="text-danger">*</span></label>
                <input type="file" class="form-control" name="archivo" id="inp_archivo" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                <div class="form-text">
                    PDF, Word, Excel o PowerPoint.
                    Se guardará en:
                    <code>.../<strong>V<?= $siguiente ?></strong>/</code>
                    dentro de la carpeta del documento.
                </div>
                <!-- Preview nombre del archivo seleccionado -->
                <div id="archivo_info" class="mt-1 text-success fw-bold" style="display:none;font-size:13px;"></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar Versión V<?= $siguiente ?>
                </button>
                <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$documento['id_documento'] ?>"
                   class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
document.getElementById('inp_archivo').addEventListener('change', function () {
    const info = document.getElementById('archivo_info');
    if (this.files[0]) {
        info.textContent = '✔ ' + this.files[0].name;
        info.style.display = '';
    } else {
        info.style.display = 'none';
    }
});
</script>
