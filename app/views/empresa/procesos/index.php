<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-diagram-2 me-2"></i>Procesos</h2></div>
    <?php if (Auth::puede('procesos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/procesos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Proceso
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Macroproceso</th><th>Proceso</th><th>Sigla</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($procesos as $p): ?>
                <tr>
                    <td><?= e($p['id_proceso']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($p['nombre_macroproceso']) ?></span></td>
                    <td><strong><?= e($p['proceso']) ?></strong></td>
                    <td><code><?= e($p['sigla_proceso']) ?></code></td>
                    <td><?= badgeEstado($p['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('procesos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/procesos/editar/<?= $p['id_proceso'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::puede('procesos', 'eliminar') && $p['estado'] === 'ACTIVO'): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/procesos/eliminar/<?= $p['id_proceso'] ?>','¿Inactivar el proceso: <?= e(addslashes($p['proceso'])) ?>?')">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
