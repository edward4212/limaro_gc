<?php
use App\Core\Auth;
$userName = Auth::get('nombre_completo') ?: Auth::get('usuario');
$emp      = empresa();
$logoUrl  = empresaLogoUrl();

// Url organigrama y mapa procesos
$orgUrl  = !empty($emp['organigrama'])
    ? (str_starts_with($emp['organigrama'], '/storage/')
        ? APP_URL . '/public' . $emp['organigrama']
        : null)
    : null;
$mapaUrl = !empty($emp['mapa_procesos'])
    ? (str_starts_with($emp['mapa_procesos'], '/storage/')
        ? APP_URL . '/public' . $emp['mapa_procesos']
        : null)
    : null;
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-house me-2"></i>Inicio</h2>
        <p class="text-muted mb-0" style="font-size:13px;">
            Bienvenido, <strong><?= e($userName) ?></strong>
            &nbsp;·&nbsp;<?= fechaEs(date('Y-m-d'), 'largo') ?>
        </p>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-blue">
            <div class="kpi-num"><?= e($kpis['documentos_vigentes']) ?></div>
            <div class="kpi-label">Documentos Vigentes</div>
            <i class="bi bi-file-earmark-check kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-orange">
            <div class="kpi-num"><?= e($kpis['solicitudes_desarrollo']) ?></div>
            <div class="kpi-label">Solicitudes en Desarrollo</div>
            <i class="bi bi-hourglass-split kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-teal">
            <div class="kpi-num"><?= e($kpis['tareas_pendientes']) ?></div>
            <div class="kpi-label">Mis Tareas Pendientes</div>
            <i class="bi bi-list-task kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-green">
            <div class="kpi-num"><?= e($kpis['solicitudes_total']) ?></div>
            <div class="kpi-label">Total Solicitudes</div>
            <i class="bi bi-inbox kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Datos empresa + Gráfico -->
<div class="row g-3 mb-4">

    <!-- Datos de la empresa -->
    <div class="col-lg-7">
        <div class="card h-100">
            <!--<div class="card-header d-flex align-items-center gap-2">-->
            <!--    <i class="bi bi-building text-primary"></i>-->
            <!--    <strong><?= e($emp['nombre_empresa'] ?? 'Datos de la Empresa') ?></strong>-->
            <!--    <?php if ($emp['URL'] ?? null): ?>-->
            <!--    <a href="<?= e($emp['URL']) ?>" target="_blank"-->
            <!--       class="ms-auto btn btn-sm btn-outline-primary py-0">-->
            <!--        <i class="bi bi-globe me-1"></i>Sitio web-->
            <!--    </a>-->
            <!--    <?php endif; ?>-->
            <!--</div>-->
            <div class="card-body">
                <div class="row g-3">
                    <?php if ($logoUrl): ?>
                    <div class="col-auto">
                        <img src="<?= e($logoUrl) ?>" alt="Logo"
                             style="max-height:64px; max-width:140px; object-fit:contain;">
                    </div>
                    <?php endif; ?>

                    <div class="col">
                        <?php if ($emp['mision'] ?? null): ?>
                        <div class="mb-2">
                            <div class="fw-semibold" style="font-size:11px;text-transform:uppercase;color:#6c757d;letter-spacing:.5px;">Misión</div>
                            <p class="mb-0" style="font-size:13px;"><?= e($emp['mision']) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($emp['vision'] ?? null): ?>
                        <div class="mb-2">
                            <div class="fw-semibold" style="font-size:11px;text-transform:uppercase;color:#6c757d;letter-spacing:.5px;">Visión</div>
                            <p class="mb-0" style="font-size:13px;"><?= e($emp['vision']) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($emp['politica_calidad'] ?? null): ?>
                        <div class="mb-2">
                            <div class="fw-semibold" style="font-size:11px;text-transform:uppercase;color:#6c757d;letter-spacing:.5px;">Política de Calidad</div>
                            <p class="mb-0" style="font-size:13px;"><?= e(truncar($emp['politica_calidad'], 200)) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Organigrama y Mapa de Procesos -->
                <?php if ($orgUrl || $mapaUrl): ?>
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if ($orgUrl): ?>
                    <a href="<?= e($orgUrl) ?>" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-diagram-2 me-1"></i>Organigrama
                    </a>
                    <?php endif; ?>
                    <?php if ($mapaUrl): ?>
                    <a href="<?= e($mapaUrl) ?>" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-map me-1"></i>Mapa de Procesos
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráfico solicitudes -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart text-primary"></i>
                Solicitudes por Estado
            </div>
            <div class="card-body d-flex align-items-center justify-content-center"
                 style="min-height:220px;">
                <?php if (empty($estadosSol)): ?>
                <p class="text-muted">Sin datos aún.</p>
                <?php else: ?>
                <canvas id="chartEstados" style="max-height:200px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Últimas solicitudes -->
<?php if (!empty($ultimas)): ?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Últimas Solicitudes</span>
        <a href="<?= e(APP_URL) ?>/solicitudes/mis-radicadas"
           class="btn btn-sm btn-lim-primary">Ver todas</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead>
                <tr>
                    <th>#</th><th>Tipo</th><th>Solicitante</th>
                    <th>Estado</th><th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimas as $s): ?>
                <tr>
                    <td><?= e($s['id_solicitud']) ?></td>
                    <td><?= e($s['tipo_solicitud']) ?></td>
                    <td><?= e($s['nombre_completo'] ?? '—') ?></td>
                    <td><?= badgeEstado($s['estado_solicitud']) ?></td>
                    <td><?= fechaEs($s['fecha_solicitud']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($estadosSol)): ?>
<?php
$labels  = array_column($estadosSol, 'estado');
$valores = array_column($estadosSol, 'total');
$colors  = ['#1e5fbf','#ff7a00','#198754','#0d9488','#dc3545','#6c757d'];
?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var ctx = document.getElementById('chartEstados');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($valores) ?>,
                backgroundColor: <?= json_encode(array_slice($colors, 0, count($labels))) ?>,
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 } } }
            }
        }
    });
});
</script>
<?php endif; ?>
