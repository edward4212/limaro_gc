<div class="page-header">
    <div><h2><i class="bi bi-person-circle me-2"></i>Mi Perfil</h2></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">
            <img id="img_preview"
     src="<?= e(APP_URL) ?>/storage/usuarios/<?= e($user['img_empleado'] ?? 'usuario.png') ?>"
     onerror="this.onerror=null; this.src='<?= e(APP_URL) ?>/assets/img/usuario.png';"
     class="rounded-circle mb-3" 
     width="100" 
     height="100" 
     style="object-fit:cover; border:3px solid var(--lim-blue);">
                <h5 class="mb-1"><?= e($user['nombre_completo']) ?></h5>
                <p class="text-muted mb-2"><?= e($user['cargo']) ?></p>
                <span class="badge bg-primary"><?= e($user['rol'] ?? implode(', ', array_column($user['roles'] ?? [], 'rol')) ?? '—') ?></span>

                <form action="<?= e(APP_URL) ?>/perfil" method="POST" enctype="multipart/form-data" class="mt-3">
                    <?= csrfField() ?>
                    <div class="mb-2">
                        <input type="file" class="form-control form-control-sm" id="img_empleado" name="img_empleado"
                               accept="image/jpeg,image/png,image/webp">
                    </div>
                    <button type="submit" class="btn btn-lim-primary btn-sm">
                        <i class="bi bi-upload me-1"></i>Actualizar Foto
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Información Personal</div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Usuario:</dt>
                    <dd class="col-sm-8"><code><?= e($user['usuario']) ?></code></dd>
                    <dt class="col-sm-4">Nombre Completo:</dt>
                    <dd class="col-sm-8"><?= e($user['nombre_completo']) ?></dd>
                    <dt class="col-sm-4">Correo:</dt>
                    <dd class="col-sm-8"><?= e($user['correo_empleado']) ?></dd>
                    <dt class="col-sm-4">Cargo:</dt>
                    <dd class="col-sm-8"><?= e($user['cargo']) ?></dd>
                    <dt class="col-sm-4">Rol:</dt>
                    <dd class="col-sm-8"><span class="badge bg-primary"><?= e($user['rol'] ?? implode(', ', array_column($user['roles'] ?? [], 'rol')) ?? '—') ?></span></dd>
                    <dt class="col-sm-4">Último login:</dt>
                    <dd class="col-sm-8"><?= $user['ultimo_login'] ? fechaEs($user['ultimo_login'], 'hora') : '—' ?></dd>
                    <dt class="col-sm-4">Última clave:</dt>
                    <dd class="col-sm-8"><?= $user['fecha_cambio_clave'] ? fechaEs($user['fecha_cambio_clave'], 'hora') : '—' ?></dd>
                </dl>
                <a href="<?= e(APP_URL) ?>/perfil/cambiar-clave" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-key me-1"></i>Cambiar Clave
                </a>
            </div>
        </div>
    </div>
</div>
