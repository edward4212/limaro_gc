<div class="page-header">
    <div><h2><i class="bi bi-gear-fill me-2"></i>Configuración del Sistema</h2></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-envelope-fill text-primary"></i> Configuración de Correo SMTP
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    La configuración SMTP se gestiona en el archivo <span>.env</span> del servidor.
                    Aquí puede verificar la configuración actual y enviar un correo de prueba.
                </p>

                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th style="width:180px">Servidor SMTP</th><td><span><?= e($smtpHost) ?: '<span class="text-danger">No configurado</span>' ?></span></td></tr>
                        <tr><th>Puerto</th><td><span><?= e($smtpPort) ?></span></td></tr>
                        <tr><th>Cifrado</th><td><span><?= strtoupper(e($smtpEnc)) ?></span></td></tr>
                        <tr><th>Usuario</th><td><span><?= e($smtpUser) ?: '<span class="text-danger">No configurado</span>' ?></span></td></tr>
                        <tr><th>Remitente</th><td><?= e($mailFromName) ?> &lt;<?= e($mailFrom) ?>&gt;</td></tr>
                    </tbody>
                </table>

                <hr>
                <h6 class="fw-semibold mb-3">Enviar correo de prueba</h6>
                <form method="POST" action="<?= e(APP_URL) ?>/configuracion/correo">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <input type="email" class="form-control" name="test_email"
                               placeholder="correo@ejemplo.com" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Enviar prueba
                        </button>
                    </div>
                    <div class="form-text">Se enviará un correo de prueba a esa dirección.</div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-info"></i> Información del Sistema
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr><th>Versión SGC</th><td><?= defined('APP_VERSION') ? e(APP_VERSION) : 'V3.0' ?></td></tr>
                        <tr><th>PHP</th><td><?= PHP_VERSION ?></td></tr>
                        <tr><th>Ambiente</th><td><?= defined('APP_ENV') ? e(APP_ENV) : 'production' ?></td></tr>
                        <tr><th>Zona horaria</th><td><?= defined('APP_TIMEZONE') ? e(APP_TIMEZONE) : 'America/Bogota' ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
