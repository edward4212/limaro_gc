<div class="page-header">
    <div><h2><i class="bi bi-x-circle me-2"></i>Listado Maestro — Documentos Obsoletos</h2></div>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export table-sm" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th><th>Nombre</th><th>Tipo</th><th>Proceso</th>
                    <th>Versión</th><th>Fecha Aprobación</th><th>Fecha Obsoleto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No hay documentos obsoletos.</td></tr>
                <?php else: ?>
                <?php foreach ($documentos as $d): ?>
                <tr>
                    <td><code><?= e($d['codigo_documento']) ?></code></td>
                    <td><?= e($d['nombre_documento']) ?></td>
                    <td><?= e($d['tipo_documento']) ?></td>
                    <td><?= e($d['proceso']) ?></td>
                    <td><span class="badge bg-danger">v<?= e($d['numero_version']) ?></span></td>
                    <td><?= fechaEs($d['fecha_aprobacion']) ?></td>
                    <td><?= fechaEs($d['fecha_obsoleto']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
