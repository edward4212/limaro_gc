/**
 * Limaro SGC — JavaScript principal
 * DataTables, confirmaciones, upload, sidebar toggle
 */
'use strict';

// -----------------------------------------------------------------------
// Sidebar toggle — desktop: colapsa | móvil: drawer
// -----------------------------------------------------------------------
(function () {
    // Crear overlay ANTES del DOMContentLoaded para que esté disponible
    var overlay = document.createElement('div');
    overlay.id = 'sidebar-overlay';
    document.addEventListener('DOMContentLoaded', function () {
        document.body.insertBefore(overlay, document.body.firstChild);
    });

    var MOBILE = 991;
    function isMobile() { return window.innerWidth <= MOBILE; }

    function openDrawer(sidebar) {
        sidebar.classList.add('show');
        sidebar.classList.remove('collapsed');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer(sidebar) {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('sidebar');
        var body    = document.body;
        var toggle  = document.getElementById('sidebar-toggle');

        if (!sidebar || !toggle) return;

        // Restaurar estado desktop
        if (!isMobile() && localStorage.getItem('sidebar_collapsed') === '1') {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
        }

        // Toggle principal
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (isMobile()) {
                sidebar.classList.contains('show') ? closeDrawer(sidebar) : openDrawer(sidebar);
            } else {
                var collapsed = sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed', collapsed);
                localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
            }
        });

        // Overlay cierra el drawer
        overlay.addEventListener('click', function () { closeDrawer(sidebar); });

        // Escape cierra el drawer
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isMobile()) closeDrawer(sidebar);
        });

        // Links del sidebar cierran el drawer en móvil
        sidebar.querySelectorAll('.sidebar-link[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) closeDrawer(sidebar);
            });
        });

        // Resize: limpiar estado móvil
        window.addEventListener('resize', function () {
            if (!isMobile()) closeDrawer(sidebar);
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const body    = document.body;
    const toggle  = document.getElementById('sidebar-toggle');

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

    document.querySelectorAll('table.datatable:not([data-no-datatable])').forEach(function (tabla) {
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
        // Soporte de data-order="[[col,dir],...]" para orden inicial personalizado
        var dataOrder = tabla.getAttribute('data-order');
        var orderInit = dataOrder ? JSON.parse(dataOrder) : [[0, 'asc']];

        try {
            var dt = $(tabla).DataTable({
                language  : { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                pageLength: 15,
                lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, 'Todos']],
                responsive: true,
                autoWidth : false,
                orderCellsTop: true,
                order     : orderInit,
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
// Autocomplete de documentos — auto-rellena y bloquea Tipo de Documento
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const inputDoc = document.getElementById('doc_search');
    const hiddenId = document.getElementById('id_documento');
    const listEl   = document.getElementById('doc_list');
    if (!inputDoc || !hiddenId || !listEl) return;

    const selTipo  = document.getElementById('sel_tipo_documento');
    const hidTipo  = document.getElementById('hid_tipo_documento');
    // Sincronizar hidden cuando el select cambia manualmente
    if (selTipo && hidTipo) {
        selTipo.addEventListener('change', function () { hidTipo.value = this.value; });
    }
    const selInfo  = document.getElementById('doc_selected');
    const btnClr   = document.getElementById('doc_clear');
    let debounce;

    function limpiarSeleccion() {
        hiddenId.value = '';
        if (selTipo) { selTipo.value = ''; }
        if (hidTipo) hidTipo.value = '';
        var cc = document.getElementById('campo_codigo_doc');
        var dd = document.getElementById('div_codigo_doc');
        if (cc) cc.value = '';
        if (dd) dd.style.display = 'none';
        if (selInfo) selInfo.style.display = 'none';
        if (btnClr)  btnClr.style.display  = 'none';
        listEl.style.display = 'none';
        listEl.innerHTML     = '';
    }

    inputDoc.addEventListener('input', function () {
        clearTimeout(debounce);
        // Si el usuario edita, limpiar selección previa
        if (hiddenId.value) limpiarSeleccion();
        const q = this.value.trim();
        if (q.length < 2) { listEl.style.display = 'none'; listEl.innerHTML = ''; return; }
        debounce = setTimeout(function () {
            fetch(APP_URL + '/documentos?ajax=buscar&q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    listEl.innerHTML = '';
                    if (!data || data.length === 0) {
                        listEl.innerHTML = '<li class="list-group-item text-muted py-2 ps-3" style="font-size:12px;">Sin resultados</li>';
                        listEl.style.display = 'block'; return;
                    }
                    data.forEach(function (d) {
                        var li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action py-2 ps-3';
                        li.style.cursor = 'pointer';
                        li.style.fontSize = '13px';
                        const sigla = (d.sigla_tipo_documento || '') + (d.tipo_documento ? ' - ' + d.tipo_documento : '');
                        li.innerHTML = '<code style="font-size:11px;background:#f1f5f9;padding:1px 4px;border-radius:3px;">'
                            + (d.codigo || d.codigo_documento) + '</code> '
                            + '<span class="ms-1">' + d.nombre_documento + '</span>'
                            + (sigla ? '<span class="badge bg-secondary ms-2" style="font-size:10px;">' + sigla + '</span>' : '');
                        li.addEventListener('click', function () {
                            inputDoc.value = (d.codigo || d.codigo_documento) + ' — ' + d.nombre_documento;
                            hiddenId.value = d.id_documento;
                            listEl.style.display = 'none';
                            listEl.innerHTML = '';

                            // Auto-rellenar y BLOQUEAR Tipo de Documento
                            if (selTipo && d.id_tipo_documento) {
                                selTipo.value = d.id_tipo_documento;
                            }
                            // Actualizar hidden backup (disabled no envía valor en POST)
                            if (hidTipo && d.id_tipo_documento) {
                                hidTipo.value = d.id_tipo_documento;
                            }

                            // Confirmación visual
                            if (selInfo) {
                                selInfo.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>'
                                    + '<strong>' + (d.codigo || d.codigo_documento) + ' — ' + d.nombre_documento + '</strong>'
                                    + (sigla ? '<span class="ms-2 text-muted">' + sigla + '</span>' : '');
                                selInfo.style.display = 'block';
                            }
                            if (btnClr) btnClr.style.display = 'inline-block';
                        });
                        listEl.appendChild(li);
                    });
                    listEl.style.display = 'block';
                })
                .catch(function () { listEl.style.display = 'none'; });
        }, 280);
    });

    if (btnClr) btnClr.addEventListener('click', function () {
        inputDoc.value = '';
        limpiarSeleccion();
        inputDoc.focus();
    });

    document.addEventListener('click', function (e) {
        if (!inputDoc.contains(e.target) && !listEl.contains(e.target)) {
            listEl.style.display = 'none';
        }
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

// -----------------------------------------------------------------------
// Upload Base64 — convierte archivos a Base64 antes del submit
// Solución para servidores sin upload_tmp_dir configurado.
// Agrega automáticamente campos ocultos: {name}_b64, {name}_mime, {name}_nombre
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    // Procesar todos los formularios con enctype=multipart
    document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var inputs = form.querySelectorAll('input[type="file"]');
            var pendientes = 0;

            // Capturar el botón que disparó el submit ANTES de e.preventDefault()
            // form.submit() programático no incluye el valor del botón → se pierde accion=enviar
            var submitter = e.submitter || null;

            inputs.forEach(function (input) {
                var file = input.files && input.files[0];
                if (!file) return;

                // Verificar si ya existe el campo b64 (por si se re-envía)
                var existente = form.querySelector('input[name="' + input.name + '_b64"]');
                if (existente && existente.value) return;

                pendientes++;
                e.preventDefault();

                var reader = new FileReader();
                reader.onload = function (evt) {
                    // Crear campos ocultos con los datos del archivo
                    var b64Val  = evt.target.result;
                    var mimeVal = file.type || 'application/octet-stream';
                    var nomVal  = file.name;

                    _addHidden(form, input.name + '_b64',    b64Val);
                    _addHidden(form, input.name + '_mime',   mimeVal);
                    _addHidden(form, input.name + '_nombre', nomVal);

                    // Preservar el valor del botón (ej: accion=enviar)
                    // que se pierde al usar form.submit() en lugar del click nativo
                    if (submitter && submitter.name && submitter.value) {
                        _addHidden(form, submitter.name, submitter.value);
                    }

                    // Limpiar el input de archivo (evitar doble envío por $_FILES)
                    input.value = '';

                    pendientes--;
                    if (pendientes === 0) {
                        form.submit();
                    }
                };
                reader.onerror = function () {
                    pendientes--;
                    alert('No se pudo leer el archivo. Intente de nuevo.');
                    if (pendientes === 0) form.submit();
                };
                reader.readAsDataURL(file);
            });
        });
    });

    function _addHidden(form, name, value) {
        var existing = form.querySelector('input[name="' + name + '"]');
        if (existing) {
            existing.value = value;
        } else {
            var inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = name;
            inp.value = value;
            form.appendChild(inp);
        }
    }
});

// -----------------------------------------------------------------------
// Validación global de formularios — campos requeridos
// Usa Bootstrap 5 was-validated + SweetAlert2 toast
// -----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // Excluir forms que no necesitan validación visual
    const SKIP_FORMS = ['#formFoto', '#swal-confirm-form', '[data-novalidate]'];

    document.querySelectorAll('form').forEach(function (form) {
        // Saltar forms excluidos
        if (SKIP_FORMS.some(sel => form.matches(sel))) return;
        // Saltar forms sin campos required
        if (!form.querySelector('[required]')) return;

        form.addEventListener('submit', function (e) {
            // Si el form ya fue interceptado por SweetAlert2, no re-validar
            if (form.dataset.swalConfirmed === '1') return;

            var invalidos = [];
            var primerInvalido = null;

            form.querySelectorAll('[required]').forEach(function (campo) {
                // Ignorar campos deshabilitados o en fieldset disabled
                if (campo.disabled || campo.closest('fieldset[disabled]')) return;

                var vacio = false;
                if (campo.type === 'checkbox' || campo.type === 'radio') {
                    vacio = !campo.checked;
                } else {
                    vacio = !campo.value.trim();
                }

                if (vacio) {
                    // Marcar en rojo
                    campo.classList.add('is-invalid');
                    campo.classList.remove('is-valid');

                    // Crear mensaje si no existe
                    if (!campo.nextElementSibling || !campo.nextElementSibling.classList.contains('invalid-feedback')) {
                        var msg = document.createElement('div');
                        msg.className = 'invalid-feedback';
                        var label = form.querySelector('label[for="' + campo.id + '"]')
                                 || campo.closest('.mb-3, .col-md-3, .col-md-4, .col-md-6, .col-12')
                                    ?.querySelector('label');
                        msg.textContent = 'El campo "' + (label ? label.textContent.replace('*','').trim() : 'requerido') + '" es obligatorio.';
                        campo.parentNode.insertBefore(msg, campo.nextSibling);
                    }

                    invalidos.push(campo);
                    if (!primerInvalido) primerInvalido = campo;
                } else {
                    campo.classList.remove('is-invalid');
                    campo.classList.add('is-valid');
                    // Limpiar mensaje si ya fue corregido
                    var fb = campo.nextElementSibling;
                    if (fb && fb.classList.contains('invalid-feedback')) fb.remove();
                }
            });

            if (invalidos.length > 0) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll al primer campo inválido
                primerInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
                primerInvalido.focus();

                // Toast SweetAlert2
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: invalidos.length === 1
                            ? 'Falta completar 1 campo obligatorio.'
                            : 'Faltan ' + invalidos.length + ' campos obligatorios.',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        iconColor: '#F43F5E',
                    });
                }
                return false;
            }

            // Limpiar todos los is-valid al enviar OK
            form.querySelectorAll('.is-valid').forEach(function(c) {
                c.classList.remove('is-valid');
            });
        });

        // Limpiar el error al escribir en el campo
        form.querySelectorAll('[required]').forEach(function (campo) {
            campo.addEventListener('input', function () {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    var fb = this.nextElementSibling;
                    if (fb && fb.classList.contains('invalid-feedback')) fb.remove();
                }
            });
            campo.addEventListener('change', function () {
                if (this.value.trim() || this.checked) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });
    });

    // Marcar forms confirmados por SweetAlert2 para que no re-validen
    var origSetModal = window.setModalConfirm;
    if (origSetModal) {
        window.setModalConfirm = function(url, msg, titulo) {
            return origSetModal(url, msg, titulo);
        };
    }
});

/* =====================================================================
   RESPONSIVE — table-responsive + ARIA + lazy loading
   ===================================================================== */
(function () {
    'use strict';

    /* Skip link accesibilidad */
    var skip = document.createElement('a');
    skip.href = '#main-content';
    skip.className = 'skip-link';
    skip.textContent = 'Saltar al contenido';
    document.addEventListener('DOMContentLoaded', function () {
        document.body.insertBefore(skip, document.body.firstChild);
    });

    /* table-responsive automático (excluye DataTables) */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table').forEach(function (table) {
            var parent = table.parentElement;
            if (!parent) return;
            if (parent.classList.contains('table-responsive') ||
                parent.classList.contains('table-responsive-auto') ||
                parent.classList.contains('dataTables_scrollBody') ||
                table.classList.contains('dataTable')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-responsive-auto';
            parent.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    });

    /* ARIA */
    document.addEventListener('DOMContentLoaded', function () {
        var nav = document.getElementById('sidebar');
        if (nav && !nav.getAttribute('aria-label')) nav.setAttribute('aria-label', 'Menú principal');
        var mc = document.getElementById('main-content');
        if (mc) { mc.setAttribute('role', 'main'); mc.setAttribute('tabindex', '-1'); }
    });

    /* Lazy loading */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('img:not([loading])').forEach(function (img) {
            if (!img.closest('#sidebar') && !img.closest('#topbar')) {
                img.setAttribute('loading', 'lazy');
            }
        });
    });

})();
