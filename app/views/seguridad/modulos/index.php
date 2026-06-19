<div class="page-header">
    <div><h2><i class="bi bi-grid me-2"></i>Módulos del Sistema</h2></div>
    <?php if (Auth::puede('modulos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/modulos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Módulo
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Código</th><th>Nombre</th><th>URL</th><th>Padre</th><th>Orden</th><th>Estado</th><th>Acc.</th></tr>
            </thead>
            <tbody>
            <?php foreach ($modulos as $m): ?>
            <tr>
                <td><?= $m['id_modulo'] ?></td>
                <td><code style="font-size:11px;"><?= e($m['codigo']) ?></span></td>
                <td>
                    <i class="bi <?= e($m['icono'] ?? 'bi-circle') ?> me-1 text-primary"></i>
                    <?= e($m['nombre']) ?>
                </td>
                <td style="font-size:11px;"><?= e($m['url'] ?? '—') ?></td>
                <td style="font-size:11px;"><?= e($m['padre_nombre'] ?? '—') ?></td>
                <td class="text-center"><?= $m['orden'] ?></td>
                <td><?= badgeEstado($m['estado']) ?></td>
                <td>
                    <a href="<?= e(APP_URL) ?>/modulos/editar/<?= $m['id_modulo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
