<?php $anios = range(date('Y'), 2023); ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-exclamation-triangle me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Auditoría Interna ISO 9001:2015 §9.2</small>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<!-- KPIs -->
<div class="row g-2 mb-3">
    <?php foreach([
        ['Total',           $kpis['total']??0,           'primary'],
        ['Abiertos',        $kpis['abiertos']??0,        'danger'],
        ['En Proceso',      $kpis['en_proceso']??0,      'warning'],
        ['Cerrados',        $kpis['cerrados']??0,        'success'],
        ['No Conformidades',$kpis['no_conformidades']??0,'dark'],
        ['Vencidos',        $kpis['vencidos']??0,        'danger'],
    ] as [$l,$v,$c]): ?>
    <div class="col-4 col-md-2">
        <div class="card border-<?= $c ?> text-center py-2">
            <div class="fw-bold fs-3 text-<?= $c ?>"><?= (int)$v ?></div>
            <div class="text-muted" style="font-size:12px;"><?= $l ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<form method="GET" class="row g-2 mb-3 d-print-none">
    <div class="col-md-2">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <?php foreach(['ABIERTO','EN_PROCESO','CERRADO'] as $e): ?><option value="<?= $e ?>" <?= ($filtros['estado']??'')===$e?'selected':'' ?>><?= $e ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos los tipos</option>
            <?php foreach(['NO_CONFORMIDAD','OBSERVACION','OPORTUNIDAD','FORTALEZA'] as $t): ?><option value="<?= $t ?>" <?= ($filtros['tipo']??'')===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
        </select>
    </div>
</form>

<!-- Por Proceso -->
<?php if(!empty($porProceso)): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-diagram-3 me-1"></i>Distribución por Proceso</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:12px;">
            <thead><tr><th>Proceso</th><th class="text-center">Total</th><th class="text-center">Abiertos</th><th class="text-center">Cerrados</th><th>Progreso</th></tr></thead>
            <tbody>
            <?php foreach($porProceso as $pp): $pct=$pp['total']>0?round($pp['cerrados']/$pp['total']*100):0; ?>
            <tr>
                <td><?= e($pp['proceso_nombre']??$pp['proceso_auditado']??'Sin proceso') ?></td>
                <td class="text-center"><strong><?= (int)$pp['total'] ?></strong></td>
                <td class="text-center"><span class="badge bg-danger"><?= (int)$pp['abiertos'] ?></span></td>
                <td class="text-center"><span class="badge bg-success"><?= (int)$pp['cerrados'] ?></span></td>
                <td style="min-width:100px;">
                    <div class="progress" style="height:6px;"><div class="progress-bar bg-<?= $pct==100?'success':'primary' ?>" style="width:<?= $pct ?>%"></div></div>
                    <small class="text-muted" style="font-size:12px;"><?= $pct ?>% cerrados</small>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Detalle hallazgos -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm datatable datatable-export mb-0" style="font-size:12px;">
            <thead><tr><th>Plan</th><th>Tipo</th><th>Cláusula</th><th>Proceso</th><th>Descripción</th><th>Responsable</th><th>Fecha Cierre</th><th>AC</th><th class="text-center">Estado</th></tr></thead>
            <tbody>
            <?php foreach($hallazgos as $h): $tipos=['NO_CONFORMIDAD'=>'danger','OBSERVACION'=>'warning','OPORTUNIDAD'=>'info','FORTALEZA'=>'success']; ?>
            <tr>
                <td><?= e($h['plan_codigo']??($h['programa_codigo']??'—')) ?></td>
                <td><span class="badge bg-<?= $tipos[$h['tipo']]??'secondary' ?>" style="font-size:12px;"><?= e($h['tipo']) ?></span></td>
                <td><?= e($h['clausula_iso']??'—') ?></td>
                <td><?= e($h['proceso_nombre']??$h['proceso_auditado']??'—') ?></td>
                <td><?= e(mb_strimwidth($h['descripcion']??'',0,60,'…')) ?></td>
                <td><?= e($h['responsable_nombre']??$h['responsable']??'—') ?></td>
                <td><?= $h['fecha_cierre']?fechaEs($h['fecha_cierre']):'—' ?></td>
                <td><?= e($h['ac_codigo']??'—') ?></td>
                <td class="text-center"><?= badgeEstado($h['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
