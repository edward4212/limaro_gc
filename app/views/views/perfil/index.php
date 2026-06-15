<div class="page-header">
    <div><h2><i class="bi bi-person-circle me-2"></i>Mi Perfil</h2></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body py-4">
            <?php
$AVATAR_DEFAULT = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj4KICA8Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI1MCIgZmlsbD0iI2UyZThmMCIvPgogIDxjaXJjbGUgY3g9IjUwIiBjeT0iMzgiIHI9IjE4IiBmaWxsPSIjOTRhM2I4Ii8+CiAgPGVsbGlwc2UgY3g9IjUwIiBjeT0iODUiIHJ4PSIyOCIgcnk9IjIwIiBmaWxsPSIjOTRhM2I4Ii8+Cjwvc3ZnPg==';
// Siempre usar el endpoint PHP para evitar 403 en acceso directo a storage
$imgSrc = e(APP_URL) . '/foto-usuario/' . (int)(Auth::id() ?? 0);
?>
<img id="img_preview"
     src="<?= $imgSrc ?>"
     onerror="this.onerror=null; this.style.opacity='0.5';"
     class="rounded-circle mb-3"
     width="100" height="100"
     style="object-fit:cover; border:3px solid var(--lim-blue);">
                <h5 class="mb-1"><?= e($user['nombre_completo']) ?></h5>
                <p class="text-muted mb-2"><?= e($user['cargo']) ?></p>
                <div class="d-flex flex-wrap gap-1 justify-content-center mt-1">
                <?php
                $rolesLista = $user['roles'] ?? [];
                if (empty($rolesLista) && !empty($user['rol'])) {
                    foreach (explode(',', $user['rol']) as $r):
                    $r = trim($r);
                ?>
                <span class="badge bg-primary" style="font-size:12px;"><?= e($r) ?></span>
                <?php endforeach;
                } else {
                    foreach ($rolesLista as $rol):
                ?>
                <span class="badge bg-primary" style="font-size:12px;"><?= e($rol['rol']) ?></span>
                <?php endforeach; } ?>
                </div>

                <form action="<?= e(APP_URL) ?>/perfil" method="POST" id="formFoto" class="mt-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="img_b64"  id="img_b64_val">
                    <input type="hidden" name="img_mime" id="img_mime_val">
                    <div class="mb-2">
                        <input type="file" class="form-control form-control-sm"
                               id="selectorFoto" accept="image/jpeg,image/png,image/webp"
                               onchange="cargarFotoPreview(this)">
                    </div>
                    <button type="submit" class="btn btn-lim-primary btn-sm"
                            onclick="return validarFoto()">
                        <i class="bi bi-upload me-1"></i>Actualizar Foto
                    </button>
                </form>
                <script>
                function cargarFotoPreview(input) {
                    if (!input.files || !input.files[0]) return;
                    var file = input.files[0];
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('img_preview').src = e.target.result;
                        document.getElementById('img_b64_val').value  = e.target.result;
                        document.getElementById('img_mime_val').value = file.type;
                    };
                    reader.readAsDataURL(file);
                }
                function validarFoto() {
                    if (!document.getElementById('img_b64_val').value) {
                        alert('Seleccione una imagen primero.');
                        return false;
                    }
                    return true;
                }
                </script>
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
                    <dd class="col-sm-8">
                    <div class="d-flex flex-wrap gap-1">
                    <?php
                    $rolesInfo = $user['roles'] ?? [];
                    if (empty($rolesInfo) && !empty($user['rol'])) {
                        foreach (explode(',', $user['rol']) as $rr):
                            $rr = trim($rr); if (!$rr) continue;
                    ?>
                    <span class="badge bg-primary" style="font-size:12px;"><?= e($rr) ?></span>
                    <?php endforeach;
                    } else { foreach ($rolesInfo as $rItem): ?>
                    <span class="badge bg-primary" style="font-size:12px;"><?= e($rItem['rol']) ?></span>
                    <?php endforeach; } ?>
                    </div>
                </dd>
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
