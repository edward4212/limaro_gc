<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 9.2</small>
    </div>
    <?php if (Auth::puede('auditoria_interna','crear')): ?>
    <a href="<?= e(APP_URL) ?>/auditoria-interna/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Programa
    </a>
    <?php endif; ?>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <?php
    $estados = ['PROGRAMADA'=>['primary','calendar'], 'EN_CURSO'=>['warning','hourglass-split'],
                'FINALIZADA'=>['success','check-circle'], 'CANCELADA'=>['danger','x-circle']];
    $totales = array_column($resumen, 'total', 'estado');
    foreach ($estados as $est => [$color, $icon]): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-2"></i>
            <div class="fw-bold fs-4"><?= $totales[$est] ?? 0 ?></div>
            <div class="text-muted" style="font-size:12px;"><?= ucfirst(strtolower(str_replace('_',' ',$est))) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Año</th><th>Descripción</th><th>Auditor Líder</th><th>Período</th><th>NC</th><th>Cerrados</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($programas as $p): ?>
                <tr>
                    <td><strong><?= e($p['anio']) ?></strong></td>
                    <td><?= e(truncar($p['descripcion'],60)) ?></td>
                    <td><?= e($p['auditor_lider']) ?></td>
                    <td style="font-size:12px;">
                        <?= $p['fecha_inicio'] ? fechaEs($p['fecha_inicio']) : '—' ?>
                        <?= $p['fecha_fin'] ? ' → ' . fechaEs($p['fecha_fin']) : '' ?>
                    </td>
                    <td class="text-center"><span class="badge bg-danger"><?= (int)$p['nc'] ?></span></td>
                    <td class="text-center"><span class="badge bg-success"><?= (int)$p['cerrados'] ?>/<?= (int)$p['total_hallazgos'] ?></span></td>
                    <td><?= badgeEstado($p['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('auditoria_interna','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/auditoria-interna/editar/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
