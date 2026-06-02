<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>404 — Página no encontrada</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="text-center">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <h3>Página no encontrada</h3>
        <p class="text-muted">La ruta solicitada no existe en el sistema.</p>
        <a href="<?= defined('APP_URL') ? e(APP_URL) : '' ?>/inicio" class="btn btn-primary">
            Volver al inicio
        </a>
    </div>
</body>
</html>
