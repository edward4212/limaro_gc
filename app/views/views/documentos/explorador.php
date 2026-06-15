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
    <h6 class="text-muted text-uppercase fw-bold px-1" style="font-size:12px;letter-spacing:1px;">
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
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"
     style="max-width:min(98vw,1400px);width:98vw;">
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
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:min(95vw,1100px);width:95vw;">
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
      <div class="modal-body p-0" id="cuerpoDocs" style="max-height:72vh;overflow-y:auto;">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<style>
.card-proceso:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(30,95,191,.18)!important; }
.card-tipo  { cursor:pointer; border:2px solid transparent; transition:.15s; }
.card-tipo:hover { border-color:var(--lim-blue); background:#f0f6ff; }
.badge-vigente   { background:#198754; color:#fff; font-size:12px; padding:3px 7px; border-radius:20px; }
.badge-sin-arch  { background:#dc3545; color:#fff; font-size:12px; padding:3px 7px; border-radius:20px; }
</style>


<!-- HU-015: Modal visor de documentos (PDF, Word, Excel) -->
<div class="modal fade" id="modalVisor" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"
       style="max-width:min(96vw,1200px);width:96vw;">
    <div class="modal-content" style="height:90vh;">
      <div class="modal-header py-2"
           style="background:linear-gradient(135deg,var(--lim-blue-dark),var(--lim-blue));color:#fff;">
        <span class="modal-title" id="visorTitulo" style="font-size:14px;font-weight:600;"></span>
        <div class="d-flex gap-2 ms-auto">
          <a id="visorDescargar" href="#" class="btn btn-sm btn-outline-light py-0" title="Descargar">
            <i class="bi bi-download me-1"></i>Descargar
          </a>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <!-- Barra de progreso HU-015 CA-2 -->
      <div id="visorProgreso" class="progress" style="height:3px;border-radius:0;display:none;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
             style="width:100%;"></div>
      </div>
      <!-- Mensaje de error con fallback HU-015 CA-3 -->
      <div id="visorError" class="d-none flex-column align-items-center justify-content-center p-5 text-center"
           style="height:calc(90vh - 60px);">
        <i class="bi bi-exclamation-circle text-danger" style="font-size:48px;"></i>
        <p class="mt-3 text-muted">No se pudo cargar el visor en línea.</p>
        <a id="visorErrorDescarga" href="#" class="btn btn-lim-primary mt-2">
          <i class="bi bi-download me-1"></i>Descargar documento
        </a>
      </div>
      <!-- Iframe del visor -->
      <iframe id="visorIframe"
              style="width:100%;height:calc(90vh - 63px);border:none;"
              allow="fullscreen"
              title="Visor de documento"></iframe>
    </div>
  </div>
</div>


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
                    <span style="font-size:12px;">${esc(s.sigla_subproceso)}</span>
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

// ── Renderizar tabla de documentos ────────────────────────────────
const POR_PAGINA = 10;
let _docsActuales = [];
let _paginaActual = 1;

function renderDocumentos(docs, pagina) {
    _docsActuales = docs || [];
    _paginaActual = pagina || 1;

    if (_docsActuales.length === 0) {
        document.getElementById('cuerpoDocs').innerHTML =
            '<p class="text-muted text-center py-4"><i class="bi bi-inbox me-2"></i>No hay documentos vigentes.</p>';
        return;
    }

    // Calcular paginación
    const total   = _docsActuales.length;
    const totalPag = Math.ceil(total / POR_PAGINA);
    const desde   = (_paginaActual - 1) * POR_PAGINA;
    const hasta   = Math.min(desde + POR_PAGINA, total);
    const docsPag = _docsActuales.slice(desde, hasta);

    let html = `<table class="table table-hover table-sm mb-0">
        <thead class="table-light"><tr>
            <th style="width:115px">Código</th>
            <th>Nombre</th>
            <th style="width:75px" class="text-center">Ver.</th>
            <th style="width:105px">Vigencia</th>
            <th style="width:120px" class="text-center">Acciones</th>
        </tr></thead><tbody>`;
    docsPag.forEach(d => {
        // Puede tener archivo en tabla archivo (nuevo) o ruta legada en versionamiento.documento
        const tieneArch = (d.id_archivo && d.id_archivo > 0) || (d.archivo_ruta && d.archivo_ruta.trim() !== '');
        const dot = tieneArch
            ? '<i class="bi bi-circle-fill text-success me-1" style="font-size:12px;"></i>'
            : '<i class="bi bi-circle text-danger me-1" style="font-size:12px;"></i>';
        const urlVer      = d.id_archivo > 0
            ? `${APP_URL}/archivo/${d.id_archivo}?inline=1`
            : `${APP_URL}/archivo/v/${d.id_versionamiento}?inline=1`;
        const urlDescarga = d.id_archivo > 0
            ? `${APP_URL}/archivo/${d.id_archivo}`
            : `${APP_URL}/archivo/v/${d.id_versionamiento}`;

        // HU-015: detectar extensión para pasar al visor
        const ext = (urlVer.split('?')[0]).split('.').pop().toLowerCase();
        const esOffice = ['docx','doc','xlsx','xls','pptx','ppt'].includes(ext);
        const labelVer = esOffice ? 'Word/Excel' : 'Ver PDF';
        const iconVer  = esOffice ? 'bi-file-earmark-spreadsheet' : 'bi-eye';

        const btns = tieneArch
            ? `<button onclick="abrirVisor('${urlVer}','${urlDescarga}','${esc(d.nombre_documento)}','')"
                  class="btn btn-sm btn-outline-danger py-0 px-2 me-1"
                  title="${labelVer} — Visor en línea">
                  <i class="bi ${iconVer}"></i></button>
               <a href="${urlDescarga}"
                  class="btn btn-sm btn-outline-primary py-0 px-2" title="Descargar">
                  <i class="bi bi-download"></i></a>`
            : '<span class="text-muted" style="font-size:12px;">Sin archivo</span>';
        // Tomar solo YYYY-MM-DD (los primeros 10 caracteres) para evitar
        // que la hora afecte el parseo en diferentes zonas horarias
        const fechaStr = d.fecha_aprobacion ? String(d.fecha_aprobacion).substring(0, 10) : null;
        const partesFecha = fechaStr ? fechaStr.split('-') : null;
        const fecha = partesFecha && partesFecha.length === 3
            ? `${partesFecha[2]}/${partesFecha[1]}/${partesFecha[0]}`
            : '—';
        const desc = d.descripcion_version || d.objetivo_documento || '';
        html += `<tr>
            <td><span style="font-size:12px;background:#f1f5f9;padding:2px 5px;border-radius:3px;">${esc(d.codigo)}</span></td>
            <td>
                <div style="font-size:13px;font-weight:500;">${dot}${esc(d.nombre_documento)}</div>
                ${desc ? `<small class="text-muted d-block" style="font-size:12px;">${esc(desc).substring(0,100)}${desc.length>100?'...':''}</small>` : ''}
                ${d.nombre_subproceso ? `<span class="badge bg-info text-dark mt-1" style="font-size:12px;">${esc(d.nombre_subproceso)}</span>` : ''}
            </td>
            <td class="text-center"><span class="badge bg-primary">v${d.numero_version}</span></td>
            <td><small style="font-size:12px;">${fecha}</small></td>
            <td class="text-center">${btns}</td>
        </tr>`;
    });
    html += '</tbody></table>';

    // Controles de paginación
    if (totalPag > 1) {
        html += `<nav class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <small class="text-muted">Mostrando ${desde+1}–${hasta} de ${total} documentos</small>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item ${_paginaActual === 1 ? 'disabled' : ''}">
                    <button class="page-link" onclick="renderDocumentos(_docsActuales, ${_paginaActual - 1})">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                </li>`;
        for (let i = 1; i <= totalPag; i++) {
            html += `<li class="page-item ${i === _paginaActual ? 'active' : ''}">
                <button class="page-link" onclick="renderDocumentos(_docsActuales, ${i})">${i}</button>
            </li>`;
        }
        html += `   <li class="page-item ${_paginaActual === totalPag ? 'disabled' : ''}">
                    <button class="page-link" onclick="renderDocumentos(_docsActuales, ${_paginaActual + 1})">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </li>
            </ul>
        </nav>`;
    } else {
        html += `<div class="px-3 py-1 border-top">
            <small class="text-muted">${total} documento(s)</small>
        </div>`;
    }

    document.getElementById('cuerpoDocs').innerHTML = html;
}


// ── HU-015: Visor de documentos inline ───────────────────────────────────
function abrirVisor(urlArchivo, urlDescarga, nombreDoc, mimeType) {
    const modal      = new bootstrap.Modal(document.getElementById('modalVisor'));
    const iframe     = document.getElementById('visorIframe');
    const progreso   = document.getElementById('visorProgreso');
    const errorDiv   = document.getElementById('visorError');
    const titulo     = document.getElementById('visorTitulo');
    const btnDescarg = document.getElementById('visorDescargar');
    const btnErrDesc = document.getElementById('visorErrorDescarga');

    titulo.textContent  = nombreDoc || 'Documento';
    btnDescarg.href     = urlDescarga;
    btnErrDesc.href     = urlDescarga;
    iframe.src          = '';
    errorDiv.classList.add('d-none');
    errorDiv.style.display = 'none';
    iframe.style.display   = 'block';
    progreso.style.display = 'block';  // CA-2: mostrar barra de progreso

    // Detectar tipo de archivo
    const ext = (urlArchivo.split('?')[0]).split('.').pop().toLowerCase();
    const esOffice = ['docx','doc','xlsx','xls','pptx','ppt'].includes(ext);
    const esPDF    = ext === 'pdf' || (mimeType || '').includes('pdf');

    let urlVisor = urlArchivo;

    if (esOffice) {
        // CA-1 HU-015: Microsoft Office Online viewer para Word/Excel
        const urlAbs = urlArchivo.startsWith('http') ? urlArchivo
            : window.location.origin + urlArchivo;
        urlVisor = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(urlAbs);
    } else if (esPDF) {
        // PDF: mostrar inline directamente
        urlVisor = urlArchivo;
    }

    // Timeout de 10 segundos para detectar fallo (CA-2 HU-015)
    const timeoutId = setTimeout(function() {
        progreso.style.display = 'none';
        iframe.style.display   = 'none';
        errorDiv.classList.remove('d-none');
        errorDiv.style.display = 'flex';
    }, 10000);

    iframe.onload = function() {
        clearTimeout(timeoutId);
        progreso.style.display = 'none';
    };
    iframe.onerror = function() {
        clearTimeout(timeoutId);
        progreso.style.display = 'none';
        iframe.style.display   = 'none';
        errorDiv.classList.remove('d-none');
        errorDiv.style.display = 'flex';
    };

    iframe.src = urlVisor;
    modal.show();
}

// Helper para escapar HTML
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
