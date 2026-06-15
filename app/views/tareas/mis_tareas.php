<?php
$badges = [
    'CREADO'     => 'bg-secondary',
    'REVISION'   => 'bg-warning text-dark',
    'APROBACION' => 'bg-info text-dark',
    'DEVUELTO'   => 'bg-danger',
    'FINALIZADO' => 'bg-success',
];
$iconos = [
    'CREADO'     => 'bi-pencil',
    'REVISION'   => 'bi-eye',
    'APROBACION' => 'bi-check2',
    'DEVUELTO'   => 'bi-arrow-return-left',
    'FINALIZADO' => 'bi-check2-circle',
];
$links = [
    'CREADO'     => APP_URL . '/tareas/elaborar/',
    'REVISION'   => APP_URL . '/tareas/revisar/',
    'APROBACION' => APP_URL . '/tareas/aprobar/',
    'DEVUELTO'   => APP_URL . '/tareas/elaborar/',
    'FINALIZADO' => '#',
];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-list-task me-2"></i>Mis Tareas</h2>
        <small class="text-muted">Todas tus tareas asignadas y su estado actual</small>
    </div>
</div>

<?php if (empty($tareas)): ?>

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
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox" style="font-size:3rem; color:#cbd5e1;"></i>
        <p class="text-muted mt-3">No tienes tareas asignadas actualmente.</p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover datatable mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Tipo Documento</th>
                    <th style="width:120px">Tipo Solicitud</th>
                    <th style="width:140px">Prioridad</th>
                    <th style="width:100px">Mi Rol</th>
                    <th style="width:130px" class="text-center">Estado</th>
                    <th style="width:110px">Última Act.</th>
                    <th style="width:90px" class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tareas as $t):
                $estado   = $t['estado_actual'] ?? 'CREADO';
                $badge    = $badges[$estado]  ?? 'bg-secondary';
                $icono    = $iconos[$estado]  ?? 'bi-circle';
                $urlBase  = $links[$estado]   ?? '#';
                $urlAccion = $estado !== 'FINALIZADO' ? $urlBase . (int)$t['id_tarea'] : '#';
                $prioridad = match($t['prioridad'] ?? '') {
                    'IMPORTANTE - URGENTE'     => ['bg-danger',   'Urgente e Importante'],
                    'IMPORTANTE - NO URGENTE'  => ['bg-warning text-dark', 'Importante'],
                    'NO IMPORTANTE - URGENTE'  => ['bg-info text-dark',    'Urgente'],
                    default                    => ['bg-light text-dark',   'Normal'],
                };
            ?>
            <tr class="<?= $estado === 'DEVUELTO' ? 'table-danger' : '' ?>">
                <td>
                    <strong class="text-primary"><?= (int)$t['id_tarea'] ?></strong>
                    <br><small class="text-muted" style="font-size:10px;">Sol.<?= (int)$t['id_solicitud'] ?></small>
                </td>
                <td style="font-size:12px;">
                    <?= e($t['tipo_documento'] ?? '—') ?>
                    <br><small class="text-muted" style="font-size:11px;">
                        <?= e($t['solicitante'] ?? '') ?>
                    </small>
                </td>
                <td style="font-size:11px;">
                    <?= labelTipoSolicitud($t['tipo_solicitud'] ?? '') ?>
                </td>
                <td>
                    <span class="badge <?= $prioridad[0] ?>" style="font-size:10px;">
                        <?= $prioridad[1] ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-primary" style="font-size:10px;">
                        <?= e($t['rol_asignacion'] ?? '—') ?>
                    </span>
                    <?php if (($t['estado_asignacion'] ?? '') !== 'ACTIVA'): ?>
                    <span class="badge bg-secondary" style="font-size:9px;">inactiva</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <span class="badge <?= $badge ?> d-flex align-items-center justify-content-center gap-1" style="font-size:11px;">
                        <i class="bi <?= $icono ?>"></i>
                        <?= $estado ?>
                    </span>
                </td>
                <td style="font-size:11px; color:#6b7280;">
                    <?= !empty($t['fecha_ultimo_estado'])
                        ? date('d/m/Y H:i', strtotime($t['fecha_ultimo_estado']))
                        : '—' ?>
                </td>
                <td class="text-center">
                    <?php if ($estado !== 'FINALIZADO'): ?>
                    <a href="<?= $urlAccion ?>"
                       class="btn btn-sm btn-lim-primary py-0 px-2"
                       title="Gestionar tarea">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px;">✓</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
