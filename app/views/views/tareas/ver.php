<?php $tarea = $tarea ?? []; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-file-earmark-text me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/tareas/finalizadas">Tareas Finalizadas</a>
            </li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea'] ?? '') ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-success">
            <i class="bi bi-check2-circle me-1"></i>FINALIZADO
        </span>
        <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Aviso solo lectura -->
<div class="alert alert-info py-2 mb-3" style="font-size:12px;">
    <i class="bi bi-lock me-1"></i>
    <strong>Modo solo lectura.</strong> Esta tarea está finalizada — no se pueden realizar modificaciones.
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<div class="row g-3 mt-1">
    <!-- Archivos del documento -->
    <div class="col-lg-6">
        <?php if (!empty($archivosDoc)): ?>
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-file-earmark me-1"></i>Documento Elaborado
            </div>
            <div class="card-body p-0">
                <?php foreach ($archivosDoc as $a): ?>
                <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                    <i class="bi bi-file-earmark text-primary" style="font-size:1.2rem;"></i>
                    <div class="flex-grow-1" style="font-size:13px;">
                        <?= e($a['nombre_original']) ?>
                        <small class="text-muted ms-1">(<?= round(($a['tamano_bytes']??0)/1024) ?> KB)</small>
                    </div>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$a['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$a['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Anexos de la solicitud -->
        <?php if (!empty($archivosAnexos)): ?>
        <div class="card mt-3">
            <div class="card-header py-2">
                <i class="bi bi-paperclip me-1"></i>Anexos de la Solicitud
                <span class="badge bg-warning text-dark ms-1"><?= count($archivosAnexos) ?></span>
            </div>
            <div class="card-body p-0">
                <?php foreach ($archivosAnexos as $a): ?>
                <div class="d-flex align-items-center gap-2 p-2 border-bottom" style="font-size:12px;">
                    <i class="bi bi-paperclip text-muted"></i>
                    <span class="flex-grow-1"><?= e($a['nombre_original']) ?></span>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$a['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-secondary py-0 px-2">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Comentarios (solo lectura) -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <span><i class="bi bi-chat-text me-2"></i>Comentarios</span>
                <span class="badge bg-secondary"><?= count($comentarios ?? []) ?></span>
            </div>
            <?php if (!empty($comentarios)): ?>
            <div class="card-body py-2" style="max-height:350px;overflow-y:auto;">
                <?php foreach ($comentarios as $c): ?>
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0 me-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                             style="width:30px;height:30px;font-size:12px;font-weight:700;">
                            <?= mb_strtoupper(mb_substr($c['nombre_completo'] ?? 'U', 0, 1)) ?>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="bg-light rounded p-2">
                            <strong style="font-size:12px;"><?= e($c['nombre_completo'] ?? '—') ?></strong>
                            <small class="text-muted ms-2"><?= fechaEs($c['fecha_comentario'], 'hora') ?></small>
                            <p class="mb-0 mt-1" style="font-size:13px;"><?= nl2br(e($c['comentario'])) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3" style="font-size:13px;">
                Sin comentarios registrados.
            </div>
            <?php endif; ?>
            <!-- CA-3: NO hay form de comentarios — solo lectura -->
            <div class="card-footer text-muted text-center py-2" style="font-size:12px;">
                <i class="bi bi-lock me-1"></i>Tarea finalizada — comentarios bloqueados
            </div>
        </div>
    </div>
</div>
