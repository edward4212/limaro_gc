<?php
// Agrupar procesos por macroproceso para mostrar secciones
$porMacro = [];
foreach ($procesos as $p) {
    $porMacro[$p['macroproceso']][] = $p;
}
?>
<div class="page-header">
    <div><h2><i class="bi bi-folder2-open me-2"></i><?= e($pageTitle) ?></h2></div>
</div>

<?php foreach ($porMacro as $macro => $lista): ?>
<div class="mb-2">
    <h6 class="text-muted text-uppercase fw-bold px-1" style="font-size:11px;letter-spacing:1px;">
        <i class="bi bi-layers me-1"></i><?= e($macro) ?>
    </h6>
    <div class="row g-3 mb-4">
        <?php foreach ($lista as $p): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card card-proceso shadow-sm h-100 border-0"
                 style="cursor:pointer; transition:.15s;"
                 onclick="abrirProceso(<?= (int)$p['id_proceso'] ?>, '<?= e(addslashes($p['proceso'])) ?>')">
                <div class="card-body text-center py-3 px-2">
                    <div class="mb-2">
                        <i class="bi bi-folder2 text-primary" style="font-size:1.8rem;"></i>
                    </div>
                    <div class="fw-semibold" style="font-size:13px;line-height:1.3;">
                        <?= e($p['proceso']) ?>
                    </div>
                    <span class="badge bg-primary mt-2"><?= (int)$p['total_documentos'] ?> documento(s)</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- ── Modal nivel 1: Subprocesos o Tipos ───────────────────────────── -->
<div class="modal fade" id="modalNivel1" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloNivel1"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="cuerpoNivel1">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal nivel 2: Tipos (cuando viene de subproceso) ────────────── -->
<div class="modal fade" id="modalNivel2" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloNivel2"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="cuerpoNivel2">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal nivel 3: Listado de documentos ─────────────────────────── -->
<div class="modal fade" id="modalDocs" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tituloDocs"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" id="cuerpoDocs">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<style>
.card-proceso:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(30,95,191,.18)!important; }
.card-tipo  { cursor:pointer; border:2px solid transparent; transition:.15s; }
.card-tipo:hover { border-color:var(--lim-blue); background:#f0f6ff; }
.badge-vigente   { background:#198754; color:#fff; font-size:11px; padding:3px 7px; border-radius:20px; }
.badge-sin-arch  { background:#dc3545; color:#fff; font-size:11px; padding:3px 7px; border-radius:20px; }
</style>

<script>
const APP_URL = '<?= e(APP_URL) ?>';
let ctx = { idProceso: null, nombreProceso: '', idSubproceso: null, nombreSub: '' };

// ── Abrir proceso ─────────────────────────────────────────────────────
function abrirProceso(idProceso, nombreProceso) {
    ctx.idProceso    = idProceso;
    ctx.nombreProceso = nombreProceso;
    ctx.idSubproceso = null;
    ctx.nombreSub    = '';

    document.getElementById('tituloNivel1').textContent = nombreProceso;
    document.getElementById('cuerpoNivel1').innerHTML   =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

    new bootstrap.Modal(document.getElementById('modalNivel1')).show();

    fetch(`${APP_URL}/documentos/explorador/proceso/${idProceso}`)
        .then(r => r.json())
        .then(data => {
            if (data.tiene_subprocesos) {
                renderSubprocesos(data.subprocesos, data.tipos, data.id_proceso);
            } else {
                renderTipos(data.tipos, 'cuerpoNivel1', idProceso, null);
            }
        })
        .catch(() => {
            document.getElementById('cuerpoNivel1').innerHTML =
                '<p class="text-danger text-center py-3">Error al cargar los datos.</p>';
        });
}

// ── Renderizar tarjetas de subprocesos ────────────────────────────────
function renderSubprocesos(subprocesos, tiposDirectos, idProceso) {
    let html = '<div class="p-3">';

    if (subprocesos.length > 0) {
        html += '<h6 class="text-muted mb-3"><i class="bi bi-diagram-3 me-1"></i>Subprocesos</h6>';
        html += '<div class="row g-2 mb-4">';
        subprocesos.forEach(s => {
            html += `
            <div class="col-6 col-md-4">
                <div class="card card-tipo text-center py-3"
                     onclick="abrirSubproceso(${s.id_subproceso},'${esc(s.subproceso)}',${idProceso})">
                    <i class="bi bi-folder2 text-info mb-1" style="font-size:1.5rem;"></i>
                    <div style="font-size:13px;font-weight:600;">${esc(s.subproceso)}</div>
                    <code style="font-size:11px;">${esc(s.sigla_subproceso)}</code>
                </div>
            </div>`;
        });
        html += '</div>';
    }

    // Si además tiene documentos directos al proceso (sin subproceso)
    if (tiposDirectos.length > 0) {
        html += '<h6 class="text-muted mb-3"><i class="bi bi-file-earmark me-1"></i>Tipos sin subproceso</h6>';
        html += renderTiposHTML(tiposDirectos, idProceso, null);
    }

    html += '</div>';
    document.getElementById('cuerpoNivel1').innerHTML = html;
}

// ── Abrir subproceso → modal nivel 2 con tipos ───────────────────────
function abrirSubproceso(idSubproceso, nombreSub, idProceso) {
    ctx.idSubproceso = idSubproceso;
    ctx.nombreSub    = nombreSub;

    document.getElementById('tituloNivel2').textContent = `${ctx.nombreProceso} › ${nombreSub}`;
    document.getElementById('cuerpoNivel2').innerHTML   =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

    new bootstrap.Modal(document.getElementById('modalNivel2')).show();

    fetch(`${APP_URL}/documentos/explorador/proceso/${idProceso}`)
        .then(r => r.json())
        .then(data => {
            // Filtrar tipos que tengan documentos bajo ese subproceso
            // Pedimos al backend los tipos filtrados
            fetch(`${APP_URL}/documentos/explorador/tipo?id_proceso=${idProceso}&id_tipo=0&id_subproceso=${idSubproceso}&solo_tipos=1`)
                .catch(() => null);

            // Mientras tanto usamos los tipos generales del proceso
            renderTipos(data.tipos, 'cuerpoNivel2', idProceso, idSubproceso);
        });
}

// ── Renderizar tarjetas de tipos de documento ─────────────────────────
function renderTipos(tipos, contenedorId, idProceso, idSubproceso) {
    if (!tipos || tipos.length === 0) {
        document.getElementById(contenedorId).innerHTML =
            '<p class="text-muted text-center py-4"><i class="bi bi-inbox me-2"></i>No hay documentos vigentes en este proceso.</p>';
        return;
    }
    document.getElementById(contenedorId).innerHTML =
        '<div class="p-3">' + renderTiposHTML(tipos, idProceso, idSubproceso) + '</div>';
}

function renderTiposHTML(tipos, idProceso, idSubproceso) {
    let html = '<div class="row g-2">';
    tipos.forEach(t => {
        const sub = idSubproceso || 0;
        html += `
        <div class="col-6 col-md-4">
            <div class="card card-tipo text-center py-3"
                 onclick="abrirDocumentos(${idProceso},${t.id_tipo_documento},'${esc(t.tipo_documento)}',${sub})">
                <i class="bi bi-file-earmark-text text-primary mb-1" style="font-size:1.5rem;"></i>
                <div style="font-size:13px;font-weight:600;">${esc(t.tipo_documento)}</div>
                <span class="badge bg-primary mt-1">${t.total_documentos} documento(s)</span>
            </div>
        </div>`;
    });
    html += '</div>';
    return html;
}

// ── Abrir listado de documentos ───────────────────────────────────────
function abrirDocumentos(idProceso, idTipo, nombreTipo, idSubproceso) {
    const titulo = idSubproceso
        ? `${ctx.nombreProceso} › ${ctx.nombreSub} — ${nombreTipo}`
        : `${ctx.nombreProceso} — ${nombreTipo}`;

    document.getElementById('tituloDocs').textContent = titulo;
    document.getElementById('cuerpoDocs').innerHTML   =
        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

    new bootstrap.Modal(document.getElementById('modalDocs')).show();

    const sub = idSubproceso ? `&id_subproceso=${idSubproceso}` : '';
    fetch(`${APP_URL}/documentos/explorador/tipo?id_proceso=${idProceso}&id_tipo=${idTipo}${sub}`)
        .then(r => r.json())
        .then(data => renderDocumentos(data.documentos))
        .catch(() => {
            document.getElementById('cuerpoDocs').innerHTML =
                '<p class="text-danger text-center py-3">Error al cargar los documentos.</p>';
        });
}

// ── Renderizar tabla de documentos ────────────────────────────────────
function renderDocumentos(docs) {
    if (!docs || docs.length === 0) {
        document.getElementById('cuerpoDocs').innerHTML =
            '<p class="text-muted text-center py-4"><i class="bi bi-inbox me-2"></i>No hay documentos vigentes.</p>';
        return;
    }

    let html = `
    <table class="table table-hover table-sm mb-0">
        <thead>
            <tr>
                <th>Macroproceso</th>
                <th>Código</th>
                <th>Nombre del Documento</th>
                <th class="text-center">Versión</th>
                <th>Fecha Vigencia</th>
                <th class="text-center">Descarga</th>
            </tr>
        </thead>
        <tbody>`;

    docs.forEach(d => {
        const tieneArchivo = d.archivo_ruta && d.archivo_ruta.trim() !== '';
        const indicador    = tieneArchivo
            ? '<span class="badge-vigente">●</span>'
            : '<span class="badge-sin-arch">●</span>';
        const btnDescarga  = tieneArchivo
            ? `<a href="${APP_URL}/public${d.archivo_ruta}" target="_blank"
                  class="btn btn-sm btn-outline-primary py-0" title="Descargar">
                  <i class="bi bi-download"></i>
               </a>`
            : '<span class="text-muted">—</span>';

        const fechaVig = d.fecha_aprobacion
            ? new Date(d.fecha_aprobacion).toLocaleDateString('es-CO',
                {year:'numeric',month:'2-digit',day:'2-digit'})
            : '—';

        html += `
        <tr>
            <td><small>${esc(d.macroproceso)}</small></td>
            <td><code style="font-size:11px;">${esc(d.codigo)}</code></td>
            <td>
                <div style="font-size:13px;">${indicador} ${esc(d.nombre_documento)}</div>
                ${d.objetivo_documento
                    ? `<small class="text-muted" style="font-size:11px;">${esc(d.objetivo_documento).substring(0,80)}${d.objetivo_documento.length>80?'...':''}</small>`
                    : ''}
            </td>
            <td class="text-center"><span class="badge bg-primary">V${d.numero_version}</span></td>
            <td><small>${fechaVig}</small></td>
            <td class="text-center">${btnDescarga}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    document.getElementById('cuerpoDocs').innerHTML = html;
}

// Helper para escapar HTML
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
