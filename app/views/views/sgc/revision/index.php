<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i>Revisión por la Dirección — §9.3</h2>
        <p class="text-muted mb-0" style="font-size:13px;">
            ISO 9001:2015 — Cláusula 9.3
        </p>
    </div>
    <?php if (Auth::puede('revision_direccion','crear')): ?>
    <a href="<?= e(APP_URL) ?>/revision-direccion/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nueva Revisión
    </a>
    <?php endif; ?>
</div>

<!-- Resumen rápido -->
<div class="row g-3 mb-4">
    <?php
    $total    = count($revisiones);
    $aprob    = count(array_filter($revisiones, fn($r) => $r['estado'] === 'APROBADA'));
    $borrador = count(array_filter($revisiones, fn($r) => $r['estado'] === 'BORRADOR'));
    $anios    = array_unique(array_column($revisiones, 'anio'));
    ?>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-blue">
            <div class="kpi-num"><?= $total ?></div>
            <div class="kpi-label">Total Revisiones</div>
            <i class="bi bi-clipboard-check kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-green">
            <div class="kpi-num"><?= $aprob ?></div>
            <div class="kpi-label">Aprobadas</div>
            <i class="bi bi-check-circle kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-amber">
            <div class="kpi-num"><?= $borrador ?></div>
            <div class="kpi-label">En Borrador</div>
            <i class="bi bi-pencil kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-teal">
            <div class="kpi-num"><?= count($anios) ?></div>
            <div class="kpi-label">Años Registrados</div>
            <i class="bi bi-calendar kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Tabla de revisiones -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Año</th>
                    <th>Fecha Revisión</th>
                    <th>Convocado por</th>
                    <th>Participantes</th>
                    <th class="text-center">Estado</th>
                    <th>Acta</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($revisiones as $rev):
                $badge = match($rev['estado'] ?? '') {
                    'APROBADA'  => 'bg-success',
                    'BORRADOR'  => 'bg-warning text-dark',
                    'REVISION'  => 'bg-info text-dark',
                    default     => 'bg-secondary',
                };
            ?>
            <tr>
                <td><?= $rev['id'] ?></td>
                <td><strong><?= e($rev['anio'] ?? '—') ?></strong></td>
                <td style="font-size:12px;">
                    <?= $rev['fecha_revision'] ? date('d/m/Y', strtotime($rev['fecha_revision'])) : '—' ?>
                </td>
                <td style="font-size:12px;"><?= e($rev['convocado_por'] ?? '—') ?></td>
                <td style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:normal;">
                    <?= e(truncar($rev['participantes'] ?? '—', 50)) ?>
                </td>
                <td class="text-center">
                    <span class="badge <?= $badge ?>" style="font-size:12px;">
                        <?= $rev['estado'] ?? '—' ?>
                    </span>
                </td>
                <td style="font-size:12px;">
                    <?php if ($rev['archivo_acta'] ?? null): ?>
                    <a href="<?= e(APP_URL) ?>/archivo-sgc?path=<?= urlencode($rev['archivo_acta']) ?>"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2" title="Ver acta">
                        <i class="bi bi-file-pdf"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (($rev['estado'] ?? '') === 'APROBADA'): ?>
                    <a href="<?= e(APP_URL) ?>/revision-direccion/ver/<?= $rev['id'] ?>"
                       class="btn btn-sm btn-outline-success py-0 px-2" title="Ver detalle (solo lectura)">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php elseif (Auth::puede('revision_direccion','editar')): ?>
                    <a href="<?= e(APP_URL) ?>/revision-direccion/editar/<?= $rev['id'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php else: ?>
                    <a href="<?= e(APP_URL) ?>/revision-direccion/editar/<?= $rev['id'] ?>"
                       class="btn btn-sm btn-outline-secondary py-0 px-2" title="Ver (solo lectura)">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (Auth::puede('revision_direccion','eliminar') && ($rev['estado'] ?? '') !== 'APROBADA'): ?>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="setModalConfirm('<?= e(APP_URL) ?>/revision-direccion/eliminar/<?= $rev['id'] ?>','¿Eliminar revisión del año <?= e($rev['anio'] ?? '') ?>?')">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($revisiones)): ?>
            <tr><td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-clipboard-x fs-3 d-block mb-2"></i>
                No hay revisiones registradas.
                <a href="<?= e(APP_URL) ?>/revision-direccion/crear">Crear primera revisión</a>.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
