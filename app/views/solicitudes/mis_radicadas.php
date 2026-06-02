<div class="page-header">
    <div><h2><i class="bi bi-journal-text me-2"></i>Mis Solicitudes Radicadas</h2></div>
    <div class="d-flex gap-2">
        <a href="<?= e(APP_URL) ?>/solicitudes/crear" class="btn btn-lim-primary btn-sm">
            <i class="bi bi-file-plus me-1"></i>Crear Documento
        </a>
        <a href="<?= e(APP_URL) ?>/solicitudes/actualizar" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-arrow-up me-1"></i>Actualizar Documento
        </a>
        <a href="<?= e(APP_URL) ?>/solicitudes/eliminar" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-x me-1"></i>Eliminar Documento
        </a>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo Solicitud</th><th>Tipo Doc.</th><th>Prioridad</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No has radicado solicitudes.</td></tr>
                <?php else: ?>
                <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td><?= e($s['id_solicitud']) ?></td>
                    <td><?= e($s['tipo_solicitud']) ?></td>
                    <td><?= e($s['tipo_documento']) ?></td>
                    <td><?= prioridadLabel($s['prioridad'] ?? 'NO_URGENTE_IMPORTANTE') ?></td>
                    <td><?= badgeEstado($s['estado_solicitud']) ?></td>
                    <td><?= fechaEs($s['fecha_solicitud']) ?></td>
                    <td>
                        <a href="<?= e(APP_URL) ?>/solicitudes/ver/<?= $s['id_solicitud'] ?>"
                           class="btn btn-sm btn-outline-info py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
