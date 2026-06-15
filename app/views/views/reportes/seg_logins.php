<?php
$campos = ['desde','hasta'];
$campos  = ['desde','hasta','usuario'];
$opciones = [
    'estados' => ['EXITOSO','FALLIDO'],
    'usuario' => true,
];
?>
<div class="page-header d-print-none"><div><h2><i class="bi bi-key me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>Usuario</th><th>Nombre</th><th>IP</th><th>Resultado</th><th>Fecha</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<tr class="<?= $d['resultado']==='FALLIDO' ? 'table-warning' : '' ?>">
<td><span><?= e($d['usuario']) ?></span></td>
<td style="font-size:12px;"><?= e($d['nombre_completo'] ?? '—') ?></td>
<td><span style="font-size:12px;"><?= e($d['ip']) ?></span></td>
<td><?= $d['resultado']==='EXITOSO' ? '<span class="badge bg-success">EXITOSO</span>' : '<span class="badge bg-danger">FALLIDO</span>' ?></td>
<td style="font-size:12px;"><?= e($d['fecha']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
