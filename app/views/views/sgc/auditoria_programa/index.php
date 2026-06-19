<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-text me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Programas específicos derivados del Plan de Auditoría</small>
    </div>
    <?php if (Auth::puede('audit_programa','crear')): ?>
    <a href="<?= e(APP_URL) ?>/auditoria/programa/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Programa
    </a>
    <?php endif; ?>
</div>

<form method="GET" class="row g-2 mb-3 d-print-none">
    <div class="col-md-2">
        <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los años</option>
            <?php for ($y=date('Y'); $y>=2023; $y--): ?>
            <option value="<?= $y ?>" <?= ($filtros['anio']??'')==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <?php foreach (['BORRADOR','APROBADO','EN_CURSO','FINALIZADO'] as $e): ?>
            <option value="<?= $e ?>" <?= ($filtros['estado']??'')===$e?'selected':'' ?>><?= $e ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export mb-0">
            <thead>
                <tr>
                    <th>Código</th><th>Descripción</th><th>Tipo</th>
                    <th>Plan Vinculado</th><th>Proceso</th>
                    <th>Auditor Líder</th><th>Fecha Auditoría</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center d-print-none">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($programas)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Sin programas registrados.</td></tr>
                <?php else: ?>
                <?php foreach ($programas as $p): ?>
                <tr>
                    <td><code style="font-size:12px;"><?= e($p['codigo'] ?? '—') ?></span></td>
                    <td style="font-size:12px;max-width:200px;"><?= e($p['descripcion']) ?></td>
                    <td style="font-size:12px;"><?= e($p['tipo_auditoria'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($p['plan_codigo'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($p['proceso_nombre'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($p['auditor_nombre'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= $p['fecha_auditoria'] ? fechaEs($p['fecha_auditoria']) : '—' ?></td>
                    <td class="text-center"><?= badgeEstado($p['plan_estado'] ?? $p['estado']) ?></td>
                    <td class="text-center d-print-none" style="white-space:normal;">
                        <a href="<?= e(APP_URL) ?>/auditoria/programa/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-info py-0 px-2"><i class="bi bi-eye"></i></a>
                        <?php
                        $planEstadoIdx = $p['plan_estado'] ?? $p['estado'];
                        $editableIdx = Auth::puede('audit_programa','editar') && ($planEstadoIdx === 'BORRADOR');
                        if ($editableIdx): ?>
                        <a href="<?= e(APP_URL) ?>/auditoria/programa/editar/<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php /* Estado del programa = estado del plan */ ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
