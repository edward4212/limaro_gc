<div class="page-header">
    <div>
        <h2><i class="bi bi-truck me-2"></i>Registro de Proveedores</h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 8.4</small>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::puede('proveedores_registro','crear')): ?>
        <a href="<?= e(APP_URL) ?>/proveedores/crear" class="btn btn-lim-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Registrar Proveedor
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
$porEstado = array_column($resumenEstado, 'total', 'estado');
$totalProv = array_sum(array_column($resumenEstado, 'total'));
$kpis = [
    ['label'=>'Activos',     'valor'=>$porEstado['ACTIVO']      ?? 0, 'icono'=>'bi-check-circle-fill',       'tipo'=>'kpi-green', 'filtro'=>'ACTIVO'],
    ['label'=>'Inactivos',   'valor'=>$porEstado['INACTIVO']    ?? 0, 'icono'=>'bi-pause-circle-fill',       'tipo'=>'kpi-blue',  'filtro'=>'INACTIVO'],
    ['label'=>'Restringidos','valor'=>$porEstado['RESTRINGIDO'] ?? 0, 'icono'=>'bi-exclamation-triangle-fill','tipo'=>'kpi-rose',  'filtro'=>'RESTRINGIDO'],
];
$kpiTotal = ['label'=>'Total Proveedores', 'valor'=>$totalProv];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-0 small">Buscar</label>
                <input type="text" class="form-control form-control-sm" name="busqueda"
                       placeholder="Razón social o documento" value="<?= e($filtros['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small">Estado</label>
                <select class="form-select form-select-sm" name="estado" id="selEstado">
                    <option value="">Todos</option>
                    <?php foreach (['ACTIVO','INACTIVO','RESTRINGIDO'] as $est): ?>
                    <option value="<?= $est ?>" <?= ($filtros['estado'] ?? '') === $est ? 'selected' : '' ?>><?= $est ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0 small">Tipo</label>
                <select class="form-select form-select-sm" name="tipo_vinculo">
                    <option value="">Todos</option>
                    <option value="PROVEEDOR" <?= ($filtros['tipo_vinculo'] ?? '') === 'PROVEEDOR' ? 'selected' : '' ?>>Proveedor</option>
                    <option value="CONTRATISTA" <?= ($filtros['tipo_vinculo'] ?? '') === 'CONTRATISTA' ? 'selected' : '' ?>>Contratista</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-lim-primary btn-sm flex-grow-1"><i class="bi bi-search"></i></button>
                <a href="<?= e(APP_URL) ?>/proveedores" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th><th>Razón Social</th><th>Documento</th><th>Tipo</th>
                    <th>Contacto</th><th>Última Evaluación</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $colorEstado = ['ACTIVO'=>'success','INACTIVO'=>'secondary','RESTRINGIDO'=>'danger'];
                $colorResultado = ['EXCELENTE'=>'success','BUENO'=>'primary','REGULAR'=>'warning','NO_CONFIABLE'=>'danger'];
                ?>
                <?php foreach ($items as $p): ?>
                <tr>
                    <td><strong><?= e($p['codigo']) ?></strong></td>
                    <td><?= e($p['razon_social']) ?></td>
                    <td><?= e($p['numero_documento'] ?? '—') ?></td>
                    <td><span class="badge bg-light text-dark"><?= e($p['tipo_vinculo']) ?></span></td>
                    <td><?= e($p['contacto_nombre'] ?? '—') ?></td>
                    <td class="text-center">
                        <?php if ($p['ultimo_resultado']): ?>
                        <span class="badge bg-<?= $colorResultado[$p['ultimo_resultado']] ?? 'secondary' ?>">
                            <?= str_replace('_',' ',$p['ultimo_resultado']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted small">Sin evaluar</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorEstado[$p['estado']] ?? 'secondary' ?>"><?= e($p['estado']) ?></span>
                    </td>
                    <td class="text-center">
                        <a href="<?= e(APP_URL) ?>/proveedores/ver/<?= (int)$p['id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-1" title="Ver">
                            <i class="bi bi-eye" style="font-size:11px;"></i>
                        </a>
                        <?php if (Auth::puede('proveedores_registro','editar')): ?>
                        <a href="<?= e(APP_URL) ?>/proveedores/editar/<?= (int)$p['id'] ?>" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Editar">
                            <i class="bi bi-pencil" style="font-size:11px;"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay proveedores registrados con estos filtros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla(estado) {
    document.getElementById('selEstado').value = estado;
    document.getElementById('selEstado').form.submit();
}
</script>
