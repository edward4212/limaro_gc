<!-- Modal visor documentos (igual que explorador.php) -->
<div class="modal fade" id="modalVisor" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:min(96vw,1200px);width:96vw;">
    <div class="modal-content" style="height:90vh;">
      <div class="modal-header py-2" style="background:linear-gradient(135deg,var(--lim-blue-dark),var(--lim-blue));color:#fff;">
        <span class="modal-title" id="visorTitulo" style="font-size:14px;font-weight:600;"></span>
        <div class="d-flex gap-2 ms-auto">
          <a id="visorDescargar" href="#" class="btn btn-sm btn-outline-light py-0" title="Descargar">
            <i class="bi bi-download me-1"></i>Descargar
          </a>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div id="visorProgreso" class="progress" style="height:3px;border-radius:0;display:none;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width:100%;"></div>
      </div>
      <div id="visorError" class="d-none flex-column align-items-center justify-content-center p-5 text-center"
           style="height:calc(90vh - 60px);">
        <i class="bi bi-exclamation-circle text-danger" style="font-size:48px;"></i>
        <p class="mt-3 text-muted">No se pudo cargar el visor en línea.</p>
        <a id="visorErrorDescarga" href="#" class="btn btn-lim-primary mt-2">
          <i class="bi bi-download me-1"></i>Descargar documento
        </a>
      </div>
      <iframe id="visorIframe" style="width:100%;height:calc(90vh - 63px);border:none;"
              allow="fullscreen" title="Visor de documento"></iframe>
    </div>
  </div>
</div>

<div class="page-header">
    <div><h2><i class="bi bi-list-check me-2"></i>Listado Maestro — Documentos Vigentes</h2></div>
    <div class="d-flex gap-2"><a href="<?= e(APP_URL) ?>/documentos/vigentes/descargar-zip"
           class="btn btn-success btn-sm" title="Descargar todos como ZIP">
            <i class="bi bi-file-zip me-1"></i>Descargar Todo
        </a>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button></div>
</div>

<?php if (empty($documentos)): ?>
<div class="alert alert-info">No hay documentos registrados.</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <table id="tbl-docs-documentos" class="table table-hover table-sm mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>Proceso</th>
                    <th>Código</th>
                    <th>Nombre del Documento</th>
                    <th>Tipo</th>
                    <th class="text-center">Versión</th>
                    <th>Últ. Aprobación</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($documentos as $d): ?>
            <tr>
                <td><?= e(($d['macroproceso'] ?? '') . ' — ' . ($d['proceso'] ?? '')) ?></td>
                <td><span style="font-size:12px;background:#f1f5f9;padding:2px 5px;border-radius:3px;"><?= e($d['codigo'] ?? $d['codigo_documento'] ?? '') ?></span></td>
                <td style="font-size:12px;"><?= e($d['nombre_documento'] ?? '') ?></td>
                <td>
                    <span class="badge bg-secondary" style="font-size:12px;">
                        <?= e($d['sigla_tipo_documento'] ?? '') ?><?php if(!empty($d['tipo_documento'])): ?><br><small style='font-size:12px;font-weight:normal;opacity:.85;'><?= e($d['tipo_documento']) ?></small><?php endif; ?>
                    </span>
                    <?php if (!empty($d['tipo_documento'])): ?>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">
                        <?= e($d['tipo_documento']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-center"><span class="badge bg-primary">V<?= e($d['numero_version'] ?? 0) ?></span></td>
                <td style="font-size:12px;"><?= fechaEs($d['fecha_aprobacion'] ?? null) ?></td>
                <td class="text-center"><a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$d['id_documento'] ?>"
               class="btn btn-sm btn-outline-info py-0" title="Ver historial">
                <i class="bi bi-clock-history"></i>
            </a>
            <?php if (!empty($d['id_archivo'])): ?>
            <?php
            $nombreArch = $d['archivo_nombre'] ?? $d['archivo_ruta_legacy'] ?? '';
            $extArch    = strtolower(pathinfo($nombreArch, PATHINFO_EXTENSION));
            $esOffice   = in_array($extArch, ['doc','docx','xls','xlsx','ppt','pptx']);
            $idArch     = (int)$d['id_archivo'];
            $urlVer     = e(APP_URL) . '/archivo/' . $idArch . '?inline=1';
            $urlDesc    = e(APP_URL) . '/archivo/' . $idArch;
            $nomEsc     = addslashes(e($d['nombre_documento'] ?? ''));
            $iconVer    = $esOffice ? 'bi-file-earmark-word' : 'bi-eye';
            $titleVer   = $esOffice ? 'Ver en Office Online' : 'Ver en línea';
            ?>
            <button onclick="abrirVisor('<?= $urlVer ?>','<?= $urlDesc ?>','<?= $nomEsc ?>','')"
                    class="btn btn-sm btn-outline-danger py-0" title="<?= $titleVer ?>">
                <i class="bi <?= $iconVer ?>"></i>
            </button>
            <a href="<?= $urlDesc ?>"
               class="btn btn-sm btn-outline-success py-0" title="Descargar">
                <i class="bi bi-download"></i>
            </a>
            <?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable === 'undefined') return;
    $('#tbl-docs-documentos').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json' },
        pageLength: 25,
        lengthMenu: [[15,25,50,100,-1],['15','25','50','100','Todos']],
        orderFixed: [[0,'asc']],
        columnDefs: [
            { targets: 0, visible: false },
            { targets: [-1], orderable: false }
        ],
        rowGroup: {
            dataSrc: 0,
            startRender: function(rows, group) {
                return $('<tr class="table-primary"/>')
                    .append('<td colspan="100" class="fw-bold py-2">' +
                        '<i class="bi bi-folder2-open me-2"></i>' + group +
                        ' <span class="badge bg-primary ms-2">' + rows.count() + ' doc(s)</span>' +
                    '</td>');
            }
        },
        dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>tip',
        order: [[1,'asc']]
    });
});
</script>

<?php endif; ?>

<script>
const APP_URL = '<?= e(APP_URL) ?>';
function abrirVisor(urlArchivo, urlDescarga, nombreDoc, mimeType) {
    const modal    = new bootstrap.Modal(document.getElementById('modalVisor'));
    const iframe   = document.getElementById('visorIframe');
    const progreso = document.getElementById('visorProgreso');
    const errorDiv = document.getElementById('visorError');
    const titulo   = document.getElementById('visorTitulo');
    document.getElementById('visorDescargar').href     = urlDescarga;
    document.getElementById('visorErrorDescarga').href = urlDescarga;
    titulo.textContent = nombreDoc || 'Documento';
    iframe.src = '';
    errorDiv.classList.add('d-none');
    errorDiv.style.display   = 'none';
    iframe.style.display     = 'block';
    progreso.style.display   = 'block';

    const ext      = urlArchivo.split('?')[0].split('.').pop().toLowerCase();
    const esOffice = ['docx','doc','xlsx','xls','pptx','ppt'].includes(ext);
    let   urlVisor = urlArchivo;

    if (esOffice) {
        const urlAbs = urlArchivo.startsWith('http') ? urlArchivo : window.location.origin + urlArchivo;
        urlVisor = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(urlAbs);
    }

    const timeoutId = setTimeout(function () {
        progreso.style.display = 'none';
        iframe.style.display   = 'none';
        errorDiv.classList.remove('d-none');
        errorDiv.style.display = 'flex';
    }, 12000);

    iframe.onload = function () {
        clearTimeout(timeoutId);
        progreso.style.display = 'none';
    };
    iframe.src = urlVisor;
    modal.show();
}
</script>
