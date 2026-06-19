<?php
$estados = ['CREADO','REVISION','APROBACION','DEVUELTO','FINALIZADO'];
$colores = [
    'CREADO'     => ['kpi-blue',  'bi-pencil-square',     'Creado'],
    'REVISION'   => ['kpi-amber', 'bi-eye-fill',          'Revisión'],
    'APROBACION' => ['kpi-teal',  'bi-check2-square',     'Aprobación'],
    'DEVUELTO'   => ['kpi-rose',  'bi-arrow-return-left', 'Devuelto'],
    'FINALIZADO' => ['kpi-green', 'bi-check2-circle',     'Finalizado'],
];
$stats = $stats ?? [];
$total = array_sum($stats);
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-speedometer2 me-2"></i>Panel de Tareas</h2>
        <small class="text-muted">Seguimiento global del sistema</small>
    </div>
    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?></small>
</div>

<!-- KPI Cards -->
<div class="row g-2 mb-4">
    <?php foreach ($estados as $est):
        [$tipo, $icono, $label] = $colores[$est];
        $cnt = (int)($stats[$est] ?? 0);
        $pct = $total > 0 ? round($cnt / $total * 100) : 0;
    ?>
    <div class="col-4 col-sm-4 col-md-2">
        <div class="kpi-card <?= $tipo ?>" style="cursor:pointer;"
             onclick="filtrarEstado('<?= $est ?>')">
            <div class="kpi-num"><?= $cnt ?></div>
            <div class="kpi-label"><?= $label ?></div>
            <i class="bi <?= $icono ?> kpi-icon"></i>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-4 col-sm-4 col-md-2">
        <div class="kpi-card" style="background:var(--lim-blue);cursor:pointer;" onclick="filtrarEstado('')">
            <div class="kpi-num"><?= $total ?></div>
            <div class="kpi-label">Total</div>
            <i class="bi bi-list-task kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <strong><i class="bi bi-table me-1"></i>Todas las Tareas</strong>
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($estados as $est):
                [$tipo, , $label] = $colores[$est];
                $cnt = (int)($stats[$est] ?? 0);
                if (!$cnt) continue;
                $badgeClass = match($tipo) {
                    'kpi-blue'  => 'bg-primary',
                    'kpi-amber' => 'bg-warning text-dark',
                    'kpi-teal'  => 'bg-info text-dark',
                    'kpi-green' => 'bg-success',
                    'kpi-rose'  => 'bg-danger',
                    default     => 'bg-secondary',
                };
            ?>
            <span class="badge <?= $badgeClass ?>" style="font-size:10px;cursor:pointer;"
                  onclick="filtrarEstado('<?= $est ?>')">
                <?= $label ?> (<?= $cnt ?>)
            </span>
            <?php endforeach; ?>
            <span class="badge bg-light text-dark border" style="font-size:10px;cursor:pointer;"
                  onclick="filtrarEstado('')">Todos</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm datatable datatable-export mb-0"
               id="tablaPanelTareas" style="width:100%;min-width:750px;">
            <thead>
                <tr>
                    <th style="width:55px">#</th>
                    <th>Tipo Documento</th>
                    <th style="width:130px">Tipo de Solicitud</th>
                    <th style="width:120px">Prioridad</th>
                    <th style="min-width:160px">Responsable</th>
                    <th style="width:90px">Rol</th>
                    <th style="width:115px" class="text-center">Estado</th>
                    <th style="width:95px">Última Act.</th>
                    <th style="width:55px" class="text-center">Ir</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tareas as $t):
                $estado = $t['estado_actual'] ?? '';
                [$tipo, $icono, $label] = $colores[$estado] ?? ['kpi-blue','bi-circle','—'];
                $badgeCls = match($tipo) {
                    'kpi-blue'  => 'bg-primary',
                    'kpi-amber' => 'bg-warning text-dark',
                    'kpi-teal'  => 'bg-info text-dark',
                    'kpi-green' => 'bg-success',
                    'kpi-rose'  => 'bg-danger',
                    default     => 'bg-secondary',
                };
                $prio = match($t['prioridad'] ?? '') {
                    'IMPORTANTE - URGENTE'    => ['bg-danger','🔴 Urgente e Importante'],
                    'IMPORTANTE - NO URGENTE' => ['bg-warning text-dark','🟡 Importante'],
                    'NO IMPORTANTE - URGENTE' => ['bg-info text-dark','🟠 Urgente'],
                    default                   => ['bg-light text-dark','⚪ Normal'],
                };
                $urlVer = match($estado) {
                    'CREADO','DEVUELTO' => APP_URL.'/tareas/elaborar/'.(int)$t['id_tarea'],
                    'REVISION'          => APP_URL.'/tareas/revisar/'.(int)$t['id_tarea'],
                    'APROBACION'        => APP_URL.'/tareas/aprobar/'.(int)$t['id_tarea'],
                    default             => '#',
                };
            ?>
            <tr>
                <td>
                    <strong class="text-primary" style="font-size:12px;"><?= (int)$t['id_tarea'] ?></strong>
                    <br><span class="text-muted" style="font-size:10px;">Sol.<?= (int)$t['id_solicitud'] ?></span>
                </td>
                <td style="font-size:12px;max-width:180px;">
                    <?= e($t['tipo_documento'] ?? '—') ?>
                </td>
                <td style="font-size:11px;"><?= labelTipoSolicitud($t['tipo_solicitud'] ?? '') ?></td>
                <td>
                    <span class="badge <?= $prio[0] ?>" style="font-size:10px;"><?= $prio[1] ?></span>
                </td>
                <td style="font-size:11px;min-width:160px;word-break:break-word;">
                    <?= e($t['responsable_actual'] ?? '—') ?>
                </td>
                <td>
                    <span class="badge bg-primary" style="font-size:10px;">
                        <?= e($t['rol_actual'] ?? '—') ?>
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge <?= $badgeCls ?>" style="font-size:10px;">
                        <i class="bi <?= $icono ?> me-1"></i><?= $label ?>
                    </span>
                </td>
                <td style="font-size:11px;color:#6b7280;white-space:normal;">
                    <?= !empty($t['fecha_ultimo_estado'])
                        ? date('d/m/Y', strtotime($t['fecha_ultimo_estado'])) : '—' ?>
                </td>
                <td class="text-center">
                    <?php if ($urlVer !== '#'): ?>
                    <a href="<?= $urlVer ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <i class="bi bi-check-circle text-success"></i>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tareas)): ?>
            <tr><td colspan="9" class="text-center py-4 text-muted">
                <i class="bi bi-clipboard-x fs-3 d-block mb-2"></i>No hay tareas registradas.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
function filtrarEstado(val) {
    if ($.fn.DataTable.isDataTable('#tablaPanelTareas')) {
        $('#tablaPanelTareas').DataTable().column(6).search(val).draw();
    }
}
</script>
