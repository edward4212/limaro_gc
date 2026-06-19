<?php $t = $tarea ?? []; ?>
<div class="card">
    <div class="card-header">Información de la Tarea</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Solicitud:</strong><br>#<?= e($t['id_solicitud'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Tipo Solicitud:</strong><br><?= e($t['tipo_solicitud'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Tipo Documento:</strong><br><?= e($t['tipo_documento'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Prioridad:</strong><br><?= prioridadLabel($t['prioridad'] ?? '') ?></div>
        </div>
        <?php if ($t['nombre_documento'] ?? null): ?>
        <div class="row mt-2">
            <div class="col-12">
                <strong>Documento:</strong>
                <code><?= e($t['codigo_documento']) ?></code> — <?= e($t['nombre_documento']) ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($t['descripcion'] ?? null): ?>
        <div class="mt-2">
            <strong>Descripción:</strong>
            <p class="bg-light border rounded p-2 mb-0 mt-1"><?= nl2br(e($t['descripcion'])) ?></p>
        </div>
        <?php endif; ?>
        <!-- Archivos subidos -->
        <?php if (!empty($t['archivos'])): ?>
        <div class="mt-3">
            <strong>Archivos:</strong>
            <?php foreach ($t['archivos'] as $ar): ?>
            <a href="<?= e(APP_URL) ?>/archivo/<?= $ar['id_archivo'] ?>?inline=1"
               target="_blank"
               class="btn btn-sm btn-outline-danger me-1 mt-1"
               title="Ver en navegador">
                <i class="bi bi-eye me-1"></i><?= e($ar['nombre_original']) ?>
            </a>
            <a href="<?= e(APP_URL) ?>/archivo/<?= $ar['id_archivo'] ?>"
               class="btn btn-sm btn-outline-primary me-1 mt-1"
               title="Descargar">
                <i class="bi bi-download"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <!-- Historial de estados -->
        <?php if (!empty($t['estados'])): ?>
        <div class="mt-3">
            <strong>Historial:</strong>
            <div class="mt-2">
                <?php foreach ($t['estados'] as $est): ?>
                <div class="d-flex align-items-start mb-2">
                    <div class="me-2 mt-1"><?= badgeEstado($d['tarea_estado'] ?? 'CREADO') ?></div>
                    <div>
                        <small class="text-muted"><?= fechaEs($est['fecha_estado'], 'hora') ?> — <?= e($est['nombre_completo'] ?? $est['usuario'] ?? '') ?></small>
                        <?php if ($est['descripcion'] ?? null): ?>
                        <p class="mb-0 small"><?= e($est['descripcion']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
