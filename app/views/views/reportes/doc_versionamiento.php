<?php $campos = ['desde','hasta']; $opciones = ['procesos' => $procesos]; ?>
<div class="page-header d-print-none">
    <div><h2><i class="bi bi-layers me-2"></i><?= e($pageTitle) ?></h2></div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>
<?php include __DIR__ . '/_filtros.php'; ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm datatable datatable-export mb-0">
            <thead>
                <tr><th>Código</th><th>Documento</th><th>Proceso</th><th>Versión</th><th>Estado</th><th>Descripción Cambio</th><th>Elaboró</th><th>Revisó</th><th>Aprobó</th><th>F. Aprobación</th><th>F. Obsoleto</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $d): ?>
                <tr>
                    <td><span style="font-size:12px;"><?= e($d['codigo']) ?></span></td>
                    <td style="font-size:12px;"><?= e(truncar($d['nombre_documento'],45)) ?></td>
                    <td style="font-size:12px;"><?= e($d['proceso']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $d['estado_version']==='VIGENTE'?'success':($d['estado_version']==='OBSOLETO'?'secondary':'primary') ?>">V<?= e($d['numero_version']) ?></span></td>
                    <td><?= badgeEstado($d['estado_version']) ?></td>
                    <td style="font-size:12px;"><?= e(truncar($d['descripcion_version']??'',50)) ?></td>
                    <td style="font-size:12px;"><?= e($d['elaborador']??$d['usuario_creacion']??'—') ?></td>
                    <td style="font-size:12px;"><?= e($d['revisor']??$d['usuario_revision']??'—') ?></td>
                    <td style="font-size:12px;"><?= e($d['aprobador']??$d['usuario_aprobacion']??'—') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($d['fecha_aprobacion']) ?></td>
                    <td style="font-size:12px;"><?= $d['fecha_obsoleto'] ? fechaEs($d['fecha_obsoleto']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
