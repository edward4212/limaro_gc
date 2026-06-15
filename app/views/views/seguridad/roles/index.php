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
                                                <?php
                        $rolesPredefinidos = [1,2,3,4,5,6,7,8,10,11];
                        $esPredefinido = in_array((int)$r['id_rol'], $rolesPredefinidos);
                        $esAdmin = (int)$r['id_rol'] === 1;
                        if (Auth::puede('roles', 'editar')):
                        ?>
                        <?php if (!$esAdmin): ?>
                        <a href="<?= e(APP_URL) ?>/roles/permisos/<?= $r['id_rol'] ?>"
                           class="btn btn-sm btn-outline-info py-0" title="Gestionar Permisos">
                            <i class="bi bi-shield-check"></i>
                        </a>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" disabled
                                title="ADMINISTRADOR: acceso total inmutable">
                            <i class="bi bi-shield-lock"></i>
                        </button>
                        <?php endif; ?>
                        <?php if (!$esPredefinido): ?>
                        <a href="<?= e(APP_URL) ?>/roles/editar/<?= $r['id_rol'] ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-2" title="Renombrar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/roles/eliminar/<?= $r['id_rol'] ?>','¿Inactivar el rol: <?= e(addslashes($r['rol'])) ?>?')"
                                title="Inactivar">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                        <?php else: ?>
                        <span class="badge bg-secondary ms-1" style="font-size:12px;"
                              title="Rol del sistema — no puede renombrarse ni inactivarse">
                            <i class="bi bi-lock me-1"></i>Sistema
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>

                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
