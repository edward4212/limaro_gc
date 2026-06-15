<div class="page-header">
    <div>
        <h2><i class="bi bi-key me-2"></i>Cambiar Clave</h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/perfil">Mi Perfil</a></li>
            <li class="breadcrumb-item active">Cambiar Clave</li>
        </ol></nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-5">
<?php if (Auth::get('clave_requiere_reset')): ?>
<div class="alert alert-warning">
    <i class="bi bi-shield-exclamation me-2"></i>
    <strong>Cambio obligatorio.</strong> Su contraseña fue reseteada por el administrador. 
    Debe establecer una nueva contraseña para continuar.
</div>
<?php endif; ?>
<div class="card">
    <div class="card-header">Cambiar Clave de Acceso</div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle me-2"></i>
            La clave debe tener mínimo <strong>8 caracteres</strong>, incluyendo
            mayúscula, minúscula, número y símbolo especial.
        </div>
        <form action="<?= e(APP_URL) ?>/perfil/cambiar-clave" method="POST" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Clave Actual <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="clave_actual" name="clave_actual" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleActual()">
                        <i class="bi bi-eye" id="eye-actual"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Nueva Clave <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="nueva_clave" name="clave_nueva" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="toggleNew()"><i class="bi bi-eye" id="eye-new"></i></button>
                </div>
                <div id="strength-bar" class="progress mt-1" style="height:4px;display:none;">
                    <div id="strength-fill" class="progress-bar" style="width:0%;"></div>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirmar Nueva Clave <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_clave" name="clave_confirmar" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleConfirm()">
                        <i class="bi bi-eye" id="eye-confirm"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-lim-primary">
                <i class="bi bi-check-lg me-1"></i>Actualizar Clave
            </button>
            <a href="<?= e(APP_URL) ?>/perfil" class="btn btn-secondary ms-2">Cancelar</a>
        </form>
    </div>
</div>
</div>
</div>

<script>
function toggleActual() {
    var f = document.getElementById('clave_actual');
    var e = document.getElementById('eye-actual');
    f.type = f.type === 'password' ? 'text' : 'password';
    e.classList.toggle('bi-eye'); e.classList.toggle('bi-eye-slash');
}
function toggleNew() {
    const input = document.getElementById('nueva_clave');
    const eye = document.getElementById('eye-new');
    input.type = (input.type === 'password') ? 'text' : 'password';
    eye.className = (input.type === 'text') ? 'bi bi-eye-slash' : 'bi bi-eye';
}
function toggleConfirm() {
    var f = document.getElementById('confirm_clave');
    var e = document.getElementById('eye-confirm');
    f.type = f.type === 'password' ? 'text' : 'password';
    e.className = f.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
document.getElementById('nueva_clave').addEventListener('input', function () {
    const v = this.value;
    const bar = document.getElementById('strength-bar');
    const fill = document.getElementById('strength-fill');
    bar.style.display = v ? 'flex' : 'none';
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[\W_]/.test(v)) score++;
    const colors = ['bg-danger','bg-warning','bg-info','bg-success'];
    fill.style.width = (score * 25) + '%';
    fill.className = 'progress-bar ' + (colors[score - 1] || 'bg-danger');
});
</script>
