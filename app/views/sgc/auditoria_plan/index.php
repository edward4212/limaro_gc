<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-calendar3 me-2"></i><?= e($pageTitle) ?></h2>
        <!--<small class="text-muted">ISO 9001:2015 §9.2 — Auditoría Interna</small>-->
    </div>
    <?php if (Auth::puede('audit_plan','crear')): ?>
    <a href="<?= e(APP_URL) ?>/auditoria/plan/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Plan
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<form method="GET" class="row g-2 mb-3 d-print-none">
    <div class="col-md-2">
        <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los años</option>
            <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
            <option value="<?= $y ?>" <?= ($filtros['anio'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <?php foreach (['BORRADOR','APROBADO','EN_CURSO','FINALIZADO','CANCELADO'] as $e): ?>
            <option value="<?= $e ?>" <?= ($filtros['estado'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong><i class="bi bi-table me-1"></i>Planes Registrados</strong>
        <span class="badge bg-primary"><?= count($planes) ?> plan(es)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm datatable datatable-export mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th class="text-center">Año</th>
                    <th>Tipo</th>
                    <th>Auditor Líder</th>
                    <th>Procesos</th>
                    <th class="text-center">Progreso</th>
                    <th>Fechas</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center d-print-none">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($planes)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No hay planes registrados.</td></tr>
                <?php else: ?>
                <?php foreach ($planes as $p):
                    $tot  = (int)($p['total_actividades'] ?? 0);
                    $comp = (int)($p['actividades_completadas'] ?? 0);
                    $pct  = $tot > 0 ? round($comp/$tot*100) : 0;
                ?>
                <tr>
                    <td><code style="font-size:11px;"><?= e($p['codigo']) ?></span></td>
                    <td style="font-size:12px;max-width:200px;"><?= e($p['titulo']) ?></td>
                    <td class="text-center"><?= (int)$p['anio'] ?></td>
                    <td style="font-size:11px;"><?= e($p['tipo_auditoria'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($p['auditor_nombre'] ?? '—') ?></td>
                    <td style="font-size:11px;max-width:150px;"><?= e($p['procesos'] ?? '—') ?></td>
                    <td class="text-center" style="min-width:90px;">
                        <?php if ($tot > 0): ?>
                        <div class="progress" style="height:6px;" title="<?= $comp ?>/<?= $tot ?> actividades">
                            <div class="progress-bar bg-<?= $pct==100?'success':($pct>=50?'warning':'primary') ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="text-muted" style="font-size:10px;"><?= $comp ?>/<?= $tot ?></small>
                        <?php else: ?>
                        <small class="text-muted" style="font-size:10px;">Sin actividades</small>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:11px;">
                        <?= $p['fecha_inicio'] ? fechaEs($p['fecha_inicio']) : '—' ?>
                        <?= $p['fecha_fin'] ? ' → '.fechaEs($p['fecha_fin']) : '' ?>
                    </td>
                    <td class="text-center"><?= badgeEstado($p['estado']) ?></td>
                    <td class="text-center d-print-none" style="white-space:normal;">
                        <a href="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-info py-0 px-2" title="Ver"><i class="bi bi-eye"></i></a>
                        <?php if ($p['estado'] === 'BORRADOR' && Auth::puede('audit_plan','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/auditoria/plan/editar/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($p['estado'] === 'BORRADOR'): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/plan/revisar/<?= (int)$p['id'] ?>"
                              style="display:inline;">
                            <?= csrfField() ?>
                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2" title="Enviar a Revisión"
                                    onclick="swalConfirmForm(event,
                                        'Se enviará el plan a los Coordinadores de Calidad para revisión.',
                                        'Enviar a Revisión')">
                                <i class="bi bi-send"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($p['estado'] === 'EN_REVISION' && Auth::hasRole([1,2])): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/plan/aprobar/<?= (int)$p['id'] ?>"
                              style="display:inline;">
                            <?= csrfField() ?>
                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" title="Aprobar"
                                    onclick="swalConfirmForm(event,
                                        'Se aprobará el plan <?= e(addslashes($p['codigo'])) ?>.',
                                        '¿Aprobar Plan?')">
                                <i class="bi bi-check2-circle"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
