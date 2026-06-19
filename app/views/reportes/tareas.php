<?php
$estados_tarea = ['CREADO','REVISION','APROBACION','DEVUELTO','FINALIZADO','CAMBIO'];
$campos = ['desde','hasta','modo_tiempo'];
$opciones = ['estados' => $estados_tarea];
$modoDias = ($filtros['modo_tiempo'] ?? 'calendario') === 'habiles';
?>
<div class="page-header d-print-none">
    <div><h2><i class="bi bi-list-task me-2"></i><?= e($pageTitle) ?></h2></div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong><?= e($pageTitle) ?></strong>
        <span class="badge bg-primary"><?= count($datos) ?> tarea(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm datatable datatable-export mb-0">
            <thead>
                <tr>
                    <th style="width:60px">#Tarea</th>
                    <th style="width:60px">#Sol.</th>
                    <th>Tipo Solicitud</th>
                    <th>Tipo Documento</th>
                    <th>Solicitante</th>
                    <th>Asignado a</th>
                    <th>Estado</th>
                    <th>Inicio</th>
                    <th class="text-center">
                        Días <?= $modoDias ? 'Hábiles*' : 'Calendario' ?><br>
                        <small class="fw-normal">(2 decimales)</small>
                    </th>
                    <th class="text-center">Horas</th>
                    <th>Última Act.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $d):
                    $horas    = round((float)($d['horas_transcurridas'] ?? 0), 1);
                    $diasCal  = round((float)($d['dias_calendario']       ?? 0), 2);
                    // Días hábiles: aprox 5/7 del total calendario
                    $diasHab  = round($diasCal * 5 / 7, 2);
                    $diasMost = $modoDias ? $diasHab : $diasCal;
                    // Semáforo basado en días (con decimales)
                    $semaforo = $diasMost <= 3  ? 'success'
                              : ($diasMost <= 7  ? 'warning'
                              : 'danger');
                ?>
                <tr>
                    <td><?= (int)$d['id_tarea'] ?></td>
                    <td><?= (int)$d['id_solicitud'] ?></td>
                    <td style="font-size:12px;"><?= labelTipoSolicitud($d['tipo_solicitud'] ?? '') ?></td>
                    <td style="font-size:12px;"><?= e($d['tipo_documento'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($d['solicitante']) ?></td>
                    <td style="font-size:12px;"><?= e($d['funcionario_asignado'] ?? '—') ?></td>
                    <td><?= badgeEstado($d['tarea_estado'] ?? 'CREADO') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($d['fecha_creacion'] ?? $d['fecha_tarea_estado']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $semaforo ?>" title="<?= $diasMost ?> días">
                            <?= number_format($diasMost, 2) ?>
                        </span>
                    </td>
                    <td class="text-center" style="font-size:12px;"><?= number_format($horas, 1) ?> h</td>
                    <td style="font-size:12px;"><?= fechaEs($d['fecha_tarea_estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-print-none text-muted" style="font-size:12px;">
        <span class="badge bg-success me-1">0–3 días</span> En tiempo ·
        <span class="badge bg-warning me-1">4–7 días</span> Moderado ·
        <span class="badge bg-danger me-1">+7 días</span> Excedido
        <?php if ($modoDias): ?>
        <span class="ms-3 fst-italic">* Días hábiles estimados (5/7 del calendario)</span>
        <?php endif; ?>
    </div>
</div>
</div>
