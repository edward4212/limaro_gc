<?php
$emp        = empresa();
$empNombre  = $emp['nombre_empresa'] ?: 'SGC';
$empLogo    = empresaLogoUrl();
$empUrl     = $emp['URL'] ?? '';
$empInicial = mb_strtoupper(mb_substr($empNombre, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — <?= e($empNombre) ?></title>
    <?php if ($empLogo): ?>
    <link rel="icon" type="image/png" href="<?= e($empLogo) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0f1e38;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* ── Fondo con formas ── */
        .bg-shapes { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
        .bg-circle1 {
            position: absolute; width: 600px; height: 600px; border-radius: 50%;
            background: rgba(0,181,216,0.07); top: -200px; right: -150px;
        }
        .bg-circle2 {
            position: absolute; width: 400px; height: 400px; border-radius: 50%;
            background: rgba(27,58,107,0.5); bottom: -120px; left: -100px;
        }
        .bg-circle3 {
            position: absolute; width: 200px; height: 200px; border-radius: 50%;
            background: rgba(0,181,216,0.05); top: 40%; left: 20%;
        }
        .bg-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* ── Tarjeta principal ── */
        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            padding: 16px;
        }
        .login-card {
            background: rgba(255,255,255,0.05);
            border: 0.5px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ── Cabecera ── */
        .login-header { display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .login-logo-wrap {
            width: 80px; height: 80px; border-radius: 16px;
            background: #fff;
            border: none;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            padding: 6px;
        }
        .login-logo-wrap img { width: 100%; height: 100%; object-fit: contain; }
        .login-logo-initials {
            font-size: 32px; font-weight: 700; color: #fff;
        }
        .login-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(0,181,216,0.15);
            border: 0.5px solid rgba(0,181,216,0.3);
            border-radius: 20px; padding: 3px 10px;
        }
        .login-badge-dot {
            width: 6px; height: 6px; border-radius: 50%; background: #00B5D8;
        }
        .login-badge span { font-size:12px; color: #00B5D8; font-weight: 500; letter-spacing: 0.5px; }
        .login-emp-nombre {
            font-size: 18px; font-weight: 600; color: #fff;
            text-align: center; line-height: 1.3;
        }
        .login-emp-sub { font-size:12px; color: rgba(255,255,255,0.4); text-align: center; }

        /* ── Formulario ── */
        .login-form { display: flex; flex-direction: column; gap: 14px; }
        .login-lbl { font-size:12px; color: rgba(255,255,255,0.55); margin-bottom: 5px; letter-spacing: 0.3px; }
        .login-input {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 0.5px solid rgba(255,255,255,0.14);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px; color: #fff;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
        }
        .login-input::placeholder { color: rgba(255,255,255,0.3); }
        .login-input:focus {
            border-color: #00B5D8;
            background: rgba(255,255,255,0.09);
            box-shadow: 0 0 0 3px rgba(0,181,216,0.15);
        }
        .login-input-wrap { position: relative; }
        .login-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: rgba(255,255,255,0.35);
            cursor: pointer; padding: 0; font-size: 16px;
            transition: color 0.2s;
        }
        .login-eye:hover { color: rgba(255,255,255,0.7); }
        .login-row { display: flex; justify-content: space-between; align-items: center; }
        .login-check {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: rgba(255,255,255,0.45); cursor: pointer;
        }
        .login-check input { accent-color: #00B5D8; }
        .login-forgot { font-size: 12px; color: #00B5D8; text-decoration: none; }
        .login-forgot:hover { color: #33c4e2; }
        .login-btn {
            background: #00B5D8;
            border: none; border-radius: 10px;
            padding: 12px; width: 100%;
            font-size: 14px; font-weight: 600; color: #fff;
            font-family: 'Inter', sans-serif;
            cursor: pointer; letter-spacing: 0.3px;
            transition: background 0.2s, transform 0.15s;
        }
        .login-btn:hover { background: #0097b8; transform: translateY(-1px); }
        .login-btn:active { transform: translateY(0); }
        .login-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ── Flash messages ── */
        .login-alert {
            border-radius: 10px; padding: 10px 14px; font-size: 13px;
            display: flex; align-items: center; gap: 8px;
        }
        .login-alert.error { background: rgba(239,68,68,0.15); border: 0.5px solid rgba(239,68,68,0.3); color: #fca5a5; }
        .login-alert.success { background: rgba(34,197,94,0.15); border: 0.5px solid rgba(34,197,94,0.3); color: #86efac; }

        /* ── Footer ── */
        .login-footer { text-align: center; font-size:12px; color: rgba(255,255,255,0.2); }
        .login-footer a { color: rgba(0,181,216,0.5); text-decoration: none; }
        .login-footer a:hover { color: #00B5D8; }

        /* ── Animación entrada ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-card { animation: fadeUp 0.5s ease forwards; }
    </style>
</head>
<body>

<!-- Fondo -->
<div class="bg-shapes">
    <div class="bg-circle1"></div>
    <div class="bg-circle2"></div>
    <div class="bg-circle3"></div>
    <div class="bg-grid"></div>
</div>

<!-- Tarjeta -->
<div class="login-wrap">
    <div class="login-card">

        <!-- Cabecera con datos de la empresa -->
        <div class="login-header">
            <div class="login-logo-wrap">
                <?php if ($empLogo): ?>
                    <img src="<?= e($empLogo) ?>" alt="<?= e($empNombre) ?>"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                    <span class="login-logo-initials" style="display:none;"><?= e($empInicial) ?></span>
                <?php else: ?>
                    <span class="login-logo-initials"><?= e($empInicial) ?></span>
                <?php endif; ?>
            </div>
            <div class="login-badge">
                <div class="login-badge-dot"></div>
                <span>Sistema de Gestión Integrado (SGI)</span>
            </div>
            <div class="login-emp-nombre"><?= e($empNombre) ?></div>
            <div class="login-emp-sub"></div>
        </div>

        <!-- Flash messages -->
        <?php
        $flashError   = \App\Core\Session::getFlash('error');
        $flashSuccess = \App\Core\Session::getFlash('success');
        ?>
        <?php if ($flashError): ?>
        <div class="login-alert error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= e($flashError) ?>
        </div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
        <div class="login-alert success">
            <i class="bi bi-check-circle-fill"></i>
            <?= e($flashSuccess) ?>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form action="<?= e(APP_URL) ?>/login" method="POST" novalidate autocomplete="off"
              class="login-form" id="loginForm">
            <?= csrfField() ?>

            <div>
                <div class="login-lbl">Usuario</div>
                <input type="text" name="usuario" id="usuario" class="login-input"
                       placeholder="Ingresa tu usuario"
                       value="<?= e($_POST['usuario'] ?? '') ?>"
                       required autofocus autocomplete="username">
            </div>

            <div>
                <div class="login-lbl">Contraseña</div>
                <div class="login-input-wrap">
                    <input type="password" name="clave" id="clave" class="login-input"
                           placeholder="••••••••" style="padding-right:40px;"
                           required autocomplete="current-password">
                    <button type="button" class="login-eye" id="toggleClave" tabindex="-1"
                            aria-label="Mostrar/ocultar contraseña">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="login-row">
                <label class="login-check">
                    <input type="checkbox" name="recordar" value="1"> Recordarme
                </label>
                <a href="<?= e(APP_URL) ?>/recuperar-clave" class="login-forgot">
                    ¿Olvidaste tu clave?
                </a>
            </div>

            <button type="submit" class="login-btn" id="btnIngresar">
                Ingresar al sistema
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            Desarrollado por <a href="https://limaro.cloud" target="_blank">Limaro Software</a>
            · v3.0
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
// Toggle contraseña
document.getElementById('toggleClave').addEventListener('click', function () {
    var inp  = document.getElementById('clave');
    var icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

// Deshabilitar botón al enviar (evitar doble submit)
document.getElementById('loginForm').addEventListener('submit', function () {
    var btn = document.getElementById('btnIngresar');
    btn.disabled = true;
    btn.textContent = 'Ingresando...';
});
</script>
</body>
</html>
