<?php
$emp       = empresa();
$empNombre = $emp['nombre_empresa'] ?: 'Limaro SGC';
$empLogo   = empresaLogoUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — <?= e($empNombre) ?></title>
    <?php if ($empLogo): ?>
        <link rel="icon" type="image/png" href="<?= e($empLogo) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/app.css">
</head>
<body>
<?= $content ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
