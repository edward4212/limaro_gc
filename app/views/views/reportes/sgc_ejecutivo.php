<?php
$doc = $resumen['documentos'] ?? [];
$sol = $resumen['solicitudes'] ?? [];
$sgc = $resumen['sgc'] ?? [];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-speedometer2 me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Generado: <?= date('d/m/Y H:i') ?></small>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<!-- KPIs principales -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 shadow-sm border-0">
            <i class="bi bi-file-earmark-check text-primary fs-1"></i>
            <div class="fw-bold fs-3"><?= (int)($doc['vigentes'] ?? 0) ?></div>
            <div class="text-muted">Documentos Vigentes</div>
            <small class="text-muted"><?= (int)($doc['obsoletos'] ?? 0) ?> obsoletos · <?= (int)($doc['en_creacion'] ?? 0) ?> en creación</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 shadow-sm border-0">
            <i class="bi bi-inbox text-warning fs-1"></i>
            <div class="fw-bold fs-3"><?= (int)($sol['total'] ?? 0) ?></div>
            <div class="text-muted">Solicitudes (12 meses)</div>
            <small class="text-muted"><?= (int)($sol['en_desarrollo'] ?? 0) ?> en desarrollo</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 shadow-sm border-0">
            <i class="bi bi-bullseye text-success fs-1"></i>
            <div class="fw-bold fs-3"><?= $sgc['pct_cumplimiento_obj'] ?? '—' ?>%</div>
            <div class="text-muted">Cumplimiento Objetivos</div>
            <small class="text-muted"><?= (int)($sgc['objetivos_activos'] ?? 0) ?> objetivo(s) activo(s)</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 shadow-sm border-0 <?= ($sgc['ac_vencidas'] ?? 0) > 0 ? 'border-danger' : '' ?>">
            <i class="bi bi-tools <?= ($sgc['ac_vencidas'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' ?> fs-1"></i>
            <div class="fw-bold fs-3 <?= ($sgc['ac_vencidas'] ?? 0) > 0 ? 'text-danger' : '' ?>">
                <?= (int)($sgc['ac_abiertas'] ?? 0) ?>
            </div>
            <div class="text-muted">Acciones Correctivas Abiertas</div>
            <?php if (($sgc['ac_vencidas'] ?? 0) > 0): ?>
            <small class="text-danger fw-bold"><?= (int)$sgc['ac_vencidas'] ?> VENCIDAS</small>
            <?php else: ?>
            <small class="text-success">Sin vencidas</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
<!-- Cumplimiento Objetivos de Calidad -->
<div class="col-lg-6">
<div class="card h-100">
    <div class="card-header"><i class="bi bi-bullseye me-1 text-success"></i><strong>Objetivos de Calidad §6.2</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Código</th><th>Objetivo</th><th>Meta</th><th>Cumplimiento</th></tr></thead>
            <tbody>
                <?php if (empty($objetivos)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-3" style="font-size:12px;">
                        <i class="bi bi-inbox d-block mb-1" style="font-size:1.4rem;"></i>
                        No hay objetivos de calidad activos registrados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($objetivos as $o):
                    $pct   = $o['pct_cumplimiento'] ?? null;
                    $color = $pct === null ? 'secondary' : ($pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'));
                ?>
                <tr>
                    <td><code style="font-size:12px;"><?= e($o['codigo']) ?></span></td>
                    <td style="font-size:12px;"><?= e(truncar($o['objetivo'],45)) ?></td>
                    <td><span class="badge bg-primary" style="font-size:12px;"><?= e($o['meta']??'—') ?></span></td>
                    <td>
                        <?php if ($pct !== null): ?>
                        <div class="d-flex align-items-center gap-1">
                            <div class="progress flex-grow-1" style="height:6px;">
                                <div class="progress-bar bg-<?= $color ?>" style="width:<?= min($pct,100) ?>%"></div>
                            </div>
                            <small class="text-<?= $color ?> fw-bold" style="font-size:12px;"><?= $pct ?>%</small>
                        </div>
                        <?php else: ?>
                        <small class="text-muted">Sin mediciones</small>
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

<!-- Hallazgos de Auditoría -->
<div class="col-lg-6">
<div class="card h-100">
    <div class="card-header"><i class="bi bi-exclamation-triangle me-1 text-warning"></i><strong>Hallazgos Recientes §9.2</strong></div>
    <?php if (empty($hallazgos)): ?>
    <div class="card-body text-muted text-center py-4">Sin hallazgos registrados este año.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Tipo</th><th>Cláusula</th><th>Descripción</th><th>Estado</th></tr></thead>
            <tbody>
                <?php foreach (array_slice($hallazgos, 0, 8) as $h):
                    $ct = ['NO_CONFORMIDAD'=>'danger','FORTALEZA'=>'success','OPORTUNIDAD'=>'warning','OBSERVACION'=>'info'][$h['tipo']] ?? 'secondary';
                ?>
                <tr>
                    <td><span class="badge bg-<?= $ct ?>" style="font-size:12px;"><?= str_replace('_',' ',$h['tipo']) ?></span></td>
                    <td><code style="font-size:12px;"><?= e($h['clausula_iso']??'—') ?></span></td>
                    <td style="font-size:12px;"><?= e(truncar($h['descripcion'],50)) ?></td>
                    <td><?= badgeEstado($h['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($hallazgos) > 8): ?>
    <div class="card-footer text-muted text-center" style="font-size:12px;">
        + <?= count($hallazgos) - 8 ?> más.
        <a href="<?= e(APP_URL) ?>/reportes/sgc/hallazgos">Ver todos</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</div>
</div>

<div class="mt-3 text-muted text-center no-print" style="font-size:12px;">
    Reporte generado por Limaro SGC · COOPAIPE · <?= date('d/m/Y H:i:s') ?>
</div>
