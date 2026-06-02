<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-diagram-3 me-2"></i>Macroprocesos</h2>
    </div>
    <?php if (Auth::puede('macroprocesos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/macroprocesos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Macroproceso
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Macroproceso</th>
                    <th>Objetivo</th>
                    <th>Procesos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($macroprocesos as $m): ?>
                <tr>
                    <td><?= e($m['id_macroproceso']) ?></td>
                    <td><strong><?= e($m['macroproceso']) ?></strong></td>
                    <td><?= e(truncar($m['objetivo'] ?? '', 80)) ?></td>
                    <td><span class="badge bg-info text-dark"><?= e($m['total_procesos']) ?></span></td>
                    <td><?= badgeEstado($m['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('macroprocesos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/macroprocesos/editar/<?= $m['id_macroproceso'] ?>"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::puede('macroprocesos', 'eliminar') && $m['estado'] === 'ACTIVO'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/macroprocesos/eliminar/<?= $m['id_macroproceso'] ?>',
                                '¿Inactivar el macroproceso: <?= e(addslashes($m['macroproceso'])) ?>?')">
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
