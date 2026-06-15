<?php $tarea = $tarea ?? []; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-check2-square me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/tareas/revisar">Mis Revisiones</a>
            </li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea']) ?></li>
        </ol></nav>
    </div>
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<!-- CA-1: Documento elaborado -->
<?php if (!empty($tarea['archivos'])): ?>
<div class="card mt-3">
    <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-check text-success"></i>
        <strong>Documento Elaborado</strong>
        <span class="badge bg-success"><?= count($tarea['archivos']) ?></span>
        <span class="badge bg-secondary ms-auto">Solo lectura — no se puede reemplazar</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Archivo</th><th style="width:130px" class="text-center">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($tarea['archivos'] as $ar):
                $visType = esVisualizableInline($ar['nombre_original'] ?? '');
            ?>
            <tr>
                <td style="font-size:12px;">
                    <i class="bi <?= iconoArchivo($ar['nombre_original'] ?? '') ?> me-1"></i>
                    <?= e($ar['nombre_original'] ?? 'Archivo') ?>
                    <small class="text-muted ms-1"><?= round(($ar['tamano_bytes'] ?? 0)/1024) ?> KB</small>
                </td>
                <td class="text-center">
                    <?php if ($visType !== 'none'): ?>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$ar['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 me-1" title="Ver">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php endif; ?>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$ar['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Descargar">
                        <i class="bi bi-download"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning mt-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    El elaborador aún no ha subido el documento.
</div>
<?php endif; ?>

<!-- Archivos anexos de la solicitud -->
<?php if (!empty($archivosAnexos)): ?>
<div class="card mt-3">
    <div class="card-header py-2">
        <i class="bi bi-paperclip text-warning me-1"></i>
        <strong>Anexos de la Solicitud Original</strong>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
            <?php foreach ($archivosAnexos as $anx): ?>
            <tr>
                <td style="font-size:12px;">
                    <i class="bi <?= iconoArchivo($anx['nombre_original'] ?? '') ?> me-1"></i>
                    <?= e($anx['nombre_original'] ?? 'Anexo') ?>
                </td>
                <td class="text-center" style="width:90px;">
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$anx['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 me-1">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$anx['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-download"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de revisión -->
<div class="card mt-3">
    <div class="card-header">
        <i class="bi bi-check2-circle me-2"></i>Resultado de la Revisión
    </div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/tareas/revisar/<?= e($tarea['id_tarea']) ?>"
              method="POST">
            <?= csrfField() ?>

            <!-- CA-2: SELECT de aprobadores -->
            <div class="mb-3">
                <label class="form-label">
                    Aprobador
                    <span class="text-danger">*</span>
                    <span class="text-muted" style="font-size:11px;">(requerido para enviar a aprobación)</span>
                </label>
                <select class="form-select" name="id_empleado_aprobador">
                    <option value="">-- Seleccione aprobador --</option>
                    <?php foreach ($aprobadores ?? [] as $apr): ?>
                    <option value="<?= e($apr['id_empleado']) ?>">
                        <?= e($apr['nombre_completo']) ?>
                        <?php if (!empty($apr['cargo'])): ?> — <?= e($apr['cargo']) ?><?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($aprobadores)): ?>
                <div class="form-text text-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No hay usuarios con rol Aprobador activos.
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label">Observaciones de la revisión</label>
                <textarea class="form-control" name="comentario" rows="3"
                          placeholder="Observaciones, correcciones solicitadas o aprobación..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <!-- CA-4: Avanzar a APROBACION -->
                <button type="submit" name="accion" value="aprobar"
                        class="btn btn-success"
                        onclick="swalConfirm(event, '¿Enviar a aprobación? El aprobador recibirá un correo.')">
                    <i class="bi bi-send me-1"></i>Enviar a Aprobación
                </button>
                <!-- CA-3: Devolver al elaborador -->
                <button type="submit" name="accion" value="rechazar"
                        class="btn btn-danger"
                        <?php $msgDevolver = '¿Devolver al elaborador' . (!empty($tarea['elaborador']) ? ' (' . $tarea['elaborador'] . ')' : '') . ' con las observaciones indicadas?'; ?>
                        onclick="swalConfirm(event, '<?= addslashes($msgDevolver) ?>')">
                    <i class="bi bi-arrow-return-left me-1"></i>
                    Devolver al Elaborador
                    <?php if (!empty($tarea['elaborador'])): ?>
                    <span class="badge bg-light text-dark ms-1 fw-normal" style="font-size:10px;">
                        <?= e($tarea['elaborador']) ?>
                    </span>
                    <?php endif; ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php include APP_ROOT . '/app/views/tareas/_comentarios_tarea.php'; ?>
