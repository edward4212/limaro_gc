<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-exclamation-triangle me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Hallazgos de Auditoría Interna ISO 9001:2015</small>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-3">
    <?php
    $k = $kpis;
    $cards = [
        ['Total',          $k['total']??0,        'primary',  'bi-list-ul'],
        ['Abiertos',       $k['abiertos']??0,     'danger',   'bi-exclamation-circle'],
        ['En Proceso',     $k['en_proceso']??0,   'warning',  'bi-clock-history'],
        ['Cerrados',       $k['cerrados']??0,     'success',  'bi-check-circle'],
        ['No Conformidades',$k['no_conformidades']??0,'dark', 'bi-x-octagon'],
        ['Vencidos',       $k['vencidos']??0,     'danger',   'bi-calendar-x'],
    ];
    foreach ($cards as [$label, $val, $color, $icon]):
    ?>
    <div class="col-6 col-md-2">
        <div class="card border-<?= $color ?> h-100">
            <div class="card-body py-2 px-3 text-center">
                <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:1.3rem;"></i>
                <div class="fw-bold fs-4 text-<?= $color ?>"><?= (int)$val ?></div>
                <div class="text-muted" style="font-size:12px;"><?= $label ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<form method="GET" class="row g-2 mb-3 d-print-none">
    <div class="col-md-2">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <?php foreach(['ABIERTO','EN_PROCESO','CERRADO'] as $e): ?>
            <option value="<?= $e ?>" <?= ($filtros['estado']??'')===$e?'selected':'' ?>><?= $e ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los tipos</option>
            <?php foreach(['NO_CONFORMIDAD','OBSERVACION','OPORTUNIDAD','FORTALEZA'] as $t): ?>
            <option value="<?= $t ?>" <?= ($filtros['tipo']??'')===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <input type="text" name="proceso" class="form-control form-control-sm"
               placeholder="Filtrar por proceso..."
               value="<?= e($filtros['proceso']??'') ?>">
    </div>
    <div class="col-md-1">
        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
            <i class="bi bi-search"></i>
        </button>
    </div>
    <?php if (!empty(array_filter($filtros))): ?>
    <div class="col-md-1">
        <a href="<?= e(APP_URL) ?>/auditoria/hallazgos" class="btn btn-sm btn-outline-secondary w-100">
            <i class="bi bi-x"></i>
        </a>
    </div>
    <?php endif; ?>
</form>

<!-- Tabla Hallazgos -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
        <strong><i class="bi bi-table me-1"></i>Hallazgos</strong>
        <span class="badge bg-primary"><?= count($hallazgos) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm datatable datatable-export mb-0" style="font-size:12px;">
            <thead>
                <tr>
                    <th>Plan</th><th>Tipo</th><th>Cláusula</th><th>Proceso</th>
                    <th>Descripción</th><th>Responsable</th><th>Cierre</th>
                    <th class="text-center">AC</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center d-print-none">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hallazgos)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Sin hallazgos.</td></tr>
                <?php else: foreach ($hallazgos as $h): ?>
                <tr>
                    <td><?= e($h['plan_codigo']??($h['programa_codigo']??'—')) ?></td>
                    <td>
                        <?php $tipos = ['NO_CONFORMIDAD'=>'danger','OBSERVACION'=>'warning','OPORTUNIDAD'=>'info','FORTALEZA'=>'success'];
                        $tc = $tipos[$h['tipo']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $tc ?>" style="font-size:12px;"><?= e($h['tipo']) ?></span>
                    </td>
                    <td><?= e($h['clausula_iso']??'—') ?></td>
                    <td style="max-width:100px;"><?= e($h['proceso_nombre']??$h['proceso_auditado']??'—') ?></td>
                    <td style="max-width:200px;"><?= e(mb_strimwidth($h['descripcion']??'',0,80,'…')) ?></td>
                    <td><?= e($h['responsable_nombre']??$h['responsable']??'—') ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($h['fecha_cierre']): ?>
                            <?php $dias = (strtotime($h['fecha_cierre']) - time()) / 86400; ?>
                            <span class="<?= $dias < 0 ? 'text-danger fw-bold' : ($dias < 7 ? 'text-warning' : '') ?>">
                                <?= fechaEs($h['fecha_cierre']) ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($h['ac_codigo']): ?>
                        <a href="<?= e(APP_URL) ?>/acciones/<?= (int)$h['id_accion_correctiva'] ?>"
                           class="badge bg-success text-decoration-none"><?= e($h['ac_codigo']) ?></a>
                        <?php elseif ($h['estado'] !== 'CERRADO'): ?>
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/hallazgos/<?= (int)$h['id'] ?>/generar-ac" style="display:inline;">
                            <?= csrfField() ?>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:12px;"
                                    onclick="swalConfirmForm(event,'Se creará una Acción Correctiva vinculada a este hallazgo.','Generar AC')">
                                <i class="bi bi-plus-circle"></i> AC
                            </button>
                        </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center">
                        <!-- Cambio de estado inline -->
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/hallazgos/<?= (int)$h['id'] ?>/estado"
                              style="display:inline;">
                            <?= csrfField() ?>
                            <select name="estado" class="form-select form-select-sm py-0"
                                    style="font-size:12px;width:auto;display:inline;"
                                    onchange="this.form.submit()">
                                <?php foreach(['ABIERTO','EN_PROCESO','CERRADO'] as $est): ?>
                                <option value="<?= $est ?>" <?= $h['estado']===$est?'selected':'' ?>><?= $est ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="text-center d-print-none">
                        <a href="<?= e(APP_URL) ?>/hallazgos/<?= (int)$h['id'] ?>"
                           class="btn btn-xs btn-outline-info py-0 px-1">
                            <i class="bi bi-eye" style="font-size:12px;"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Hallazgos por Proceso -->
<?php if (!empty($porProceso)): ?>
<div class="card">
    <div class="card-header"><i class="bi bi-diagram-3 me-1"></i>Distribución por Proceso</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:12px;">
            <thead><tr><th>Proceso</th><th class="text-center">Total</th><th class="text-center">Abiertos</th><th class="text-center">Cerrados</th><th>Progreso</th></tr></thead>
            <tbody>
            <?php foreach ($porProceso as $pp):
                $pct = $pp['total'] > 0 ? round(($pp['cerrados']/$pp['total'])*100) : 0; ?>
            <tr>
                <td><?= e($pp['proceso_nombre']??$pp['proceso_auditado']??'Sin proceso') ?></td>
                <td class="text-center"><strong><?= (int)$pp['total'] ?></strong></td>
                <td class="text-center"><span class="badge bg-danger"><?= (int)$pp['abiertos'] ?></span></td>
                <td class="text-center"><span class="badge bg-success"><?= (int)$pp['cerrados'] ?></span></td>
                <td style="min-width:100px;">
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-<?= $pct==100?'success':'primary' ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <small class="text-muted" style="font-size:12px;"><?= $pct ?>%</small>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
