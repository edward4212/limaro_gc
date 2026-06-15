<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-text me-2"></i><?= e($item['codigo']??'Programa') ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/programa">Programas de Auditoría</a></li>
            <li class="breadcrumb-item active"><?= e($item['codigo']??'#'.$item['id']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?= badgeEstado($item['plan_estado'] ?? $item['estado']) ?>
        <small class='text-muted' style='font-size:10px;'>(Estado del Plan)</small>
        <?php
        $planEstadoVer = $item['plan_estado'] ?? $item['estado'];
        if ($planEstadoVer === 'BORRADOR' && Auth::puede('audit_programa','editar')): ?>
        <a href="<?= e(APP_URL) ?>/auditoria/programa/editar/<?= (int)$item['id'] ?>"
           class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <?php endif; ?>
        <?php /* Programa hereda estado del plan — no tiene aprobación propia */ ?>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="<?= e(APP_URL) ?>/auditoria/programa" class="btn btn-secondary btn-sm d-print-none">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Información del Programa</strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <?php foreach ([
                    'Código'           => $item['codigo'] ?? '—',
                    'Año'              => $item['anio'] ?? '—',
                    'Tipo Auditoría'   => $item['tipo_auditoria'] ?? '—',
                    'Plan Vinculado'   => $item['plan_codigo'] ? $item['plan_codigo'].' — '.($item['plan_titulo']??'') : 'Autónomo',
                    'Proceso Auditado' => $item['proceso_nombre'] ?? '—',
                    'Auditor Líder'    => $item['auditor_nombre'] ?? $item['auditor_lider'] ?? '—',
                    'Fecha Auditoría'  => !empty($item['fecha_auditoria']) ? fechaEs($item['fecha_auditoria']) : '—',
                ] as $lbl => $val): ?>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-semibold text-muted"><?= $lbl ?>:</div>
                    <div class="col-sm-8"><?= e($val) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <div class="fw-semibold text-muted mb-1">Descripción:</div>
                    <p><?= e($item['descripcion']) ?></p>
                </div>
                <div class="mb-3">
                    <div class="fw-semibold text-muted mb-1">Objetivo:</div>
                    <p><?= nl2br(e($item['objetivo'] ?? '—')) ?></p>
                </div>
                <?php if ($item['alcance'] ?? ''): ?>
                <div class="mb-3">
                    <div class="fw-semibold text-muted mb-1">Alcance:</div>
                    <p class="mb-0"><?= nl2br(e($item['alcance'] ?? '')) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($item['auditores'] ?? ''): ?>
                <div class="mb-0">
                    <div class="fw-semibold text-muted mb-1">Equipo Auditor:</div>
                    <p class="mb-0"><?= nl2br(e($item['auditores'] ?? '')) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Objetivos Específicos -->
<?php if (!empty($actividades)): ?>
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between">
        <strong><i class="bi bi-list-check me-1"></i>Objetivos Específicos y Actividades de Auditoría</strong>
        <span class="badge bg-primary"><?= count($actividades) ?> objetivo(s)</span>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
        <thead class="table-primary">
            <tr>
                <th>#</th><th>Objetivo Específico</th><th>Criterio</th>
                <th>Actividad</th><th>Riesgo</th><th>Procedimiento</th>
                <th>Observación</th><th>Referenciación</th><th>Papel Trabajo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($actividades as $i => $a): ?>
            <tr>
                <td class="text-center fw-bold"><?= $i+1 ?></td>
                <td><?= nl2br(e($a['objetivo_especifico']??'—')) ?></td>
                <td><?= nl2br(e($a['criterio']??'—')) ?></td>
                <td><?= nl2br(e($a['actividad']??'—')) ?></td>
                <td><?= nl2br(e($a['riesgo']??'—')) ?></td>
                <td><?= nl2br(e($a['procedimiento_auditoria']??'—')) ?></td>
                <td><?= nl2br(e($a['observacion']??'—')) ?></td>
                <td><?= e($a['referenciacion']??'—') ?></td>
                <td><?= e($a['papel_de_trabajo']??'—') ?></td>

            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php else: ?>
<div class="card mt-3">
    <div class="card-body text-center text-muted py-3" style="font-size:13px;">
        <i class="bi bi-list-check d-block mb-1" style="font-size:1.3rem;"></i>
        Sin objetivos específicos registrados.
    </div>
</div>
<?php endif; ?>
