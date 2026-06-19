<?php $anios = range(date('Y'), 2023); ?>
<div class="page-header">
    <div><h2><i class="bi bi-journal-text me-2"></i><?= e($pageTitle) ?></h2></div>
    <div class="d-flex gap-2 d-print-none">
        <form method="GET"><select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach($anios as $y): ?><option value="<?= $y ?>" <?= $anio==$y?'selected':'' ?>><?= $y ?></option><?php endforeach; ?>
        </select></form>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i></button>
    </div>
</div>
<div class="card"><div class="card-body p-0">
<table class="table table-sm datatable datatable-export mb-0" style="font-size:12px;">
<thead><tr><th>Código</th><th>Descripción / Título</th><th>Tipo</th><th>Auditor Líder</th><th>Estado</th></tr></thead>
<tbody>
<?php $items = $programas ?? $informes ?? []; foreach($items as $r): ?>
<tr>
    <td><span><?= e($r['codigo']??'—') ?></span></td>
    <td class="col-objetivo"><?= e(mb_strimwidth($r['descripcion']??$r['sumario_ejecutivo']??$r['titulo']??'—',0,60,'…')) ?></td>
    <td><?= e($r['tipo_auditoria']??'—') ?></td>
    <td><?= e($r['auditor_nombre']??$r['auditor_lider']??'—') ?></td>
    <td><?= badgeEstado($r['estado']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
