<?php
$estadoConfig = [
    'CREADA'                => ['kpi-blue',   'bi-inbox',            'Radicadas'],
    'ASIGNADA'              => ['kpi-amber',  'bi-person-check',     'Asignadas'],
    'EN_DESARROLLO'         => ['kpi-teal',   'bi-hourglass-split',  'En Desarrollo'],
    'FINALIZADA'            => ['kpi-green',  'bi-check2-circle',    'Finalizadas'],
    'FINALIZADA_SIN_TRAMITE'=> ['kpi-rose',   'bi-slash-circle',     'Sin Trámite'],
    'CANCELADA'             => ['kpi-rose',   'bi-x-circle',         'Canceladas'],
];
$resumen = $resumen ?? [];
$total   = array_sum(array_column($resumen, 'total'));
if (is_array(reset($resumen))) {
    $statsMap = array_column($resumen, 'total', 'estado');
} else {
    $statsMap = $resumen;
}
$totalNum = array_sum($statsMap);
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-inbox-fill me-2"></i>Panel de Solicitudes</h2>
        <small class="text-muted">Seguimiento global de todas las solicitudes</small>
    </div>
    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?></small>
</div>

<!-- KPI Cards -->
<div class="row g-2 mb-4">
    <?php foreach ($estadoConfig as $est => [$tipo, $icono, $label]):
        $cnt = (int)($statsMap[$est] ?? 0);
        $pct = $totalNum > 0 ? round($cnt / $totalNum * 100) : 0;
    ?>
    <div class="col-4 col-sm-4 col-md-2">
        <div class="kpi-card <?= $tipo ?>" style="cursor:pointer;"
             onclick="filtrarEstado('<?= htmlspecialchars($est) ?>')">
            <div class="kpi-num"><?= $cnt ?></div>
            <div class="kpi-label"><?= $label ?></div>
            <i class="bi <?= $icono ?> kpi-icon"></i>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-4 col-sm-4 col-md-2">
        <div class="kpi-card" style="background:var(--lim-blue);cursor:pointer;" onclick="filtrarEstado('')">
            <div class="kpi-num"><?= $totalNum ?></div>
            <div class="kpi-label">Total</div>
            <i class="bi bi-list-task kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-header py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <strong><i class="bi bi-table me-1"></i>Todas las Solicitudes</strong>
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($estadoConfig as $est => [$tipo, $icono, $label]):
                $cnt = (int)($statsMap[$est] ?? 0);
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
            <span class="badge <?= $badgeClass ?>" style="font-size:12px;cursor:pointer;"
                  onclick="filtrarEstado('<?= htmlspecialchars($est) ?>')">
                <?= $label ?> (<?= $cnt ?>)
            </span>
            <?php endforeach; ?>
            <span class="badge bg-light text-dark border" style="font-size:12px;cursor:pointer;"
                  onclick="filtrarEstado('')">Todos</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm datatable datatable-export mb-0"
               id="tablaSolicitudes" style="width:100%;min-width:700px;">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Documento / Solicitud</th>
                    <th style="width:110px">Tipo</th>
                    <th style="width:70px" class="text-center">Anexos</th>
                    <th style="width:100px">Prioridad</th>
                    <th style="width:130px">Solicitante</th>
                    <th style="width:130px">Asignado a</th>
                    <th style="width:110px" class="text-center">Estado</th>
                    <th style="width:100px">Fecha</th>
                    <th style="width:55px" class="text-center">Ver</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes as $s):
                $est = $s['estado_solicitud'] ?? '';
                [$tipo, $icono, $label] = $estadoConfig[$est] ?? ['kpi-blue','bi-circle','—'];
                $badgeClass = match($tipo) {
                    'kpi-blue'  => 'bg-primary',
                    'kpi-amber' => 'bg-warning text-dark',
                    'kpi-teal'  => 'bg-info text-dark',
                    'kpi-green' => 'bg-success',
                    'kpi-rose'  => 'bg-danger',
                    default     => 'bg-secondary',
                };
                $prio = match($s['prioridad'] ?? '') {
                    'IMPORTANTE - URGENTE'    => ['bg-danger',          '🔴 Urgente'],
                    'IMPORTANTE - NO URGENTE' => ['bg-warning text-dark','🟡 Importante'],
                    'NO IMPORTANTE - URGENTE' => ['bg-info text-dark',  '🟠 Urgente'],
                    default                   => ['bg-light text-dark', '⚪ Normal'],
                };
            ?>
            <tr>
                <td>
                    <strong class="text-primary" style="font-size:12px;"><?= (int)$s['id_solicitud'] ?></strong>
                </td>
                <td style="max-width:200px;">
                    <?php if (!empty($s['codigo_documento']) && $s['codigo_documento'] !== '00000'): ?>
                    <code style="font-size:12px;background:#f1f5f9;padding:1px 4px;border-radius:3px;">
                        <?= e($s['codigo_documento']) ?>
                    </code><br>
                    <?php endif; ?>
                    <small style="font-size:12px;display:block;white-space:nowrap;overflow:hidden;
                                  text-overflow:ellipsis;max-width:190px;color:#6b7280;">
                        <?= e($s['solicitud'] ?? '') ?>
                    </small>
                </td>
                <td style="font-size:12px;"><?= labelTipoSolicitud($s['tipo_solicitud'] ?? '') ?></td>
                <td class="text-center">
                    <?php $nAnexos = (int)($s['total_anexos'] ?? 0); ?>
                    <?php if ($nAnexos > 0): ?>
                    <span class="badge bg-success" style="font-size:12px;cursor:default;"
                          title="<?= $nAnexos ?> archivo(s) adjunto(s)">
                        <i class="bi bi-paperclip me-1"></i><?= $nAnexos ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:12px;" title="Sin archivos anexos">
                        <i class="bi bi-dash"></i>
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $prio[0] ?>" style="font-size:12px;"><?= $prio[1] ?></span>
                </td>
                <td style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">
                    <?= e($s['solicitante'] ?? '—') ?>
                </td>
                <td style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">
                    <?php if (!empty($s['funcionario_asignado']) && !str_contains($s['funcionario_asignado'], '<')): ?>
                        <?= e($s['funcionario_asignado']) ?>
                    <?php elseif (!empty($s['nombre_asignado'] ?? '')): ?>
                        <?= e($s['nombre_asignado']) ?>
                    <?php else: ?>
                        <em class="text-muted" style="font-size:12px;">Sin asignar</em>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <span class="badge <?= $badgeClass ?>" style="font-size:12px;">
                        <i class="bi <?= $icono ?> me-1"></i><?= $label ?>
                    </span>
                </td>
                <td style="font-size:12px;color:#6b7280;white-space:nowrap;">
                    <?= !empty($s['fecha_solicitud'])
                        ? date('d/m/Y', strtotime($s['fecha_solicitud']))
                        : '—' ?>
                </td>
                <td class="text-center">
                    <a href="<?= e(APP_URL) ?>/solicitudes/ver/<?= (int)$s['id_solicitud'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Ver solicitud">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($solicitudes)): ?>
            <tr><td colspan="9" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>No hay solicitudes.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
function filtrarEstado(val) {
    if ($.fn.DataTable.isDataTable('#tablaSolicitudes')) {
        $('#tablaSolicitudes').DataTable().column(6).search(val).draw();
    }
}
</script>
