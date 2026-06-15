<?php
use App\Core\Auth;

$logo    = empresaLogoUrl();
$orgUrl  = !empty($empresa['organigrama']) && str_starts_with($empresa['organigrama'], '/storage/')
           ? APP_URL . '/empresa-img/organigrama' : null;
$mapaUrl = !empty($empresa['mapa_procesos']) && str_starts_with($empresa['mapa_procesos'], '/storage/')
           ? APP_URL . '/empresa-img/mapa' : null;
$orgEsPdf  = $orgUrl  && str_ends_with(strtolower($empresa['organigrama']),  '.pdf');
$mapaEsPdf = $mapaUrl && str_ends_with(strtolower($empresa['mapa_procesos']), '.pdf');
?>

<!-- Encabezado empresa -->
<div class="card border-0 shadow-sm mb-4"
                 style="border-left:4px solid var(--lim-blue) !important;">
    <div class="card-body py-4">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <?php if ($logo): ?>
            <img src="<?= e($logo) ?>" alt="Logo" style="max-height:72px;max-width:200px;object-fit:contain;">
            <?php endif; ?>
            <div>
                <h2 class="mb-0 fw-bold" style="color:var(--lim-blue);">
                    <?= e($empresa['nombre_empresa'] ?? 'Bienvenido') ?>
                </h2>
                <?php if ($empresa['URL'] ?? null): ?>
                <a href="<?= e($empresa['URL']) ?>" target="_blank"
                   class="text-muted" style="font-size:13px;">
                    <i class="bi bi-globe me-1"></i><?= e($empresa['URL']) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Columna izquierda: misión, visión, política -->
    <div class="col-lg-7">

        <?php if ($empresa['mision'] ?? null): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pb-0 pt-3"
                 style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-flag me-1"></i>Misión
                </h6>
            </div>
            <div class="card-body pt-2">
                <p class="mb-0" style="font-size:14px;line-height:1.7;color:#374151;">
                    <?= nl2br(e($empresa['mision'])) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($empresa['vision'] ?? null): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pb-0 pt-3" style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-eye me-1"></i>Visión
                </h6>
            </div>
            <div class="card-body pt-2">
                <p class="mb-0" style="font-size:14px;line-height:1.7;color:#374151;">
                    <?= nl2br(e($empresa['vision'])) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($empresa['politica_calidad'] ?? null): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pb-0 pt-3" style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-shield-check me-1"></i>Política de Calidad
                </h6>
            </div>
            <div class="card-body pt-2">
                <p class="mb-0" style="font-size:14px;line-height:1.7;color:#374151;">
                    <?= nl2br(e($empresa['politica_calidad'])) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($empresa['objetivos_calidad'] ?? null): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pb-0 pt-3" style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-bullseye me-1"></i>Objetivos de Calidad
                </h6>
            </div>
            <div class="card-body pt-2">
                <p class="mb-0" style="font-size:14px;line-height:1.7;color:#374151;">
                    <?= nl2br(e($empresa['objetivos_calidad'])) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
                <!-- Objetivos SGC con cumplimiento -->
        <?php if (!empty($objetivos)): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pt-3 pb-0 d-flex align-items-center justify-content-between"
                 style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-bar-chart-line me-1"></i>Seguimiento Objetivos §6.2
                </h6>
                <a href="<?= e(APP_URL) ?>/reportes/sgc/objetivos"
                   class="btn btn-sm btn-outline-primary py-0 px-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Reporte
                </a>
            </div>
            <div class="px-3 pt-2 pb-0">
                <small class="text-muted" style="font-size:10.5px;">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    Fórmula: <strong>Mediciones cumplidas ÷ Total × 100</strong>
                    — idéntica al Reporte §6.2
                </small>
            </div>
            <div class="card-body pt-2 p-0">
                <ul class="list-group list-group-flush">
                <?php foreach ($objetivos as $o):
                    $pct      = ($o['pct_cumplimiento'] !== null) ? round((float)$o['pct_cumplimiento'],1) : null;
                    $color    = $pct === null ? 'secondary' : ($pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'));
                    $totalMed = (int)($o['total_mediciones'] ?? 0);
                ?>
                <li class="list-group-item border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                        <span style="font-size:12px;font-weight:600;flex:1;">
                            <?= e(truncar($o['objetivo'], 500)) ?>
                        </span>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <span class="badge bg-primary" style="font-size:10px;">
                                Meta: <?= e($o['meta'] ?? '—') ?>
                            </span>
                            <span class="badge bg-light text-dark border"
                                  style="font-size:10px;"><?= $totalMed ?> med.</span>
                        </div>
                    </div>
                    <?php if ($pct !== null && $totalMed > 0): ?>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar bg-<?= $color ?>"
                                 style="width:<?= min($pct,100) ?>%;"></div>
                        </div>
                        <span class="fw-bold text-<?= $color ?>"
                              style="font-size:15px;min-width:46px;text-align:right;">
                            <?= $pct ?>%
                        </span>
                    </div>
                    <?php else: ?>
                    <small class="text-muted fst-italic" style="font-size:11px;">
                        Sin mediciones registradas
                    </small>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
                </ul>
                <?php
                $con = array_filter($objetivos, fn($o) => $o['pct_cumplimiento'] !== null && ($o['total_mediciones']??0)>0);
                if (!empty($con)):
                    $prom = round(array_sum(array_column($con,'pct_cumplimiento'))/count($con),1);
                    $cg   = $prom>=80?'success':($prom>=50?'warning':'danger');
                ?>
                <div class="px-3 py-2 border-top" style="background:#f8fafc;">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted fw-semibold" style="font-size:11px;">
                            Cumplimiento Promedio Global
                        </small>
                        <strong class="text-<?= $cg ?>" style="font-size:16px;"><?= $prom ?>%</strong>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Columna derecha: organigrama + mapa -->
    <div class="col-lg-5">

        <!-- Organigrama -->
        <?php if ($orgUrl): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pb-0 pt-3" style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-diagram-2 me-1"></i>Organigrama
                </h6>
            </div>
            <div class="card-body pt-2 text-center">
                <?php if ($orgEsPdf): ?>
                <a href="<?= e($orgUrl) ?>" target="_blank"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-pdf me-1"></i>Ver Organigrama (PDF)
                </a>
                <?php else: ?>
                <a href="<?= e($orgUrl) ?>" target="_blank">
                    <img src="<?= e($orgUrl) ?>" alt="Organigrama"
                         class="img-fluid rounded" style="max-height:280px;object-fit:contain;">
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mapa de Procesos -->
        <?php if ($mapaUrl): ?>
        <div class="card border-0 shadow-sm mb-4"
             style="border-left:4px solid var(--lim-blue) !important;">
            <div class="card-header border-0 pb-0 pt-3" style="background:none;">
                <h6 class="text-uppercase fw-bold mb-0"
                    style="color:var(--lim-blue);font-size:18px;letter-spacing:1px;">
                    <i class="bi bi-map me-1"></i>Mapa de Procesos
                </h6>
            </div>
            <div class="card-body pt-2 text-center">
                <?php if ($mapaEsPdf): ?>
                <a href="<?= e($mapaUrl) ?>" target="_blank"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-pdf me-1"></i>Ver Mapa de Procesos (PDF)
                </a>
                <?php else: ?>
                <a href="<?= e($mapaUrl) ?>" target="_blank">
                    <img src="<?= e($mapaUrl) ?>" alt="Mapa de Procesos"
                         class="img-fluid rounded" style="max-height:280px;object-fit:contain;">
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>