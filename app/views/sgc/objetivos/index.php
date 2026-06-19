<div class="page-header">
    <div>
        <h2><i class="bi bi-bullseye me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 6.2</small>
    </div>
    <?php if (Auth::puede('objetivos_calidad','crear')): ?>
    <a href="<?= e(APP_URL) ?>/objetivos-calidad/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Objetivo
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Código</th><th>Objetivo</th><th>Meta</th><th>Indicador</th><th>Frecuencia</th><th>Responsable</th><th>Cumplimiento</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($objetivos as $o): ?>
                <?php
                $pct = $o['pct_cumplimiento'] !== null ? round((float)$o['pct_cumplimiento'] * 100) : null;
                $color = $pct === null ? 'secondary' : ($pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'));
                ?>
                <tr>
                    <td><span><?= e($o['codigo']) ?></span></td>
                    <td class="col-objetivo"><?= e(truncar($o['objetivo'], 500)) ?></td>
                    <td><span class="badge bg-primary"><?= e($o['meta'] ?? '—') ?></span></td>
                    <td style="font-size:12px;"><?= e(truncar($o['indicador'] ?? '', 50)) ?></td>
                    <td><span class="badge bg-secondary" style="font-size:12px;"><?= e($o['frecuencia']) ?></span></td>
                    <td style="font-size:12px;"><?= e($o['responsable'] ?? '—') ?></td>
                    <td>
                        <?php if ($pct !== null): ?>
                        <div class="d-flex align-items-center gap-1">
                            <div class="progress flex-grow-1" style="height:8px;">
                                <div class="progress-bar bg-<?= $color ?>" style="width:<?= min($pct,100) ?>%"></div>
                            </div>
                            <small class="text-<?= $color ?> fw-bold"><?= $pct ?>%</small>
                        </div>
                        <small class="text-muted"><?= (int)$o['total_mediciones'] ?> medición(es)</small>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">Sin mediciones</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (Auth::puede('objetivos_calidad', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/objetivos-calidad/editar/<?= (int)$o['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::puede('objetivos_calidad', 'eliminar')): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/objetivos-calidad/eliminar/<?= (int)$o['id'] ?>','¿Eliminar este objetivo?')"
                                title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
