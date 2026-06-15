<div class="page-header">
    <div><h2><i class="bi bi-check2-circle me-2"></i>Solicitudes Finalizadas</h2></div>
</div>

<!-- Filtro fechas -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1">Desde:</label>
                <input type="date" class="form-control form-control-sm" name="desde" value="<?= e($desde ?? '') ?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">Hasta:</label>
                <input type="date" class="form-control form-control-sm" name="hasta" value="<?= e($hasta ?? '') ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-lim-primary btn-sm">Filtrar</button>
                <a href="<?= e(APP_URL) ?>/solicitudes/finalizadas" class="btn btn-secondary btn-sm ms-1">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Desde</label>
                <input type="date" class="form-control form-control-sm" name="desde"
                       value="<?= e($desde ?? '') ?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Hasta</label>
                <input type="date" class="form-control form-control-sm" name="hasta"
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
<!-- KPIs solicitudes -->
<?php if (!empty($resumen)):
$kpis = [
    ['label'=>'Radicadas',    'valor'=>$resumen['CREADA']??0,        'icono'=>'bi-inbox',           'tipo'=>'kpi-blue'],
    ['label'=>'Asignadas',    'valor'=>$resumen['ASIGNADA']??0,      'icono'=>'bi-person-check',    'tipo'=>'kpi-amber'],
    ['label'=>'En Desarrollo','valor'=>$resumen['EN_DESARROLLO']??0, 'icono'=>'bi-hourglass-split', 'tipo'=>'kpi-teal'],
    ['label'=>'Finalizadas',  'valor'=>$resumen['FINALIZADA']??0,    'icono'=>'bi-check2-circle',   'tipo'=>'kpi-green'],
];
$kpiTotal = ['label'=>'Total','valor'=>array_sum($resumen)];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo</th><th>Tipo Doc.</th><th>Solicitante</th><th>F. Solicitud</th><th>F. Solución</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td><?= e($s['id_solicitud']) ?></td>
                    <td><?= labelTipoSolicitud($s['tipo_solicitud'] ?? '') ?></td>
                    <td><?= e($s['tipo_documento']) ?></td>
                    <td><?= e($s['solicitante']) ?></td>
                    <td><?= fechaEs($s['fecha_solicitud']) ?></td>
                    <td><?= fechaEs($s['fecha_solucion'] ?? null) ?></td>
                    <td>
                        <a href="<?= e(APP_URL) ?>/solicitudes/ver/<?= $s['id_solicitud'] ?>"
                           class="btn btn-sm btn-outline-info py-0"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
