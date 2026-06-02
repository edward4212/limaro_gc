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
<div class="col-lg-8">
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

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_tipo_documento" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= e($t['id_tipo_documento']) ?>"><?= e($t['tipo_documento']) ?></option>
                        <?php endforeach; ?>
                    </select>
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

            <?php if ($necesitaDocumento): ?>
            <div class="mb-3">
                <label class="form-label">Documento a <?= $tipoSolicitud === 'ACTUALIZACION' ? 'Actualizar' : 'Eliminar' ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="doc_search"
                       placeholder="Buscar por código o nombre..."
                       autocomplete="off">
                <input type="hidden" name="id_documento" id="id_documento">
                <ul id="doc_list" class="list-group mt-1 position-absolute" style="z-index:1000;width:100%;max-width:500px;"></ul>
                <div class="form-text">Escriba al menos 2 caracteres para buscar.</div>
            </div>
            <?php else: ?>
            <input type="hidden" name="id_documento" value="">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Descripción / Justificación <span class="text-danger">*</span></label>
                <textarea class="form-control" name="descripcion" rows="4" required
                          maxlength="1000" placeholder="Describa detalladamente la solicitud..."><?= old('descripcion') ?></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label">Archivo Adjunto <small class="text-muted">(opcional, máx. 20MB)</small></label>
                <input type="file" class="form-control" name="adjunto"
                       accept=".pdf,.doc,.docx,.jpg,.png">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-send me-1"></i>Radicar Solicitud
                </button>
                <a href="<?= e(APP_URL) ?>/solicitudes/mis-radicadas" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
