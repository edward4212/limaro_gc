<?php $r = $resumen; $doc = $r['documentos']; $sol = $r['solicitudes']; $sgc = $r['sgc']; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-bar-chart-line me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Reportes disponibles para todos los módulos del SGC</small>
    </div>
</div>

<!-- KPIs rápidos -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-blue">
            <div class="kpi-num"><?= e($doc['vigentes'] ?? 0) ?></div>
            <div class="kpi-label">Documentos Vigentes</div>
            <i class="bi bi-file-earmark-check kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-orange">
            <div class="kpi-num"><?= e($sol['en_desarrollo'] ?? 0) ?></div>
            <div class="kpi-label">Solicitudes en Desarrollo</div>
            <i class="bi bi-hourglass-split kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-teal">
            <div class="kpi-num"><?= e($sgc['pct_cumplimiento_obj'] ?? '—') ?>%</div>
            <div class="kpi-label">Cumplimiento Objetivos</div>
            <i class="bi bi-bullseye kpi-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-<?= ($sgc['ac_vencidas'] ?? 0) > 0 ? 'red' : 'green' ?>">
            <div class="kpi-num"><?= e($sgc['ac_vencidas'] ?? 0) ?></div>
            <div class="kpi-label">Acciones Correctivas Vencidas</div>
            <i class="bi bi-exclamation-triangle kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Catálogo de reportes -->
<?php
$grupos = [
    ['icono'=>'bi-file-earmark-text','titulo'=>'Documentos','color'=>'primary','reportes'=>[
        ['/reportes/documentos/vigentes',    'bi-list-check',    'Listado Maestro Vigentes',     'Todos los documentos con versión vigente. Con filtros por proceso, tipo y fecha.'],
        ['/reportes/documentos/obsoletos',   'bi-archive',       'Documentos Obsoletos',         'Historial de versiones que pasaron a obsoleto por período.'],
        ['/reportes/documentos/por-proceso', 'bi-diagram-3',     'Documentos por Proceso',       'Tabla resumen: total, vigentes, obsoletos y en creación por cada proceso.'],
        ['/reportes/documentos/versionamiento','bi-layers',      'Historial de Versionamiento',  'Trazabilidad completa de cambios por documento y período.'],
    ]],
    ['icono'=>'bi-inbox','titulo'=>'Solicitudes y Tareas','color'=>'warning','reportes'=>[
        ['/reportes/solicitudes',  'bi-clipboard-data', 'Solicitudes por Estado/Período', 'Listado de solicitudes con tiempos de resolución y responsables.'],
        ['/reportes/tareas',       'bi-list-task',      'Estado de Tareas',               'Flujo elaborar/revisar/aprobar con estado actual por solicitud.'],
    ]],
    ['icono'=>'bi-award','titulo'=>'SGC — Calidad','color'=>'success','reportes'=>[
        ['/reportes/sgc/ejecutivo',           'bi-speedometer2',  'Resumen Ejecutivo SGC',         'Vista consolidada del estado del sistema de gestión de calidad.'],
        ['/reportes/sgc/objetivos',           'bi-bullseye',      'Cumplimiento Objetivos §6.2',   'Porcentaje de cumplimiento por objetivo con histórico de mediciones.'],
        ['/reportes/sgc/hallazgos',           'bi-exclamation-triangle','Hallazgos Auditoría §9.2','NC, observaciones y oportunidades con estado de cierre.'],
        ['/reportes/sgc/acciones-correctivas','bi-tools',         'Acciones Correctivas §10.2',    'Estado, vencimiento y eficacia de todas las acciones correctivas.'],
    ]],
    ['icono'=>'bi-shield-lock','titulo'=>'Seguridad','color'=>'secondary','reportes'=>[
        ['/reportes/seguridad/logins', 'bi-key', 'Auditoría de Accesos', 'Registro de todos los intentos de login (exitosos y fallidos) con IP y fecha.'],
    ]],
];
?>
<?php foreach ($grupos as $g): ?>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="<?= $g['icono'] ?> text-<?= $g['color'] ?> fs-5"></i>
        <strong><?= $g['titulo'] ?></strong>
    </div>
    <div class="row g-0">
        <?php foreach ($g['reportes'] as [$url, $ico, $titulo, $desc]): ?>
        <div class="col-md-6 border-bottom border-end">
            <a href="<?= e(APP_URL.$url) ?>" class="d-flex align-items-start gap-3 p-3 text-decoration-none text-dark reporte-link">
                <div class="mt-1">
                    <i class="<?= $ico ?> fs-4 text-<?= $g['color'] ?>"></i>
                </div>
                <div>
                    <div class="fw-semibold" style="font-size:14px;"><?= $titulo ?></div>
                    <div class="text-muted" style="font-size:12px;"><?= $desc ?></div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<style>
.reporte-link:hover { background:#f0f6ff; }
.kpi-red .kpi-num { color:#dc3545; }
.kpi-red { border-left-color:#dc3545 !important; }
</style>
