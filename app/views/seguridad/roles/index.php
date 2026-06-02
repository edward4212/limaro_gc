<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-shield-check me-2"></i>Roles del Sistema</h2></div>
    <?php if (Auth::puede('roles', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/roles/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Rol
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Nombre del Rol</th><th>Usuarios</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $r): ?>
                <tr>
                    <td><?= e($r['id_rol']) ?></td>
                    <td><strong><?= e($r['rol']) ?></strong></td>
                    <td><span class="badge bg-info text-dark"><?= e($r['total_usuarios']) ?></span></td>
                    <td><?= badgeEstado($r['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('roles', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/roles/permisos/<?= $r['id_rol'] ?>"
                           class="btn btn-sm btn-outline-info py-0" title="Permisos">
                            <i class="bi bi-shield-lock"></i>
                        </a>
                        <a href="<?= e(APP_URL) ?>/roles/editar/<?= $r['id_rol'] ?>"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::puede('roles', 'eliminar') && $r['estado'] === 'ACTIVO'): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/roles/eliminar/<?= $r['id_rol'] ?>','¿Inactivar el rol: <?= e(addslashes($r['rol'])) ?>?')">
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
