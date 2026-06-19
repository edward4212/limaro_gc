<?php
$emp       = empresa();
$empNombre = $emp['nombre_empresa'] ?: 'SGC';
$empLogo   = empresaLogoUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña — <?= e($empNombre) ?></title>
    <?php if ($empLogo): ?>
    <link rel="icon" type="image/png" href="<?= e($empLogo) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { min-height: 100vh; background: #0f1e38; display: flex; align-items: center; justify-content: center; }
        .bg-shapes { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
        .bg-circle1 { position: absolute; width: 600px; height: 600px; border-radius: 50%; background: rgba(0,181,216,0.07); top: -200px; right: -150px; }
        .bg-circle2 { position: absolute; width: 400px; height: 400px; border-radius: 50%; background: rgba(27,58,107,0.5); bottom: -120px; left: -100px; }
        .bg-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px); background-size: 32px 32px; }
        .wrap { position: relative; z-index: 1; width: 100%; max-width: 420px; padding: 16px; }
        .card { background: rgba(255,255,255,0.05); border: 0.5px solid rgba(255,255,255,0.12); border-radius: 20px; padding: 36px 32px; }
        .logo-wrap { width: 64px; height: 64px; border-radius: 16px; background: rgba(255,255,255,0.08); border: 0.5px solid rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto 16px; }
        .logo-wrap img { width: 46px; height: 46px; object-fit: contain; }
        .logo-inicial { font-size: 28px; font-weight: 700; color: #fff; }
        h2 { font-size: 18px; font-weight: 600; color: #fff; text-align: center; margin-bottom: 6px; }
        .sub { font-size: 13px; color: rgba(255,255,255,0.45); text-align: center; margin-bottom: 24px; }
        .lbl { font-size: 12px; color: rgba(255,255,255,0.55); margin-bottom: 5px; }
        .inp { width: 100%; background: rgba(255,255,255,0.06); border: 0.5px solid rgba(255,255,255,0.14); border-radius: 10px; padding: 10px 14px; font-size: 14px; color: #fff; outline: none; }
        .inp:focus { border-color: #00B5D8; box-shadow: 0 0 0 3px rgba(0,181,216,0.15); }
        .inp::placeholder { color: rgba(255,255,255,0.3); }
        .btn-enviar { background: #00B5D8; border: none; border-radius: 10px; padding: 12px; width: 100%; font-size: 14px; font-weight: 600; color: #fff; cursor: pointer; margin-top: 8px; }
        .btn-enviar:hover { background: #0097b8; }
        .link-volver { display: block; text-align: center; margin-top: 16px; font-size: 12px; color: rgba(255,255,255,0.4); text-decoration: none; }
        .link-volver:hover { color: #00B5D8; }
        .alert-ok { background: rgba(34,197,94,0.15); border: 0.5px solid rgba(34,197,94,0.3); color: #86efac; border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
        .alert-err { background: rgba(239,68,68,0.15); border: 0.5px solid rgba(239,68,68,0.3); color: #fca5a5; border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .card { animation: fadeUp 0.4s ease forwards; }
    </style>
</head>
<body>
<div class="bg-shapes"><div class="bg-circle1"></div><div class="bg-circle2"></div><div class="bg-grid"></div></div>
<div class="wrap">
    <div class="card">
        <div class="logo-wrap">
            <?php if ($empLogo): ?>
                <img src="<?= e($empLogo) ?>" alt="<?= e($empNombre) ?>">
            <?php else: ?>
                <span class="logo-inicial"><?= e(mb_strtoupper(mb_substr($empNombre,0,1,'UTF-8'),'UTF-8')) ?></span>
            <?php endif; ?>
        </div>
        <h2>Recuperar Contraseña</h2>
        <p class="sub">Ingresa tu usuario o correo y notificaremos<br>al administrador para restablecer tu acceso.</p>

        <?php $flash = \App\Core\Session::getFlash('success'); if ($flash): ?>
        <div class="alert-ok"><i class="bi bi-check-circle me-2"></i><?= e($flash) ?></div>
        <?php endif; ?>
        <?php $flashErr = \App\Core\Session::getFlash('error'); if ($flashErr): ?>
        <div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i><?= e($flashErr) ?></div>
        <?php endif; ?>

        <?php if (empty($flash)): ?>
        <form method="POST" action="<?= e(APP_URL) ?>/recuperar-clave" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= e(\App\Core\Csrf::token()) ?>">
            <div style="margin-bottom:14px;">
                <div class="lbl">Usuario o correo electrónico</div>
                <input type="text" name="identificador" class="inp"
                       placeholder="Ej: jperez o jperez@coopaipe.com.co"
                       value="<?= e($_POST['identificador'] ?? '') ?>"
                       required autofocus autocomplete="username">
            </div>
            <button type="submit" class="btn-enviar">
                <i class="bi bi-envelope me-2"></i>Enviar solicitud
            </button>
        </form>
        <?php endif; ?>

        <a href="<?= e(APP_URL) ?>/login" class="link-volver">
            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
        </a>
    </div>
    <div style="text-align:center;margin-top:16px;font-size:11px;color:rgba(255,255,255,0.2);">
        Desarrollado por <a href="https://limaro.cloud" target="_blank" style="color:rgba(0,181,216,0.5);">Limaro Software</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
