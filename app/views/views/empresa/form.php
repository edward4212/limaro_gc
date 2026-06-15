<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-diagram-3 me-2"></i>Subprocesos</h2>
    </div>
    <?php if (Auth::puede('subprocesos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/subprocesos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Subproceso
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable" style="width:100%;">
            <thead>
                <tr>
                    <th>Macroproceso</th>
                    <th>Proceso</th>
                    <th>Subproceso</th>
                    <th>Sigla</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subprocesos as $s): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= e($s['nombre_macroproceso']) ?></span></td>
                    <td><?= e($s['nombre_proceso']) ?> <span class="ms-1"><?= e($s['sigla_proceso']) ?></span></td>
                    <td><strong><?= e($s['subproceso']) ?></strong></td>
                    <td><span><?= e($s['sigla_subproceso']) ?></span></td>
                    <td><?= badgeEstado($s['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('subprocesos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/subprocesos/editar/<?= $s['id_subproceso'] ?>"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::puede('subprocesos', 'eliminar') && $s['estado'] === 'ACTIVO'): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                onclick="setModalConfirm(
                                    '<?= e(APP_URL) ?>/subprocesos/eliminar/<?= $s['id_subproceso'] ?>',
                                    '¿Inactivar el subproceso: <?= e(addslashes($s['subproceso'])) ?>?'
                                )">
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
