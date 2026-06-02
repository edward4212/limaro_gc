<?php
$emp       = empresa();
$empNombre = $emp['nombre_empresa'] ?: 'Limaro SGC';
$empLogo   = empresaLogoUrl();
$empInicial = mb_strtoupper(mb_substr($empNombre, 0, 1));
?>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <?php if ($empLogo): ?>
                <img src="<?= e($empLogo) ?>"
                     alt="<?= e($empNombre) ?>"
                     style="width:70px;height:70px;object-fit:contain;border-radius:16px;background:#0f2d5e;padding:6px;">
            <?php else: ?>
                <svg width="70" height="70" viewBox="0 0 70 70" fill="none" aria-label="<?= e($empNombre) ?>">
                    <rect width="70" height="70" rx="16" fill="#0f2d5e"/>
                    <text x="9" y="52" font-family="sans-serif" font-weight="900"
                          font-size="44" fill="#ffffff"><?= e($empInicial) ?></text>
                    <circle cx="55" cy="18" r="9" fill="#ff7a00"/>
                    <circle cx="55" cy="18" r="5" fill="#fff" opacity=".3"/>
                </svg>
            <?php endif; ?>
            <h1><?= e($empNombre) ?></h1>
            <p>Sistema de Gestión Documental · ISO 9001:2015</p>
        </div>

        <?php include APP_ROOT . '/app/views/partials/flash.php'; ?>

        <form action="<?= e(APP_URL) ?>/login" method="POST" novalidate autocomplete="off">
            <?= csrfField() ?>

            <div class="mb-3">
                <label for="usuario" class="form-label">
                    <i class="bi bi-person me-1"></i>Usuario
                </label>
                <input type="text"
                       class="form-control"
                       id="usuario"
                       name="usuario"
                       value="<?= old('usuario') ?>"
                       placeholder="Ingrese su usuario"
                       required
                       autocomplete="username">
            </div>

            <div class="mb-4">
                <label for="clave" class="form-label">
                    <i class="bi bi-lock me-1"></i>Clave
                </label>
                <div class="input-group">
                    <input type="password"
                           class="form-control"
                           id="clave"
                           name="clave"
                           placeholder="Ingrese su clave"
                           required
                           autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePass()">
                        <i class="bi bi-eye" id="toggle-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-lim-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
            </button>
        </form>

        <p class="text-center text-muted mt-4 mb-0" style="font-size:11px;">
            <?= e($empNombre) ?> v1.0 &copy; <?= date('Y') ?><br>
            <span>ISO 9001:2015 Información Documentada</span>
        </p>
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('clave');
    const eye   = document.getElementById('toggle-eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'bi bi-eye';
    }
}
</script>
