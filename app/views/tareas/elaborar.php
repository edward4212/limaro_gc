<?php $tarea = $tarea ?? []; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-pencil me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/tareas/elaborar">Mis Tareas</a></li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea']) ?></li>
        </ol></nav>
    </div>
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<!-- Formulario de elaboración -->
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-upload me-2"></i>Subir Documento y Enviar</div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/tareas/elaborar/<?= e($tarea['id_tarea']) ?>" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Documento elaborado <span class="text-muted">(PDF o DOCX, máx. 20MB)</span></label>
                <input type="file" class="form-control" name="archivo" accept=".pdf,.doc,.docx">
            </div>
            <div class="mb-3">
                <label class="form-label">Comentario</label>
                <textarea class="form-control" name="comentario" rows="3"
                          placeholder="Describa los cambios realizados..."></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="accion" value="guardar" class="btn btn-secondary">
                    <i class="bi bi-save me-1"></i>Guardar borrador
                </button>
                <button type="submit" name="accion" value="enviar" class="btn btn-lim-primary"
                        onclick="return confirm('¿Enviar a revisión? Asegúrese de subir el documento.')">
                    <i class="bi bi-send me-1"></i>Enviar a Revisión
                </button>
            </div>
        </form>
    </div>
</div>
