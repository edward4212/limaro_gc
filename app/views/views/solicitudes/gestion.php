<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<!-- Modal Asignar -->
<div class="modal fade" id="modalAsignar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--lim-blue);color:#fff;">
                <h6 class="modal-title"><i class="bi bi-person-check me-2"></i>Asignar Funcionario</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAsignar" method="POST">
                <?= csrfField() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Empleado Elaborador <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_empleado" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($empleados as $emp): ?>
                            <option value="<?= e($emp['id_empleado']) ?>">
                                <?= e($emp['nombre_completo']) ?> — <?= e($emp['cargo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-lim-primary btn-sm">
                        <i class="bi bi-check me-1"></i>Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reasignar — HU-019 -->
<div class="modal fade" id="modalReasignar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#fd7e14;color:#fff;">
                <h6 class="modal-title">
                    <i class="bi bi-arrow-left-right me-2"></i>Reasignar Elaborador
                </h6>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>
            <form id="formReasignar" method="POST">
                <?= csrfField() ?>
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size:12px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Actual: <strong id="reasignarActual">—</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            Nuevo Elaborador <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="id_empleado_nuevo" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($empleados as $emp): ?>
                            <option value="<?= e($emp['id_empleado']) ?>">
                                <?= e($emp['nombre_completo']) ?> — <?= e($emp['cargo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm"
                            data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-arrow-left-right me-1"></i>Reasignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="page-header">
    <div><h2><i class="bi bi-kanban me-2"></i><?= e($pageTitle) ?></h2></div>
</div>

<!-- KPIs de solicitudes -->
<?php if (!empty($resumen)): ?>
<?php
$kpis = [
    ['label'=>'Radicadas',    'valor'=>$resumen['CREADA']??0,        'icono'=>'bi-inbox',           'tipo'=>'kpi-blue',  'filtro'=>'CREADA'],
    ['label'=>'Asignadas',    'valor'=>$resumen['ASIGNADA']??0,      'icono'=>'bi-person-check',    'tipo'=>'kpi-amber', 'filtro'=>'ASIGNADA'],
    ['label'=>'En Desarrollo','valor'=>$resumen['EN_DESARROLLO']??0, 'icono'=>'bi-hourglass-split', 'tipo'=>'kpi-teal',  'filtro'=>'EN_DESARROLLO'],
    ['label'=>'Finalizadas',  'valor'=>$resumen['FINALIZADA']??0,    'icono'=>'bi-check2-circle',   'tipo'=>'kpi-green', 'filtro'=>'FINALIZADA'],
];
$kpiTotal = ['label'=>'Total', 'valor'=>array_sum($resumen)];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo</th><th>Tipo Doc.</th><th>Solicitante</th><th>Prioridad</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No hay solicitudes en este estado.</td></tr>
                <?php else: ?>
                <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td><?= e($s['id_solicitud']) ?></td>
                    <td><?= labelTipoSolicitud($s['tipo_solicitud'] ?? '') ?></td>
                    <td><?= e($s['tipo_documento']) ?></td>
                    <td><?= e($s['solicitante']) ?></td>
                    <td><?= prioridadLabel($s['prioridad'] ?? '') ?></td>
                    <td><?= fechaEs($s['fecha_solicitud']) ?></td>
                    <td>
                        <a href="<?= e(APP_URL) ?>/solicitudes/ver/<?= $s['id_solicitud'] ?>"
                           class="btn btn-sm btn-outline-info py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($estado === 'CREADA' && Auth::puede('sol_radicadas', 'editar')): ?>
                        <button class="btn btn-sm btn-outline-primary py-0"
                                data-bs-toggle="modal" data-bs-target="#modalAsignar"
                                onclick="document.getElementById('formAsignar').action='<?= e(APP_URL) ?>/solicitudes/asignar/<?= $s['id_solicitud'] ?>'">
                            <i class="bi bi-person-plus"></i> Asignar
                        </button>
                        <?php endif; ?>
                        <?php if ($estado === 'ASIGNADA' && Auth::puede('sol_asignadas', 'editar')): ?>
                        <!-- HU-019: Reasignar solo en estado ASIGNADA -->
                        <button class="btn btn-sm btn-outline-warning py-0"
                                data-bs-toggle="modal" data-bs-target="#modalReasignar"
                                onclick="
                                    document.getElementById('formReasignar').action='<?= e(APP_URL) ?>/solicitudes/reasignar/<?= $s['id_solicitud'] ?>';
                                    document.getElementById('reasignarActual').textContent='<?= e(addslashes($s['funcionario_asignado'] ?? 'Sin asignar')) ?>';
                                ">
                            <i class="bi bi-arrow-left-right"></i> Reasignar
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filtrarTabla(val) {
    if ($.fn.DataTable.isDataTable('table.datatable')) {
        $('table.datatable').DataTable().search(val).draw();
    }
}
</script>
