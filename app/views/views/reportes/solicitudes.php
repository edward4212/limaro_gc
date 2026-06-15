<?php
$campos = ['desde','hasta','estado','tipo'];
$opciones = [
    'estados' => ['CREADA','ASIGNADA','EN_DESARROLLO','FINALIZADA','CANCELADA'],
    'tipos'   => [
        ['id_tipo_documento' => 'CREACION',     'tipo_documento' => 'Creación'],
        ['id_tipo_documento' => 'ACTUALIZACION','tipo_documento' => 'Actualización'],
        ['id_tipo_documento' => 'ELIMINACION',  'tipo_documento' => 'Eliminación'],
    ],
];
?>
<div class="page-header d-print-none"><div><h2><i class="bi bi-clipboard-data me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<?php include __DIR__ . '/_filtros.php'; ?>
<?php if (!empty($resumen)): ?>
<div class="row g-2 mb-3">
<?php foreach ($resumen as $r): ?>
<div class="col-auto">
<div class="card text-center px-3 py-2 shadow-sm">
<div class="fw-bold"><?= (int)$r['total'] ?></div>
<div style="font-size:12px;"><?= e($r['estado']) ?> / <?= e($r['tipo']) ?></div>
<div class="text-muted" style="font-size:12px;">~<?= round($r['promedio_horas'],1) ?>h prom.</div>
</div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>#</th><th>Tipo</th><th>Solicitante</th><th>Tipo Doc.</th><th>Prioridad</th><th>Estado</th><th>Asignado a</th><th>F. Solicitud</th><th>F. Solución</th><th>Horas</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<tr>
<td><?= (int)$d['id_solicitud'] ?></td>
<td><span class="badge bg-secondary" style="font-size:12px;"><?= e($d['tipo_solicitud']) ?></span></td>
<td style="font-size:12px;"><?= e($d['solicitante']) ?></td>
<td style="font-size:12px;"><?= e($d['tipo_documento']) ?></td>
<td style="font-size:12px;"><?= e($d['prioridad']) ?></td>
<td><?= badgeEstado($d['estado_solicitud']) ?></td>
<td style="font-size:12px;"><?= e($d['funcionario_asignado'] ?? '—') ?></td>
<td style="font-size:12px;"><?= fechaEs($d['fecha_solicitud']) ?></td>
<td style="font-size:12px;"><?= $d['fecha_solucion'] ? fechaEs($d['fecha_solucion']) : '—' ?></td>
<td class="text-center" style="font-size:12px;"><?= (int)$d['horas_transcurridas'] ?>h</td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
