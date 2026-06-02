<div class="page-header">
    <div>
        <h2><i class="bi bi-tools me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 10.2</small>
    </div>
    <?php if (Auth::puede('acciones_correctivas','crear')): ?>
    <a href="<?= e(APP_URL) ?>/acciones-correctivas/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nueva AC
    </a>
    <?php endif; ?>
</div>

<!-- KPIs -->
<?php
$totales = array_column($resumen,'total','estado');
$estados_ac = ['ABIERTA'=>['danger','exclamation-circle'],'EN_TRATAMIENTO'=>['warning','hourglass-split'],
               'VERIFICACION'=>['info','search'],'CERRADA'=>['success','check-circle'],'CANCELADA'=>['secondary','x-circle']];
?>
<div class="row g-3 mb-4">
    <?php foreach ($estados_ac as $est => [$col, $ico]): ?>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <i class="bi bi-<?= $ico ?> text-<?= $col ?> fs-2"></i>
            <div class="fw-bold"><?= $totales[$est] ?? 0 ?></div>
            <div class="text-muted" style="font-size:11px;"><?= str_replace('_',' ',$est) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Código</th><th>Origen</th><th>Descripción NC</th><th>Responsable</th><th>F. Planificada</th><th>Vencida</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($acciones as $a): ?>
                <?php
                $vencida = ($a['dias_vencida'] ?? 0) > 0 && !in_array($a['estado'],['CERRADA','CANCELADA']);
                ?>
                <tr class="<?= $vencida ? 'table-danger' : '' ?>">
                    <td><code><?= e($a['codigo']) ?></code></td>
                    <td><span class="badge bg-secondary" style="font-size:10px;"><?= e($a['origen']) ?></span></td>
                    <td style="font-size:12px;"><?= e(truncar($a['descripcion_nc'],65)) ?></td>
                    <td style="font-size:12px;"><?= e($a['responsable']??'—') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($a['fecha_planificada']??null) ?></td>
                    <td class="text-center">
                        <?php if ($vencida): ?>
                        <span class="badge bg-danger"><?= (int)$a['dias_vencida'] ?>d</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= badgeEstado($a['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('acciones_correctivas','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/acciones-correctivas/editar/<?= (int)$a['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
