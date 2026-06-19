<?php
$campos = ['anio','estado','tipo'];
$opciones = [
    'anio'    => true,
    'estados' => ['ABIERTO','EN_TRATAMIENTO','CERRADO'],
    'tipos'   => [
        ['id_tipo_documento' => 'NO_CONFORMIDAD', 'tipo_documento' => 'No Conformidad'],
        ['id_tipo_documento' => 'HALLAZGO',       'tipo_documento' => 'Hallazgo'],
        ['id_tipo_documento' => 'OBSERVACION',    'tipo_documento' => 'Observación'],
        ['id_tipo_documento' => 'OPORTUNIDAD',    'tipo_documento' => 'Oportunidad de Mejora'],
        ['id_tipo_documento' => 'FORTALEZA',      'tipo_documento' => 'Fortaleza'],
    ],
];
?>
<div class="page-header d-print-none"><div><h2><i class="bi bi-exclamation-triangle me-2"></i><?= e($pageTitle) ?></h2></div>
<button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0">
<thead><tr><th>Año</th><th>Programa</th><th>Tipo</th><th>Cláusula</th><th>Proceso</th><th>Descripción</th><th>Responsable</th><th>F. Cierre</th><th>Estado</th></tr></thead>
<tbody>
<?php foreach ($datos as $d): ?>
<?php $colorT=['NO_CONFORMIDAD'=>'danger','FORTALEZA'=>'success','OPORTUNIDAD'=>'warning','OBSERVACION'=>'info'][$d['tipo']]??'secondary'; ?>
<tr>
<td><?= e($d['anio']) ?></td>
<td style="font-size:12px;"><?= e(truncar($d['programa'],40)) ?></td>
<td><span class="badge bg-<?= $colorT ?>" style="font-size:12px;"><?= str_replace('_',' ',$d['tipo']) ?></span></td>
<td><code style="font-size:12px;"><?= e($d['clausula_iso'] ?? '—') ?></code></td>
<td style="font-size:12px;"><?= e($d['proceso_auditado'] ?? '—') ?></td>
<td style="font-size:12px;"><?= e(truncar($d['descripcion'],70)) ?></td>
<td style="font-size:12px;"><?= e($d['responsable_nombre']??$d['responsable']??'—') ?></td>
<td style="font-size:12px;"><?= $d['fecha_cierre'] ? fechaEs($d['fecha_cierre']) : '—' ?></td>
<td><?= badgeEstado($d['estado'] ?? 'ABIERTO') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
