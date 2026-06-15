<?php
$iconos = [
    'elaborar'  => 'bi-pencil',
    'revisar'   => 'bi-check2',
    'aprobar'   => 'bi-check2-all',
    'devueltas' => 'bi-arrow-return-left',
];
$icono = $iconos[$tipo] ?? 'bi-list-task';
?>
<div class="page-header">
    <div><h2><i class="bi <?= $icono ?> me-2"></i><?= e($pageTitle) ?></h2></div>
</div>

<!-- KPIs resumen de mis tareas -->
<?php if (!empty($resumen)):
$tiposKpi = [
    'CREADO'     => ['kpi-blue',  'bi-pencil-square',     'Creadas'],
    'REVISION'   => ['kpi-amber', 'bi-eye',               'En Revisión'],
    'APROBACION' => ['kpi-teal',  'bi-check2-square',     'En Aprobación'],
    'DEVUELTO'   => ['kpi-rose',  'bi-arrow-return-left', 'Devueltas'],
    'FINALIZADO' => ['kpi-green', 'bi-check2-circle',     'Finalizadas'],
];
$kpis = [];
foreach ($tiposKpi as $est => [$tipo, $icono, $lbl]) {
    if ($resumen[$est] ?? 0)
        $kpis[] = ['label'=>$lbl,'valor'=>$resumen[$est],'icono'=>$icono,'tipo'=>$tipo,'filtro'=>$est];
}
if (!empty($kpis)) {
    $kpiTotal = ['label'=>'Mis Tareas','valor'=>array_sum($resumen)];
    include APP_ROOT . '/app/views/partials/kpi_cards.php';
}
endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($tareas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
            No hay tareas pendientes en esta sección.
        </div>
        <?php else: ?>
        <table class="table table-hover datatable" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th><th>Solicitud</th><th>Tipo</th><th>Prioridad</th>
                    <th>Solicitante</th><th>Estado Actual</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tareas as $t): ?>
                <tr>
                    <td><?= e($t['id_tarea']) ?></td>
                    <td><?= e($t['tipo_solicitud']) ?></td>
                    <td><?= e($t['tipo_documento']) ?></td>
                    <td><?= prioridadLabel($t['prioridad'] ?? '') ?></td>
                    <td><?= e($t['solicitante']) ?></td>
                    <td><?= badgeEstado($t['estado_actual'] ?? 'CREADO') ?></td>
                    <td>
                        <a href="<?= e(APP_URL . $urlAccion) ?>/<?= $t['id_tarea'] ?>"
                           class="btn btn-sm btn-lim-primary">
                            <i class="bi bi-arrow-right me-1"></i>Gestionar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
