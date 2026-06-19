<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div><h2><i class="bi bi-layers me-2"></i>Versionamiento de Documentos</h2></div>
    <?php if (Auth::puede('versionamiento','crear')): ?>
    <div>
        <a href="<?= e(APP_URL) ?>/documentos" class="btn btn-lim-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nueva Versión
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($versiones)): ?>
<div class="alert alert-info">No hay documentos con versiones.</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <table id="tbl-versionamiento" class="table table-hover table-sm mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>Proceso</th>
                    <th>Código</th>
                    <th>Nombre del Documento</th>
                    <th>Tipo</th>
                    <th class="text-center">Versión</th>
                    <th>Estado</th>
                    <th>Última Apro.</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($versiones as $v): ?>
            <tr>
                <td><?= e(($v['macroproceso'] ?? '') . ' — ' . ($v['proceso'] ?? '')) ?></td>
                <td><code style="font-size:12px;background:#f1f5f9;padding:2px 5px;border-radius:3px;"><?= e($v['codigo'] ?? '') ?></span></td>
                <td style="font-size:12px;"><?= e($v['nombre_documento'] ?? '') ?></td>
                <td><span class="badge bg-secondary" style="font-size:12px;line-height:1.4;"><?= e($v['sigla_tipo'] ?? '') ?><br><small style='font-size:12px;font-weight:normal;opacity:.85;'><?= e($v['tipo_documento'] ?? '') ?></small></span></td>
                <td class="text-center"><span class="badge bg-primary">V<?= e($v['max_version'] ?? 0) ?></span></td>
                <td><?= badgeEstado($v['estado_version'] ?? 'CREADO') ?></td>
                <td style="font-size:12px;"><?= fechaEs($v['fecha_aprobacion'] ?? null) ?></td>
                <td class="text-center">
                    <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$v['id_documento'] ?>"
                       class="btn btn-sm btn-outline-info py-0" title="Ver historial">
                        <i class="bi bi-clock-history"></i>
                    </a>
                    <?php if (Auth::puede('versionamiento', 'crear')): ?>
                    <a href="<?= e(APP_URL) ?>/versionamiento/nueva/<?= (int)$v['id_documento'] ?>"
                       class="btn btn-sm btn-lim-primary py-0" title="Nueva versión">
                        <i class="bi bi-plus-circle"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable === 'undefined') return;

    // Cargar RowGroup si no está disponible
    var dt = $('#tbl-versionamiento').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        pageLength: 25,
        lengthMenu: [[15, 25, 50, 100, -1], ['15', '25', '50', '100', 'Todos']],
        orderFixed: [[0, 'asc']],   // siempre ordenar por proceso primero
        columnDefs: [
            { targets: 0, visible: false },  // ocultar col Proceso (usada por RowGroup)
            { targets: [4, 7], orderable: false }
        ],
        rowGroup: {
            dataSrc: 0,
            startRender: function (rows, group) {
                return $('<tr class="table-primary fw-bold"/>')
                    .append('<td colspan="7">' +
                        '<i class="bi bi-folder2-open me-2"></i>' +
                        group +
                        ' <span class="badge bg-primary ms-2">' + rows.count() + ' doc(s)</span>' +
                    '</td>');
            }
        },
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>tip',
        order: [[1, 'asc']]
    });
});
</script>

<?php endif; ?>
