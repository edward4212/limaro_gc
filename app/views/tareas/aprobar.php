<?php $tarea = $tarea ?? []; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-check2-all me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/tareas/aprobar">Aprobar</a></li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea']) ?></li>
        </ol></nav>
    </div>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Acción irreversible:</strong> Al aprobar, se creará una nueva versión VIGENTE del documento
    y las versiones anteriores quedarán OBSOLETAS.
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-check2-all me-2"></i>Decisión Final</div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/tareas/aprobar/<?= e($tarea['id_tarea']) ?>" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Archivo aprobado <span class="text-muted">(opcional — reemplaza el documento)</span></label>
                <input type="file" class="form-control" name="archivo" accept=".pdf,.doc,.docx">
            </div>
            <div class="mb-3">
                <label class="form-label">Comentario de aprobación</label>
                <textarea class="form-control" name="comentario" rows="3"
                          placeholder="Observaciones finales..."></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="accion" value="aprobar" class="btn btn-success"
                        onclick="return confirm('¿APROBAR DEFINITIVAMENTE este documento? Se publicará como nueva versión vigente.')">
                    <i class="bi bi-check-all me-1"></i>APROBAR y Publicar Versión
                </button>
                <button type="submit" name="accion" value="rechazar" class="btn btn-danger"
                        onclick="return confirm('¿Devolver al elaborador?')">
                    <i class="bi bi-arrow-return-left me-1"></i>Devolver
                </button>
            </div>
        </form>
    </div>
</div>
