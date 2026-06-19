<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div><h2><i class="bi bi-x-circle me-2 text-danger"></i>Listado Maestro — Documentos Obsoletos</h2></div>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<div class="alert alert-warning d-flex gap-2 align-items-center py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span style="font-size:13px;">
        Los documentos obsoletos son de solo consulta.
        No deben usarse en procesos activos.
        Se muestra la versión obsoleta más reciente — el historial completo está en Versionamiento.
    </span>
</div>

<?php if (empty($documentos)): ?>
<div class="alert alert-info">No hay documentos obsoletos registrados.</div>
<?php else: ?>

<?php
$filtroId = 'docs-obsoletos';
$tablaId  = 'tbl-obsoletos';
$columnas = [
    ['idx'=>0, 'label'=>'Proceso', 'categoria'=>'proceso'],
    ['idx'=>1, 'label'=>'Código'],
    ['idx'=>2, 'label'=>'Nombre del Documento'],
    ['idx'=>3, 'label'=>'Tipo', 'categoria'=>'tipo_documento'],
    ['idx'=>4, 'label'=>'Elaboró'],
    ['idx'=>5, 'label'=>'Revisó'],
    ['idx'=>6, 'label'=>'Aprobó'],
    ['idx'=>7, 'label'=>'F. Aprobación'],
];
include APP_ROOT . '/app/views/partials/filtro_avanzado.php';
?>
<div class="card">
    <div class="card-body p-0">
        <table id="tbl-obsoletos" class="table table-hover table-sm mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>Proceso</th>
                    <th>Código</th>
                    <th>Nombre del Documento</th>
                    <th>Tipo</th>
                    <th class="text-center">Versión</th>
                    <th>Elaboró</th>
                    <th>Revisó</th>
                    <th>Aprobó</th>
                    <th>F. Aprobación</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($documentos as $d): ?>
            <tr>
                <td><?= e(($d['macroproceso'] ?? '') . ' — ' . ($d['proceso'] ?? '')) ?></td>
                <td><code style="font-size:11px;background:#f1f5f9;padding:2px 5px;border-radius:3px;"><?= e($d['codigo'] ?? '') ?></code></td>
                <td style="font-size:12px;"><?= e($d['nombre_documento'] ?? '') ?></td>
                <td><span class="badge bg-secondary" style="font-size:10px;"><?= e($d['sigla_tipo_documento'] ?? $d['tipo_documento'] ?? '') ?></span></td>
                <td class="text-center"><span class="badge bg-dark">V<?= e($d['numero_version'] ?? 0) ?></span></td>
                <td style="font-size:11px;"><?= e(truncar($d['elaborador'] ?? $d['usuario_creacion'] ?? '—', 25)) ?></td>
                <td style="font-size:11px;"><?= e(truncar($d['revisor'] ?? $d['usuario_revision'] ?? '—', 25)) ?></td>
                <td style="font-size:11px;"><?= e(truncar($d['aprobador'] ?? $d['usuario_aprobacion'] ?? '—', 25)) ?></td>
                <td style="font-size:11px;"><?= fechaEs($d['fecha_aprobacion'] ?? null) ?></td>
                <td class="text-center">
                    <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$d['id_documento'] ?>?from=obsoletos"
                       class="btn btn-sm btn-outline-secondary py-0" title="Ver historial completo">
                        <i class="bi bi-clock-history"></i>
                    </a>
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
    $('#tbl-obsoletos').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        pageLength: 25,
        lengthMenu: [[15,25,50,100,-1],['15','25','50','100','Todos']],
        orderFixed: [[0,'asc']],
        orderCellsTop: true,
        columnDefs: [
            { targets: 0, visible: false },
            { targets: [-1], orderable: false, searchable: false }
        ],
        rowGroup: {
            dataSrc: 0,
            startRender: function(rows, group) {
                return $('<tr class="table-danger"/>')
                    .append('<td colspan="100" class="fw-bold py-2">' +
                        '<i class="bi bi-folder2-open me-2"></i>' + group +
                        ' <span class="badge bg-danger ms-2">' + rows.count() + ' doc(s)</span>' +
                    '</td>');
            }
        },
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>tip',
        order: [[1,'asc']],
        initComplete: function () {
            if (window.FAR && window.FAR['docs-obsoletos']) {
                window.FAR['docs-obsoletos'].setApi(this.api());
            }
            // Fila de filtros por columna, construida solo sobre columnas visibles
            // (la columna Proceso está oculta; iterar visible-only evita el
            // desalineamiento documentado cuando se mezcla con columnas ocultas).
            var api = this.api();
            var thead = $(api.table().header());
            var filterRow = $('<tr class="fila-filtros"></tr>');
            api.columns().every(function (idx) {
                var col = api.column(idx);
                if (!col.visible()) return;
                var th = $('<th></th>');
                if (idx !== api.columns().count() - 1) {
                    var titulo = $(col.header()).text().trim();
                    var inp = $('<input type="text" class="col-filter">')
                        .attr('placeholder', titulo.length > 12 ? titulo.substring(0, 12) + '…' : titulo)
                        .attr('data-col', idx)
                        .on('keyup change', function () { api.column(idx).search(this.value).draw(); })
                        .on('click', function (e) { e.stopPropagation(); });
                    th.append(inp);
                }
                filterRow.append(th);
            });
            thead.append(filterRow);
        }
    });
});
</script>

<?php endif; ?>
