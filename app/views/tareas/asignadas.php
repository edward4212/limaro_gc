<!-- KPIs mis tareas -->
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
        $kpis[] = ['label'=>$lbl,'valor'=>$resumen[$est],'icono'=>$icono,'tipo'=>$tipo];
}
if (!empty($kpis)) {
    $kpiTotal = ['label'=>'Mis Tareas', 'valor'=>array_sum($resumen)];
    include APP_ROOT . '/app/views/partials/kpi_cards.php';
}
endif; ?>

<div class="page-header">
    <div><h2><i class="bi bi-person-check me-2"></i>Solicitudes Asignadas a Mí</h2></div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable" style="width:100%;">
            <thead>
                <tr>
                    <th>Solicitud #</th><th>Tipo</th><th>Tipo Doc.</th><th>Prioridad</th>
                    <th>Mi Rol</th><th>F. Asignación</th><th>Estado Sol.</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asignadas)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No tiene solicitudes asignadas actualmente.</td></tr>
                <?php else: ?>
                <?php foreach ($asignadas as $a): ?>
                <tr>
                    <td><?= e($a['id_solicitud']) ?></td>
                    <td><?= e($a['tipo_solicitud']) ?></td>
                    <td><?= e($a['tipo_documento']) ?></td>
                    <td><?= prioridadLabel($a['prioridad'] ?? '') ?></td>
                    <td><span class="badge bg-primary"><?= e($a['rol_asignacion']) ?></span></td>
                    <td><?= fechaEs($a['fecha_asignacion']) ?></td>
                    <td><?= badgeEstado($d['estado_solicitud'] ?? 'CREADO') ?></td>
                    <td>
                        <?php if (!$a['id_tarea']): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/tareas/iniciar/<?= $a['id_solicitud'] ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <button class="btn btn-sm btn-lim-primary"
                                    onclick="swalConfirm(event, '¿Iniciar tarea para esta solicitud?')">
                                <i class="bi bi-play me-1"></i>Iniciar Tarea
                            </button>
                        </form>
                        <?php else: ?>
                        <?php if (Auth::puede('tarea_elaborar', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/tareas/elaborar/<?= $a['id_tarea'] ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Continuar
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
