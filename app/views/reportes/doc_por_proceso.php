<div class="page-header d-print-none"><div><h2><i class="bi bi-diagram-3 me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>Macroproceso</th><th>Proceso</th><th>Total</th><th>Vigentes</th><th>Obsoletos</th><th>En Creación</th><th>% Vigentes</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<?php $pct = $d['total'] > 0 ? round($d['vigentes']/$d['total']*100) : 0; ?>
<tr>
<td><span class="badge bg-secondary" style="font-size:11px;"><?= e($d['macroproceso']) ?></span></td>
<td><?= e($d['proceso']) ?></td>
<td class="text-center fw-bold"><?= (int)$d['total'] ?></td>
<td class="text-center text-success fw-bold"><?= (int)$d['vigentes'] ?></td>
<td class="text-center text-danger"><?= (int)$d['obsoletos'] ?></td>
<td class="text-center text-warning"><?= (int)$d['en_creacion'] ?></td>
<td>
<div class="d-flex align-items-center gap-1">
<div class="progress flex-grow-1" style="height:8px;">
<div class="progress-bar bg-<?= $pct>=80?'success':($pct>=50?'warning':'danger') ?>" style="width:<?= $pct ?>%"></div>
</div><small><?= $pct ?>%</small></div></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
