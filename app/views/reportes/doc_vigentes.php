<?php
$campos = ['desde','hasta'];
$opciones = ['procesos' => $procesos, 'tipos' => $tipos];
?>
<div class="page-header d-print-none">
    <div><h2><i class="bi bi-list-check me-2"></i><?= e($pageTitle) ?></h2></div>
    <div class="d-flex gap-2 no-print">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Imprimir</button>
    </div>
</div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><strong><?= e($pageTitle) ?></strong> — <?= date('d/m/Y') ?></span>
        <span class="badge bg-primary"><?= count($datos) ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover datatable datatable-export mb-0">
            <thead>
                <tr><th>Código</th><th>Documento</th><th>Tipo</th><th>Macroproceso</th><th>Proceso</th><th>Subproceso</th><th>Versión</th><th>Elaboró</th><th>Revisó</th><th>Aprobó</th><th>F. Aprobación</th><th>Archivo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $d): ?>
                <tr>
                    <td><span><?= e($d['codigo']) ?></span></td>
                    <td class="col-objetivo"><?= e($d['nombre_documento']) ?></td>
                    <td><span class="badge bg-secondary" style="font-size:12px;"><?= e($d['sigla_tipo_documento']) ?></span></td>
                    <td style="font-size:12px;"><?= e($d['macroproceso']) ?></td>
                    <td style="font-size:12px;"><?= e($d['proceso']) ?></td>
                    <td style="font-size:12px;"><?= e($d['nombre_subproceso'] ?? '—') ?></td>
                    <td class="text-center"><span class="badge bg-primary">V<?= e($d['numero_version']) ?></span></td>
                    <td style="font-size:12px;"><?= e($d['elaboro'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($d['reviso'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($d['aprobo'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($d['fecha_aprobacion']) ?></td>
                    <td class="text-center"><?= $d['tiene_archivo']==='Sí' ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
