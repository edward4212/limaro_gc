<?php
$estados_tarea = ['CREADO','REVISION','APROBACION','DEVUELTO','FINALIZADO','CAMBIO'];
$campos = ['desde','hasta'];
$opciones = ['estados' => $estados_tarea];
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
                <tr><th>#Tarea</th><th>#Solicitud</th><th>Tipo</th><th>Código Doc.</th><th>Solicitante</th><th>Asignado a</th><th>Estado Actual</th><th>Última Act.</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $d): ?>
                <tr>
                    <td><?= (int)$d['id_tarea'] ?></td>
                    <td><?= (int)$d['id_solicitud'] ?></td>
                    <td><span class="badge bg-secondary" style="font-size:10px;"><?= e($d['tipo_solicitud']) ?></span></td>
                    <td><code style="font-size:11px;"><?= e($d['codigo_documento']) ?></code></td>
                    <td style="font-size:12px;"><?= e($d['solicitante']) ?></td>
                    <td style="font-size:12px;"><?= e($d['funcionario_asignado'] ?? '—') ?></td>
                    <td><?= badgeEstado($d['tarea_estado']) ?></td>
                    <td style="font-size:11px;"><?= fechaEs($d['fecha_tarea_estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
