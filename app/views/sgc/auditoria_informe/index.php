<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-file-earmark-text me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Resultados de auditoría interna ISO 9001:2015 §9.2</small>
    </div>
    <?php if (Auth::puede('audit_informe','crear')): ?>
    <a href="<?= e(APP_URL) ?>/auditoria/informe/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Informe
    </a>
    <?php endif; ?>
</div>

<form method="GET" class="row g-2 mb-3 d-print-none">
    <div class="col-md-2">
        <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los años</option>
            <?php for($y=date('Y');$y>=2023;$y--): ?>
            <option value="<?= $y ?>" <?= ($filtros['anio']??'')==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <?php foreach (['BORRADOR','EN_REVISION','FINALIZADO','DISTRIBUIDO'] as $e): ?>
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
                    <th>Código</th><th>Tipo</th><th>Programa</th><th>Plan</th>
                    <th>Auditor Líder</th><th>Fecha</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center d-print-none">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($informes)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Sin informes registrados.</td></tr>
                <?php else: foreach($informes as $inf): ?>
                <tr>
                    <td><code style="font-size:11px;"><?= e($inf['codigo']) ?></span></td>
                    <td style="font-size:11px;"><?= e($inf['tipo_auditoria']??'—') ?></td>
                    <td style="font-size:11px;"><?= e($inf['programa_codigo']??'—') ?></td>
                    <td style="font-size:11px;"><?= e($inf['plan_codigo']??'—') ?></td>
                    <td style="font-size:12px;"><?= e($inf['auditor_nombre']??'—') ?></td>
                    <td style="font-size:11px;"><?= $inf['fecha_informe']?fechaEs($inf['fecha_informe']):'—' ?></td>
                    <td class="text-center"><?= badgeEstado($inf['estado']) ?></td>
                    <td class="text-center d-print-none" style="white-space:normal;">
                        <a href="<?= e(APP_URL) ?>/auditoria/informe/<?= (int)$inf['id'] ?>"
                           class="btn btn-sm btn-outline-info py-0 px-2"><i class="bi bi-eye"></i></a>
                        <?php if ($inf['estado']==='BORRADOR' && Auth::puede('audit_informe','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/auditoria/informe/editar/<?= (int)$inf['id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($inf['estado']==='BORRADOR'): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/revisar/<?= (int)$inf['id'] ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2" title="Enviar a Revisión"
                                    onclick="swalConfirmForm(event,'¿Enviar el informe a revisión?','Enviar a Revisión')">
                                <i class="bi bi-send"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($inf['estado']==='EN_REVISION' && Auth::hasRole([1,2])): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/aprobar/<?= (int)$inf['id'] ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" title="Aprobar"
                                    onclick="swalConfirmForm(event,'¿Aprobar el informe <?= e(addslashes($inf['codigo'])) ?>?','¿Aprobar Informe?')">
                                <i class="bi bi-check2-circle"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
