<div class="page-header">
    <div><h2><i class="bi bi-check2-circle me-2"></i>Tareas Finalizadas</h2></div>
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

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Desde</label>
                <input type="date"
                       class="form-control form-control-sm"
                       name="desde"
                       value="<?= e($desde ?? '') ?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Hasta</label>
                <input type="date"
                       class="form-control form-control-sm"
                       name="hasta"
                       value="<?= e($hasta ?? '') ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-lim-primary">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="?" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- filtro-fechas -->
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export table-sm" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo Solicitud</th>
                    <th>Tipo Doc.</th>
                    <th>Solicitante</th>
                    <th>Finalización</th>
                    <th class="text-center" style="width:80px;">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tareas)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Sin tareas finalizadas.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($tareas as $t): ?>
                    <tr>
                        <td><?= e($t['id_tarea']) ?></td>
                        <td><?= e($t['tipo_solicitud']) ?></td>
                        <td><?= e($t['tipo_documento']) ?></td>
                        <td><?= e($t['solicitante']) ?></td>
                        <td><?= fechaEs($t['fecha_finalizacion']) ?></td>
                        <td class="text-center">
                            <a href="<?= e(APP_URL) ?>/tareas/ver/<?= (int)$t['id_tarea'] ?>"
                               class="btn btn-sm btn-outline-primary py-0 px-2"
                               title="Ver detalle (solo lectura)">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>