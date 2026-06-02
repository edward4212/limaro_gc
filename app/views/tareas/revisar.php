<?php $tarea = $tarea ?? []; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-check2 me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/tareas/revisar">Revisar</a></li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea']) ?></li>
        </ol></nav>
    </div>
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-check-circle me-2"></i>Decisión de Revisión</div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/tareas/revisar/<?= e($tarea['id_tarea']) ?>" method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Comentario de revisión</label>
                <textarea class="form-control" name="comentario" rows="3"
                          placeholder="Observaciones de la revisión..."></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="accion" value="aprobar" class="btn btn-success"
                        onclick="return confirm('¿Aprobar y enviar a aprobación final?')">
                    <i class="bi bi-check-lg me-1"></i>Aprobar — Enviar a Aprobación
                </button>
                <button type="submit" name="accion" value="rechazar" class="btn btn-danger"
                        onclick="return confirm('¿Devolver al elaborador?')">
                    <i class="bi bi-arrow-return-left me-1"></i>Devolver al Elaborador
                </button>
            </div>
        </form>
    </div>
</div>
