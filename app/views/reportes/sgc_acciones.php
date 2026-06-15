<?php
$campos = ['anio'];
$opciones = ['anio'=>true,'estados'=>['ABIERTA','EN_TRATAMIENTO','VERIFICACION','CERRADA','CANCELADA'],'origenes'=>['AUDITORIA','QUEJA','RECLAMO','INDICADOR','PROCESO','OTRO']];
?>
<div class="page-header d-print-none"><div><h2><i class="bi bi-tools me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>Código</th><th>Origen</th><th>Descripción NC</th><th>Responsable</th><th>F. Plan.</th><th>F. Cierre</th><th>Días</th><th>Eficaz</th><th>Estado</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<?php $vencida = ($d['dias_diferencia']??0)>0 && !in_array($d['estado'],['CERRADA','CANCELADA']); ?>
<tr class="<?= $vencida ? 'table-danger' : '' ?>">
<td><code><?= e($d['codigo']) ?></code></td>
<td><span class="badge bg-secondary" style="font-size:12px;"><?= e($d['origen']) ?></span></td>
<td style="font-size:12px;"><?= e(truncar($d['descripcion_nc'],60)) ?></td>
<td style="font-size:12px;"><?= e($d['responsable_nombre']??$d['responsable']??'—') ?></td>
<td style="font-size:12px;"><?= fechaEs($d['fecha_planificada']) ?></td>
<td style="font-size:12px;"><?= $d['fecha_cierre'] ? fechaEs($d['fecha_cierre']) : '—' ?></td>
<td class="text-center <?= $vencida?'text-danger fw-bold':'' ?>"><?= (int)$d['dias_diferencia'] ?>d</td>
<td class="text-center"><?= $d['eficaz']===null ? '—' : ($d['eficaz'] ? '<i class="bi bi-check text-success fw-bold"></i>' : '<i class="bi bi-x text-danger"></i>') ?></td>
<td><?= badgeEstado($d['estado']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
