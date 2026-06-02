/**
 * Limaro SGC — JavaScript principal
 * DataTables, confirmaciones, upload, sidebar toggle
 */
'use strict';

// -----------------------------------------------------------------------
// Sidebar toggle
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const body    = document.body;
    const toggle  = document.getElementById('sidebar-toggle');

    if (localStorage.getItem('sidebar_collapsed') === '1') {
        sidebar?.classList.add('collapsed');
        body.classList.add('sidebar-collapsed');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            const collapsed = sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed', collapsed);
            localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
        });
    }

    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && e.target !== toggle) {
            sidebar.classList.remove('show');
        }
    });
    toggle?.addEventListener('click', function () {
        if (window.innerWidth <= 768) sidebar.classList.toggle('show');
    });

    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link[href]').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && currentPath === href) {
            link.classList.add('active');
            let parent = link.closest('.collapse');
            while (parent) {
                parent.classList.add('show');
                const trigger = document.querySelector('[data-bs-target="#' + parent.id + '"]');
                if (trigger) trigger.setAttribute('aria-expanded', 'true');
                parent = parent.parentElement?.closest('.collapse');
            }
        }
    });
});

// -----------------------------------------------------------------------
// DataTables — filtros por columna + ordenamiento
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') return;

    $.fn.dataTable.ext.errMode = 'console';

    // Columnas que NO tendrán filtro ni ordenamiento
    var SKIP_COLS = ['acciones', 'avatar', 'descarga', 'archivo', 'acta'];

    document.querySelectorAll('table.datatable').forEach(function (tabla) {
        if ($.fn.dataTable.isDataTable(tabla)) return;

        var thead   = tabla.querySelector('thead');
        var tbody   = tabla.querySelector('tbody');
        if (!thead || !tbody) return;

        var thCells = Array.from(thead.querySelectorAll('th'));
        var thCount = thCells.length;

        // No inicializar si no hay filas reales de datos
        var filasValidas = Array.from(tbody.querySelectorAll('tr')).filter(function (tr) {
            var tds = tr.querySelectorAll('td');
            return tds.length === thCount;
        });
        if (filasValidas.length === 0) return;

        var hasExport = tabla.classList.contains('datatable-export');
        var hasFilter = !tabla.classList.contains('datatable-nofilter');

        // Calcular índices de columnas NO ordenables
        var noOrderIdx = thCells.reduce(function (acc, th, i) {
            var txt = th.textContent.trim().toLowerCase();
            if (SKIP_COLS.some(function (s) { return txt.indexOf(s) !== -1; })) acc.push(i);
            return acc;
        }, []);

        // ── Crear tfoot con inputs ANTES de inicializar DataTables ──────
        if (hasFilter && !tabla.querySelector('tfoot')) {
            var tfoot = tabla.createTFoot();
            var tfRow = tfoot.insertRow();
            thCells.forEach(function (th, i) {
                var td   = tfRow.insertCell();
                var txt  = th.textContent.trim();
                var skip = noOrderIdx.indexOf(i) !== -1 || !txt;
                if (!skip) {
                    var lbl  = txt.length > 12 ? txt.substring(0, 12) + '…' : txt;
                    var inp  = document.createElement('input');
                    inp.type        = 'text';
                    inp.className   = 'col-filter';
                    inp.placeholder = lbl;
                    inp.setAttribute('data-col', i);
                    td.appendChild(inp);
                }
            });
        }

        // ── Inicializar DataTables ──────────────────────────────────────
        try {
            var dt = $(tabla).DataTable({
                language  : { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                pageLength: 15,
                lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, 'Todos']],
                responsive: true,
                autoWidth : false,
                orderCellsTop: true,
                columnDefs: [
                    { orderable: true,  targets: '_all'     },  // primero habilitar todo
                    { orderable: false, searchable: false, targets: noOrderIdx } // luego deshabilitar acciones
                ],
                dom: hasExport
                    ? '<"row mb-2"<"col-sm-4"l><"col-sm-4 text-center"B><"col-sm-4"f>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>'
                    : '<"row"<"col-sm-6"l><"col-sm-6"f>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
                buttons: hasExport ? [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
                        className: 'btn btn-sm btn-success',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
                        className: 'btn btn-sm btn-danger',
                        orientation: 'landscape', pageSize: 'LETTER',
                        exportOptions: { columns: ':not(:last-child)' }
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer me-1"></i>Imprimir',
                        className: 'btn btn-sm btn-secondary',
                        exportOptions: { columns: ':not(:last-child)' }
                    }
                ] : [],
            });

            // ── Conectar filtros de tfoot DESPUÉS de que DataTables inició ──
            if (hasFilter) {
                var api = dt;

                // Buscar inputs por atributo data-col (más confiable que col.footer())
                tabla.querySelectorAll('tfoot input.col-filter').forEach(function (inp) {
                    var colIdx = parseInt(inp.getAttribute('data-col'), 10);
                    inp.addEventListener('keyup', function () {
                        api.column(colIdx).search(this.value).draw();
                    });
                    inp.addEventListener('change', function () {
                        api.column(colIdx).search(this.value).draw();
                    });
                    // Evitar que el click en el input active el ordenamiento
                    inp.addEventListener('click', function (e) { e.stopPropagation(); });
                });

                // Botón "Limpiar filtros"
                var wrapper = $(tabla).closest('.dataTables_wrapper');
                if (wrapper.length && !wrapper.find('.btn-limpiar-filtros').length) {
                    var btnLimpiar = $('<button>')
                        .addClass('btn btn-sm btn-outline-secondary ms-2 btn-limpiar-filtros')
                        .attr('title', 'Limpiar todos los filtros')
                        .html('<i class="bi bi-x-circle me-1"></i>Limpiar')
                        .on('click', function () {
                            tabla.querySelectorAll('tfoot input.col-filter').forEach(function (inp) {
                                inp.value = '';
                                var colIdx = parseInt(inp.getAttribute('data-col'), 10);
                                api.column(colIdx).search('');
                            });
                            api.search('').draw();
                        });
                    wrapper.find('.dataTables_filter').append(btnLimpiar);
                }
            }

        } catch (e) {
            console.error('DataTable init error:', e, tabla);
        }
    });
});

// -----------------------------------------------------------------------
// Confirmación de acciones destructivas
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const msg = btn.dataset.confirm || '¿Está seguro de realizar esta acción?';
            if (!confirm(msg)) { e.preventDefault(); e.stopPropagation(); }
        });
    });
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm || '¿Está seguro?')) e.preventDefault();
        });
    });
});

// -----------------------------------------------------------------------
// Select dependiente: Macroproceso → Proceso
// -----------------------------------------------------------------------
function cargarProcesos(idMacroproceso, selectEl, valorActual) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">Cargando...</option>';
    if (!idMacroproceso) { selectEl.innerHTML = '<option value="">-- Seleccione --</option>'; return; }
    fetch(APP_URL + '/procesos?ajax=1&id_macroproceso=' + idMacroproceso)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            selectEl.innerHTML = '<option value="">-- Seleccione --</option>';
            data.forEach(function (p) {
                var opt = document.createElement('option');
                opt.value = p.id_proceso;
                opt.textContent = p.proceso + ' (' + p.sigla_proceso + ')';
                if (valorActual && parseInt(valorActual) === parseInt(p.id_proceso)) opt.selected = true;
                selectEl.appendChild(opt);
            });
        })
        .catch(function () { selectEl.innerHTML = '<option value="">Error al cargar</option>'; });
}

document.addEventListener('DOMContentLoaded', function () {
    const selMacro   = document.getElementById('id_macroproceso');
    const selProceso = document.getElementById('id_proceso');
    if (selMacro && selProceso) {
        selMacro.addEventListener('change', function () { cargarProcesos(this.value, selProceso, null); });
        if (selMacro.value) cargarProcesos(selMacro.value, selProceso, selProceso.dataset.current || null);
    }
});

// -----------------------------------------------------------------------
// Autocomplete de documentos
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const inputDoc = document.getElementById('doc_search');
    const hiddenId = document.getElementById('id_documento');
    const listEl   = document.getElementById('doc_list');
    if (!inputDoc || !hiddenId || !listEl) return;
    let debounce;
    inputDoc.addEventListener('input', function () {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { listEl.innerHTML = ''; return; }
        debounce = setTimeout(function () {
            fetch(APP_URL + '/documentos?ajax=buscar&q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    listEl.innerHTML = '';
                    data.forEach(function (d) {
                        var li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.textContent = d.codigo_documento + ' — ' + d.nombre_documento;
                        li.style.cursor = 'pointer';
                        li.addEventListener('click', function () {
                            inputDoc.value = d.codigo_documento + ' — ' + d.nombre_documento;
                            hiddenId.value = d.id_documento;
                            listEl.innerHTML = '';
                        });
                        listEl.appendChild(li);
                    });
                });
        }, 300);
    });
});

// -----------------------------------------------------------------------
// Preview de imagen de perfil
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const inputImg = document.getElementById('img_empleado');
    const preview  = document.getElementById('img_preview');
    if (inputImg && preview) {
        inputImg.addEventListener('change', function () {
            if (this.files[0]) preview.src = URL.createObjectURL(this.files[0]);
        });
    }
});

// -----------------------------------------------------------------------
// Auto-cierre de alertas flash
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-autohide').forEach(function (al) {
        setTimeout(function () {
            al.style.transition = 'opacity .5s';
            al.style.opacity = '0';
            setTimeout(function () { al.remove(); }, 500);
        }, 4000);
    });
});

// Variable global APP_URL
var APP_URL = (function () {
    var meta = document.querySelector('meta[name="app-url"]');
    return meta ? meta.getAttribute('content') : '';
})();
