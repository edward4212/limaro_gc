<?php $anios = range(date('Y'), 2023); ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-calendar3 me-2"></i><?= e($pageTitle) ?></h2>
        <small class="text-muted">Auditoría Interna ISO 9001:2015 §9.2</small>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <form method="GET" class="d-flex gap-2">
            <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach($anios as $y): ?><option value="<?= $y ?>" <?= $anio==$y?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
            </select>
        </form>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Imprimir</button>
    </div>
</div>

<!-- Resumen KPI -->
<?php
$total    = count($planes);
$aprobados = count(array_filter($planes, fn($p) => $p['estado']==='APROBADO'));
$finaliz   = count(array_filter($planes, fn($p) => $p['estado']==='FINALIZADO'));
$borrador  = count(array_filter($planes, fn($p) => $p['estado']==='BORRADOR'));
?>
<div class="row g-2 mb-3">
    <?php foreach([['Total',$total,'primary'],['Aprobados',$aprobados,'success'],['Finalizados',$finaliz,'dark'],['Borrador',$borrador,'secondary']] as [$l,$v,$c]): ?>
    <div class="col-6 col-md-3">
        <div class="card border-<?= $c ?> text-center py-2">
            <div class="fw-bold fs-3 text-<?= $c ?>"><?= $v ?></div>
            <div class="text-muted" style="font-size:12px;"><?= $l ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm datatable datatable-export mb-0" style="font-size:12px;">
            <thead><tr><th>Código</th><th>Título</th><th>Tipo</th><th>Auditor Líder</th><th>Procesos</th><th class="text-center">Actividades</th><th>Fechas</th><th class="text-center">Estado</th></tr></thead>
            <tbody>
            <?php foreach($planes as $p): ?>
            <tr>
                <td><a href="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$p['id'] ?>"><span><?= e($p['codigo']) ?></span></a></td>
                <td><?= e(mb_strimwidth($p['titulo']??'',0,50,'…')) ?></td>
                <td><?= e($p['tipo_auditoria']??'—') ?></td>
                <td><?= e($p['auditor_nombre']??'—') ?></td>
                <td style="font-size:12px;"><?= e(mb_strimwidth($p['procesos']??'—',0,40,'…')) ?></td>
                <td class="text-center">
                    <?php $tot=$p['total_actividades']??0; $comp=$p['actividades_completadas']??0; ?>
                    <?= $comp ?>/<?= $tot ?>
                    <?php if($tot>0): $pct=round($comp/$tot*100); ?>
                    <div class="progress mt-1" style="height:4px;"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;"><?= $p['fecha_inicio']?fechaEs($p['fecha_inicio']):'—' ?> <?= $p['fecha_fin']?'→'.fechaEs($p['fecha_fin']):'' ?></td>
                <td class="text-center"><?= badgeEstado($p['estado']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
