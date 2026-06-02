<?php $campos = ['desde','hasta']; $opciones = []; ?>
<div class="page-header d-print-none"><div><h2><i class="bi bi-archive me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card"><div class="card-header d-flex justify-content-between">
<strong><?= e($pageTitle) ?></strong><span class="badge bg-danger"><?= count($datos) ?> registro(s)</span></div>
<div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>Código</th><th>Documento</th><th>Tipo</th><th>Proceso</th><th>Versión</th><th>F. Aprobación</th><th>F. Obsoleto</th><th>Aprobó</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<tr><td><code><?= e($d['codigo']) ?></code></td><td><?= e($d['nombre_documento']) ?></td>
<td><?= e($d['sigla_tipo_documento']) ?></td><td style="font-size:12px;"><?= e($d['proceso']) ?></td>
<td class="text-center"><span class="badge bg-secondary">V<?= e($d['numero_version']) ?></span></td>
<td style="font-size:11px;"><?= fechaEs($d['fecha_aprobacion']) ?></td>
<td style="font-size:11px;"><?= fechaEs($d['fecha_obsoleto']) ?></td>
<td style="font-size:11px;"><?= e($d['aprobo'] ?? '—') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
