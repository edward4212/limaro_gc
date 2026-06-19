<div class="page-header d-print-none">
    <div><h2><i class="bi bi-diagram-3 me-2"></i><?= e($pageTitle) ?></h2></div>
</div>

<?php if (empty($foda) && empty($partes)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    El módulo de <strong>Contexto Organizacional</strong> aún no tiene datos registrados.
    Ingrese información en <a href="<?= e(APP_URL) ?>/contexto/foda">Análisis DOFA</a>
    y <a href="<?= e(APP_URL) ?>/contexto/partes-interesadas">Partes Interesadas</a>.
</div>
<?php else: ?>
<div class="row g-4">
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><strong>Análisis DOFA</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm datatable-export mb-0">
            <thead><tr><th>Tipo</th><th class="text-center">Total</th></tr></thead>
            <tbody>
            <?php foreach ($foda as $f): ?>
            <tr><td><?= e($f['tipo']) ?></td>
                <td class="text-center"><span class="badge bg-primary"><?= $f['total'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><strong>Partes Interesadas</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm datatable-export mb-0">
            <thead><tr><th>Estado</th><th class="text-center">Total</th></tr></thead>
            <tbody>
            <?php foreach ($partes as $p): ?>
            <tr><td><?= badgeEstado($p['estado']) ?></td>
                <td class="text-center"><span class="badge bg-secondary"><?= $p['total'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<?php endif; ?>
