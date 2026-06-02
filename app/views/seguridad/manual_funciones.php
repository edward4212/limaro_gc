<div class="page-header">
    <div><h2><i class="bi bi-book me-2"></i>Manual de Funciones por Cargo</h2></div>
</div>
<div class="row g-3">
    <?php foreach ($cargos as $c): ?>
    <div class="col-md-4 col-lg-3">
        <div class="card h-100 text-center">
            <div class="card-body d-flex flex-column align-items-center py-4">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mb-3"
                     style="width:56px;height:56px;">
                    <i class="bi bi-person-badge text-white fs-4"></i>
                </div>
                <h6 class="card-title fw-bold" style="font-size:13px;"><?= e($c['cargo']) ?></h6>
                <p class="text-muted small"><?= e($c['total_empleados']) ?> empleado(s)</p>
                <?= badgeEstado($c['estado']) ?>
            </div>
            <div class="card-footer">
                <?php if ($c['id_archivo'] ?? null): ?>
                <a href="<?= e(APP_URL) ?>/archivo/<?= $c['id_archivo'] ?>"
                   class="btn btn-sm btn-lim-primary w-100">
                    <i class="bi bi-download me-1"></i>Descargar Manual
                </a>
                <?php else: ?>
                <span class="text-muted small">Sin manual cargado</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
