<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-file-earmark-text me-2"></i>Documentos Registrados</h2></div>
    <?php if (Auth::puede('documentos_registrados', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/documentos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Documento
    </a>
    <?php endif; ?>
</div>

<?php
// Agrupar por proceso para CA-2 HU-006
$porProceso = [];
foreach ($documentos as $d) {
    $key = $d['macroproceso'] . ' — ' . $d['proceso'];
    $porProceso[$key][] = $d;
}
$totalDocs     = count($documentos);
$totalInactivos = count(array_filter($documentos, fn($d) => ($d['estado'] ?? 'ACTIVO') === 'INACTIVO'));
?>

<!-- Resumen rápido -->
<div class="d-flex gap-3 mb-3 flex-wrap">
    <span class="badge bg-primary fs-6">
        <?= $totalDocs ?> documento(s) en total
    </span>
    <?php if ($totalInactivos > 0): ?>
    <span class="badge bg-secondary fs-6">
        <?= $totalInactivos ?> inactivo(s)
    </span>
    <?php endif; ?>
    <span class="badge bg-success fs-6">
        <?= $totalDocs - $totalInactivos ?> activo(s)
    </span>
</div>

<div class="card">
    <div class="card-body p-0">
        <table id="tbl-docs-registrados" class="table table-hover table-sm mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>_grupo_</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Proceso</th>
                    <th>Subproceso</th>
                    <th>Versión</th>
                    <th>Est. Ver.</th>
                    <th>Estado Doc.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        No hay documentos registrados.
                    </td>
                </tr>
                <?php else: ?>

                <?php foreach ($documentos as $d):
                    $inactivo = ($d['estado'] ?? 'ACTIVO') === 'INACTIVO';
                    $grupoClave = ($d['macroproceso'] ?? '') . ' — ' . ($d['proceso'] ?? '');
                ?>
                <tr class="<?= $inactivo ? 'text-muted' : '' ?>">
                    <td><?= e($grupoClave) ?></td>
                    <td>
                        <code class="<?= $inactivo ? : '' ?>">
                            <?= e($d['codigo']) ?>
                        </span>
                        <!--<?php if (!empty($d['codigo_anterior'])): ?>-->
                        <!--<br><small class="text-muted" style="font-size:12px;">-->
                        <!--    <del><?= e($d['codigo_anterior']) ?></del>-->
                        <!--</small>-->
                        <!--<?php endif; ?>-->
                    </td>
                    <td style="font-size:12px;"><?= e(truncar($d['nombre_documento'], 50)) ?></td>
                    <td>
                        <!-- CA-4 HU-006: sigla + nombre del tipo -->
                        <span class="badge bg-secondary"><?= e($d['sigla_tipo_documento']) ?></span>
                        <span style="font-size:12px; color:#555;"> <?= e($d['tipo_documento']) ?></span>
                    </td>
                    <td style="font-size:12px;"><?= e($d['proceso']) ?></td>
                    <td>
                        <?php if (!empty($d['nombre_subproceso'])): ?>
                        <span class="badge bg-info text-dark"
                              style="font-size:12px;"><?= e($d['nombre_subproceso']) ?></span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($d['version_actual'] !== null && $d['version_actual'] !== ''): ?>
                        <span class="badge bg-primary">v<?= e($d['version_actual']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= badgeEstado($d['estado_version'] ?? 'CREADO') ?></td>
                    <td>
                        <?php if ($inactivo): ?>
                        <span class="badge bg-secondary">INACTIVO</span>
                        <?php else: ?>
                        <span class="badge bg-success">ACTIVO</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Historial -->
                        <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$d['id_documento'] ?>?from=documentos&consulta=1"
                           class="btn btn-sm btn-outline-info py-0" title="Historial versiones">
                            <i class="bi bi-layers"></i>
                        </a>

                        <?php if (!$inactivo && Auth::puede('documentos_registrados', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/documentos/editar/<?= (int)$d['id_documento'] ?>"
                           class="btn btn-sm btn-outline-primary py-0" title="Editar datos">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= e(APP_URL) ?>/documentos/reasignar/<?= (int)$d['id_documento'] ?>"
                           class="btn btn-sm btn-outline-warning py-0" title="Reasignar proceso">
                            <i class="bi bi-arrow-left-right"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!$inactivo && Auth::puede('documentos_registrados', 'eliminar')): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                onclick="setModalConfirm(
                                    '<?= e(APP_URL) ?>/documentos/eliminar/<?= (int)$d['id_documento'] ?>',
                                    '¿Inactivar: <?= e(addslashes($d['nombre_documento'])) ?>?'
                                )" title="Inactivar">
                            <i class="bi bi-slash-circle"></i>
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
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable === 'undefined') return;

    // Columnas: 0=_grupo_(oculta), 1=Código, 2=Nombre, 3=Tipo, 4=Proceso,
    //           5=Subproceso, 6=Versión, 7=EstVer, 8=EstadoDoc, 9=Acciones
    $('#tbl-docs-registrados').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        pageLength: 25,
        lengthMenu: [[15,25,50,100,-1],['15','25','50','100','Todos']],
        orderFixed: [[0,'asc']],
        columnDefs: [
            { targets: 0, visible: false },
            { targets: [9], orderable: false }
        ],
        rowGroup: {
            dataSrc: 0,
            startRender: function(rows, group) {
                return $('<tr class="table-primary"/>')
                    .append('<td colspan="100" class="fw-bold py-2 ps-3">' +
                        '<i class="bi bi-folder2-open me-2"></i>' + group +
                        ' <span class="badge bg-primary ms-2">' + rows.count() + ' doc(s)</span>' +
                    '</td>');
            }
        },
        dom: '<"row mb-2"<"col-sm-4"l><"col-sm-4 text-center"B><"col-sm-4"f>>tip',
        buttons: [
            { extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
              className: 'btn btn-sm btn-success',
              exportOptions: { columns: [1,2,3,4,5,6,7,8] } },
            { extend: 'pdfHtml5',
              text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
              className: 'btn btn-sm btn-danger',
              orientation: 'landscape', pageSize: 'LETTER',
              exportOptions: { columns: [1,2,3,4,5,6,7,8] } },
            { extend: 'print',
              text: '<i class="bi bi-printer me-1"></i>Imprimir',
              className: 'btn btn-sm btn-secondary',
              exportOptions: { columns: [1,2,3,4,5,6,7,8] } },
        ],
        order: [[1,'asc']]
    });
});
</script>
