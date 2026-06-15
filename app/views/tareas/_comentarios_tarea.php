<?php
/**
 * Partial reutilizable: sección de comentarios de la solicitud
 * en vistas de elaborar, revisar y aprobar.
 *
 * Vars esperadas: $comentarios (array), $tarea (array con id_solicitud)
 */
$idSolicitud = $tarea['id_solicitud'] ?? 0;
?>

<div class="card mt-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-chat-text me-2"></i>Comentarios de la Solicitud</span>
        <span class="badge bg-secondary"><?= count($comentarios ?? []) ?></span>
    </div>

    <?php if (!empty($comentarios)): ?>
    <div class="card-body py-2" style="max-height:280px;overflow-y:auto;">
        <?php foreach ($comentarios as $c): ?>
        <div class="d-flex mb-3">
            <div class="flex-shrink-0 me-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                     style="width:32px;height:32px;font-size:13px;font-weight:700;">
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
    <div class="card-body py-3 text-center text-muted" style="font-size:13px;">
        <i class="bi bi-chat-square d-block mb-1" style="font-size:1.5rem;"></i>
        Sin comentarios aún.
    </div>
    <?php endif; ?>

    <?php if ($idSolicitud): ?>
    <div class="card-footer">
        <form action="<?= e(APP_URL) ?>/solicitudes/comentar/<?= $idSolicitud ?>"
              method="POST" data-novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="redirect_to"
                   value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" name="comentario"
                       placeholder="Agregar comentario..." required maxlength="500">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
