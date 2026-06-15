<div class="page-header">
    <div><h2><i class="bi bi-people me-2"></i><?= e($pageTitle) ?></h2></div>
    <div class="d-flex gap-2">
        <?php if (!empty($item['archivo_acta'])): ?>
        <a href="<?= e(APP_URL) ?>/archivo/acta/<?= (int)$item['id'] ?>?inline=1" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-pdf me-1"></i>Ver Acta
        </a>
        <?php endif; ?>
        <?php if (Auth::puede('revision_direccion','editar')): ?>
        <a href="<?= e(APP_URL) ?>/revision-direccion/editar/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= e(APP_URL) ?>/revision-direccion" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>
<div class="row g-4">
<div class="col-lg-4">
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0" style="font-size:13px;">
            <dt class="col-5">Año</dt><dd class="col-7"><?= e($item['anio']) ?></dd>
            <dt class="col-5">Fecha</dt><dd class="col-7"><?= fechaEs($item['fecha_revision']) ?></dd>
            <dt class="col-5">Convocó</dt><dd class="col-7"><?= e($item['convocado_por']) ?></dd>
            <dt class="col-5">Estado</dt><dd class="col-7"><?= badgeEstado($item['estado']) ?></dd>
        </dl>
        <?php if ($item['participantes']??null): ?>
        <hr><div class="fw-semibold mb-1" style="font-size:12px;">PARTICIPANTES</div>
        <p style="font-size:12px;"><?= nl2br(e($item['participantes'])) ?></p>
        <?php endif; ?>
    </div>
</div>
</div>
<div class="col-lg-8">
<?php
$secciones = [
    'desempeno_procesos'  => '§9.3.2.a Desempeño SGC',
    'satisfaccion_partes' => '§9.3.2.b Satisfacción cliente',
    'resultados_auditorias' => '§9.3.2.c Resultados auditorías',
    'no_conformidades'    => '§9.3.2.d No conformidades',
    'objetivos_calidad'   => '§9.3.2.e Objetivos calidad',
    'riesgos_opor'        => '§9.3.2.f Riesgos y oportunidades',
    'recursos'            => '§9.3.2.g Recursos',
    'mejoras_sgc'         => '§9.3.3 Mejoras SGC',
    'salidas'             => '§9.3.3 Cambios necesarios',
    'recursos_necesarios' => '§9.3.3 Recursos necesarios',
];
foreach ($secciones as $campo => $etiqueta): if (!empty($item[$campo])): ?>
<div class="card mb-2">
    <div class="card-body py-2">
        <div class="fw-semibold text-primary mb-1" style="font-size:12px;"><?= e($etiqueta) ?></div>
        <p class="mb-0" style="font-size:13px;"><?= nl2br(e($item[$campo])) ?></p>
    </div>
</div>
<?php endif; endforeach; ?>
</div>
</div>
