<div class="page-header">
    <div>
        <h2><i class="bi bi-people me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 9.3</small>
    </div>
    <?php if (Auth::puede('revision_direccion','crear')): ?>
    <a href="<?= e(APP_URL) ?>/revision-direccion/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nueva Revisión
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Año</th><th>Fecha</th><th>Convocado por</th><th>Entradas</th><th>Estado</th><th>Acta</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($revisiones as $r): ?>
                <tr>
                    <td><strong><?= e($r['anio']) ?></strong></td>
                    <td><?= fechaEs($r['fecha_revision']) ?></td>
                    <td><?= e($r['convocado_por']) ?></td>
                    <td style="font-size:12px;"><?= e(truncar($r['entradas']??'',60)) ?></td>
                    <td><?= badgeEstado($r['estado']) ?></td>
                    <td>
                        <?php if ($r['archivo_acta']): ?>
                        <a href="<?= e(APP_URL) ?>/public<?= e($r['archivo_acta']) ?>" target="_blank"
                           class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-file-pdf"></i></a>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= e(APP_URL) ?>/revision-direccion/ver/<?= (int)$r['id'] ?>"
                           class="btn btn-sm btn-outline-info py-0"><i class="bi bi-eye"></i></a>
                        <?php if (Auth::puede('revision_direccion','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/revision-direccion/editar/<?= (int)$r['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
