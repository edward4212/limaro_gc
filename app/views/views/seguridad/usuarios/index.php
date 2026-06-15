<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2></div>
    <?php if (Auth::puede('usuarios', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/usuarios/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Usuario
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Avatar</th><th>Usuario</th><th>Nombre</th><th>Cargo</th><th>Roles</th><th>Último Login</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <img src="<?= e(urlFotoPerfil($u['img_empleado'] ?? null)) ?>"
                             onerror="this.onerror=null; this.src='<?= e(APP_URL) ?>/assets/img/usuario.png';"
                             class="rounded-circle" width="32" height="32"
                             style="object-fit:cover; background:#e5e7eb;"
                             alt="<?= e($u['nombre_completo'] ?? '') ?>">
                    </td>
                    <td><strong><?= e($u['usuario']) ?></strong></td>
                    <td><?= e($u['nombre_completo']) ?></td>
                    <td><?= e($u['cargo']) ?></td>
                    <td>
                        <?php
                        $roles = array_filter(explode(', ', $u['roles_nombres'] ?? $u['rol'] ?? ''));
                        foreach ($roles as $rol): ?>
                        <span class="badge bg-primary me-1"><?= e(trim($rol)) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><?= $u['ultimo_login'] ? fechaEs($u['ultimo_login'], 'hora') : '<span class="text-muted">Nunca</span>' ?></td>
                    <td><?= badgeEstado($u['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('usuarios', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/usuarios/editar/<?= $u['id_usuario'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::puede('usuarios', 'editar')): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/usuarios/resetear/<?= $u['id_usuario'] ?>" style="display:inline;"
                              data-confirm="¿Resetear clave del usuario <?= e(addslashes($u['usuario'])) ?>? Se generará una clave aleatoria y se enviará a su correo.">
                            <?= csrfField() ?>
                            <button class="btn btn-sm btn-outline-warning py-0" title="Resetear clave">
                                <i class="bi bi-key"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <!-- CA-6: Botón Activar para INACTIVO y CREADO -->
                        <?php if (Auth::puede('usuarios', 'editar') && in_array($u['estado'], ['INACTIVO','CREADO'])): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/usuarios/activar/<?= $u['id_usuario'] ?>"
                              style="display:inline;"
                              <?php $msgActivar = '¿Activar usuario ' . addslashes($u['usuario']) . '?'; ?>
                              onsubmit="return swalConfirmForm(event, '<?= $msgActivar ?>')">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-outline-success py-0"
                                    title="Activar usuario">
                                <i class="bi bi-person-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if (Auth::puede('usuarios', 'eliminar') && $u['id_usuario'] != Auth::id() && $u['estado'] === 'ACTIVO'): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/usuarios/eliminar/<?= $u['id_usuario'] ?>','¿Inactivar usuario: <?= e(addslashes($u['usuario'])) ?>?')">
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
