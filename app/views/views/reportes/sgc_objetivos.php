<?php $opciones = ['anio'=>true]; ?>
<div class="page-header d-print-none"><div><h2><i class="bi bi-bullseye me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>Código</th><th>Objetivo</th><th>Meta</th><th>Frecuencia</th><th>Responsable</th><th>Mediciones</th><th>Cumplidas</th><th>% Cumplimiento</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<?php $pct = $d['pct_cumplimiento'] ?? null; $color = $pct===null?'secondary':($pct>=80?'success':($pct>=50?'warning':'danger')); ?>
<tr>
<td><span><?= e($d['codigo']) ?></span></td>
<td><?= e(truncar($d['objetivo'],60)) ?></td>
<td><span class="badge bg-primary"><?= e($d['meta'] ?? '—') ?></span></td>
<td style="font-size:12px;"><?= e($d['frecuencia']) ?></td>
<td style="font-size:12px;"><?= e($d['responsable_nombre']??$d['responsable']??'—') ?></td>
<td class="text-center"><?= (int)$d['total_mediciones'] ?></td>
<td class="text-center"><?= (int)$d['mediciones_cumplidas'] ?>/<?= (int)$d['total_mediciones'] ?></td>
<td><div class="d-flex align-items-center gap-1">
<div class="progress flex-grow-1" style="height:8px;"><div class="progress-bar bg-<?= $color ?>" style="width:<?= min((float)($pct??0),100) ?>%"></div></div>
<small class="text-<?= $color ?> fw-bold"><?= $pct!==null ? $pct.'%' : '—' ?></small>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
