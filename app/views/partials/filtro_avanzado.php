<?php
/**
 * Partial: filtro_avanzado.php
 * Búsqueda avanzada con condiciones encadenadas (AND).
 *
 * Variables esperadas desde quien incluye:
 *   $filtroId   - string: id único para aislar instancias del panel (ej: 'docs-vigentes')
 *   $tablaId    - string: id REAL del elemento <table> en el HTML (ej: 'tbl-docs-documentos').
 *                 Debe coincidir exactamente, ya que DataTables usa este id para
 *                 distinguir a qué tabla aplica la función de búsqueda registrada.
 *   $columnas   - array de columnas filtrables, cada una:
 *                 ['idx'=>int, 'label'=>string, 'categoria'=>string|null]
 *                 - Si 'categoria' está presente (ej: 'proceso', 'tipo_documento',
 *                   'estado_version'), el criterio queda fijo en "Es igual a" y
 *                   el valor se llena con un <select> poblado vía
 *                   /filtro-opciones/{categoria} (ver FiltroOpcionesController).
 *                 - Si no tiene 'categoria', es texto libre con los criterios
 *                   completos (contiene, empieza, termina, igual, etc.)
 */
?>
<div class="filtro-avanzado mb-3" id="fa-<?= e($filtroId) ?>" data-tabla="<?= e($filtroId) ?>">
    <button class="btn btn-sm btn-lim-primary w-auto px-3 fa-toggle" type="button">
        <i class="bi bi-funnel me-1"></i>
        <span class="fa-badge">Búsqueda Avanzada</span>
        <span class="badge bg-light text-dark ms-2 fa-count" style="display:none;">0</span>
    </button>

    <div class="fa-panel card mt-2" style="display:none;">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-muted">Condicion(es) Seleccionada(s)
                    <span class="fa-count-inline">0</span>
                </span>
                <button class="btn btn-xs btn-outline-secondary fa-reset" type="button">
                    <i class="bi bi-x-circle me-1"></i>Restablecer Filtro
                </button>
            </div>
            <div class="fa-condiciones"></div>
            <button class="btn btn-sm btn-outline-primary mt-2 fa-agregar" type="button">
                <i class="bi bi-plus me-1"></i>Agregar Condición
            </button>
        </div>
    </div>

    <script>
    (function () {
        var COLS = <?= json_encode(array_values($columnas)) ?>;
        var CRITERIOS_TEXTO = [
            { k: 'contiene',     l: 'Contiene'     },
            { k: 'no_contiene',  l: 'No contiene'  },
            { k: 'empieza',      l: 'Empieza con'  },
            { k: 'termina',      l: 'Termina en'   },
            { k: 'igual',        l: 'Es igual a'   },
            { k: 'diferente',    l: 'Es diferente' },
        ];
        var cacheOpciones = {}; // categoria -> array de valores (evita pedir 2 veces lo mismo)

        var panelId  = 'fa-<?= e($filtroId) ?>';
        var root     = document.getElementById(panelId);
        var panel    = root.querySelector('.fa-panel');
        var contDiv  = root.querySelector('.fa-condiciones');
        var toggleBtn = root.querySelector('.fa-toggle');
        var countBadge = root.querySelector('.fa-count');
        var countInline = root.querySelector('.fa-count-inline');
        var condiciones = [];   // [{col, crit, val}]
        var dtApi    = null;    // se asigna desde la tabla al init

        function colInfo (idx) {
            return COLS.find(function (c) { return String(c.idx) === String(idx); });
        }

        // Registro global: la tabla llama a FAR.register(tableId, api) en initComplete
        window.FAR = window.FAR || {};
        window.FAR['<?= e($filtroId) ?>'] = {
            setApi: function (api) {
                dtApi = api;
                // Registrar función de filtrado personalizada, específica a esta tabla
                $.fn.dataTable.ext.search.push(function (settings, data) {
                    if (settings.sTableId !== '<?= e($tablaId) ?>') return true;
                    if (condiciones.length === 0) return true;
                    return condiciones.every(function (c) {
                        if (!c.col || !c.crit || !c.val) return true;
                        var celda = (data[c.col] || '').toLowerCase().replace(/<[^>]*>/g, '').trim();
                        var val   = c.val.toLowerCase();
                        var info  = colInfo(c.col);
                        // Columnas con categoría (lista cerrada): coincidencia flexible,
                        // ya que la celda real puede mezclar sigla+nombre u otros textos.
                        if (info && info.categoria) {
                            return celda.indexOf(val) !== -1;
                        }
                        switch (c.crit) {
                            case 'contiene':    return celda.indexOf(val) !== -1;
                            case 'no_contiene': return celda.indexOf(val) === -1;
                            case 'empieza':     return celda.indexOf(val) === 0;
                            case 'termina':     return celda.slice(-val.length) === val;
                            case 'igual':       return celda === val;
                            case 'diferente':   return celda !== val;
                        }
                        return true;
                    });
                });
            }
        };

        function buildSelCol () {
            var sel = document.createElement('select');
            sel.className = 'form-select form-select-sm fa-sel-col';
            var def = document.createElement('option');
            def.value = ''; def.textContent = 'Condición de Búsqueda'; sel.appendChild(def);
            COLS.forEach(function (c) {
                var o = document.createElement('option');
                o.value = c.idx; o.textContent = c.label; sel.appendChild(o);
            });
            return sel;
        }

        function cargarOpciones (categoria, callback) {
            if (cacheOpciones[categoria]) { callback(cacheOpciones[categoria]); return; }
            fetch('<?= e(APP_URL) ?>/filtro-opciones/' + encodeURIComponent(categoria))
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    cacheOpciones[categoria] = json.valores || [];
                    callback(cacheOpciones[categoria]);
                })
                .catch(function () { callback([]); });
        }

        function agregarFila () {
            var idx = condiciones.length;
            condiciones.push({ col: '', crit: '', val: '' });

            var row = document.createElement('div');
            row.className = 'd-flex gap-2 align-items-center mb-2 fa-fila';
            row.dataset.idx = idx;

            var selCol  = buildSelCol();
            var critWrap = document.createElement('div');
            critWrap.className = 'fa-crit-wrap';
            var valWrap = document.createElement('div');
            valWrap.className = 'fa-val-wrap flex-grow-1';

            var btnDel = document.createElement('button');
            btnDel.type      = 'button';
            btnDel.className = 'btn btn-xs btn-outline-danger fa-del';
            btnDel.innerHTML = '<i class="bi bi-x"></i>';

            function renderCritYVal () {
                var info = colInfo(selCol.value);
                critWrap.innerHTML = '';
                valWrap.innerHTML  = '';

                if (!selCol.value) {
                    var critVacio = document.createElement('select');
                    critVacio.className = 'form-select form-select-sm';
                    critVacio.disabled = true;
                    critVacio.innerHTML = '<option>Criterio de Búsqueda</option>';
                    critWrap.appendChild(critVacio);
                    var valVacio = document.createElement('input');
                    valVacio.className = 'form-control form-control-sm';
                    valVacio.placeholder = 'Valor Buscado';
                    valVacio.disabled = true;
                    valWrap.appendChild(valVacio);
                    condiciones[idx].crit = ''; condiciones[idx].val = '';
                    return;
                }

                if (info && info.categoria) {
                    // Criterio fijo: solo "Es igual a"
                    var critFijo = document.createElement('select');
                    critFijo.className = 'form-select form-select-sm fa-sel-crit';
                    critFijo.innerHTML = '<option value="igual">Es igual a</option>';
                    critWrap.appendChild(critFijo);
                    condiciones[idx].crit = 'igual';

                    var selVal = document.createElement('select');
                    selVal.className = 'form-select form-select-sm fa-sel-val';
                    selVal.innerHTML = '<option value="">Cargando…</option>';
                    selVal.disabled = true;
                    valWrap.appendChild(selVal);

                    cargarOpciones(info.categoria, function (valores) {
                        selVal.innerHTML = '';
                        var def = document.createElement('option');
                        def.value = ''; def.textContent = 'Valor Buscado';
                        selVal.appendChild(def);
                        valores.forEach(function (v) {
                            var o = document.createElement('option');
                            o.value = v; o.textContent = v;
                            selVal.appendChild(o);
                        });
                        selVal.disabled = false;
                    });
                    selVal.addEventListener('change', function () {
                        condiciones[idx].val = this.value;
                        dibujar();
                    });
                } else {
                    // Columna de texto libre: criterios completos + input
                    var selCrit = document.createElement('select');
                    selCrit.className = 'form-select form-select-sm fa-sel-crit';
                    var defCrit = document.createElement('option');
                    defCrit.value = ''; defCrit.textContent = 'Criterio de Búsqueda';
                    selCrit.appendChild(defCrit);
                    CRITERIOS_TEXTO.forEach(function (c) {
                        var o = document.createElement('option');
                        o.value = c.k; o.textContent = c.l;
                        selCrit.appendChild(o);
                    });
                    selCrit.addEventListener('change', function () {
                        condiciones[idx].crit = this.value;
                        dibujar();
                    });
                    critWrap.appendChild(selCrit);

                    var inpVal = document.createElement('input');
                    inpVal.type        = 'text';
                    inpVal.className   = 'form-control form-control-sm fa-inp-val';
                    inpVal.placeholder = 'Valor Buscado';
                    inpVal.addEventListener('keyup', function () {
                        condiciones[idx].val = this.value;
                        dibujar();
                    });
                    valWrap.appendChild(inpVal);
                }
            }

            selCol.addEventListener('change', function () {
                condiciones[idx].col = this.value;
                renderCritYVal();
                dibujar();
            });
            btnDel.addEventListener('click', function () {
                condiciones.splice(idx, 1);
                reconstruirFilas();
                dibujar();
            });

            row.appendChild(selCol);
            row.appendChild(critWrap);
            row.appendChild(valWrap);
            row.appendChild(btnDel);
            contDiv.appendChild(row);
            renderCritYVal();
            actualizarContador();
        }

        function reconstruirFilas () {
            contDiv.innerHTML = '';
            var copia = condiciones.slice();
            condiciones = [];
            copia.forEach(function (c) {
                agregarFila();
                var i = condiciones.length - 1;
                var fila = contDiv.querySelectorAll('.fa-fila')[i];
                var selCol = fila.querySelector('.fa-sel-col');
                selCol.value = c.col;
                selCol.dispatchEvent(new Event('change'));
                var info = colInfo(c.col);
                if (info && info.categoria) {
                    // El <select> de valor se llena vía fetch async: esperar a que
                    // termine de cargar antes de fijar la selección visual.
                    var intentos = 0;
                    var esperar = setInterval(function () {
                        var selVal = fila.querySelector('.fa-sel-val');
                        intentos++;
                        if (selVal && !selVal.disabled) {
                            selVal.value = c.val;
                            condiciones[i].val = c.val;
                            clearInterval(esperar);
                        } else if (intentos > 40) { // ~4s máximo de espera
                            clearInterval(esperar);
                        }
                    }, 100);
                } else {
                    var inp = fila.querySelector('.fa-inp-val');
                    if (inp) inp.value = c.val;
                    condiciones[i].val = c.val;
                }
            });
            actualizarContador();
        }

        function dibujar () {
            if (dtApi) dtApi.draw();
            actualizarContador();
        }

        function actualizarContador () {
            var activas = condiciones.filter(function (c) {
                return c.col && c.crit && c.val;
            }).length;
            countBadge.textContent  = activas;
            countInline.textContent = condiciones.length;
            countBadge.style.display = activas > 0 ? '' : 'none';
        }

        toggleBtn.addEventListener('click', function () {
            var visible = panel.style.display !== 'none';
            panel.style.display = visible ? 'none' : '';
        });

        root.querySelector('.fa-reset').addEventListener('click', function () {
            condiciones = [];
            contDiv.innerHTML = '';
            actualizarContador();
            if (dtApi) dtApi.draw();
        });

        root.querySelector('.fa-agregar').addEventListener('click', function () {
            agregarFila();
        });

        // Iniciar con una fila vacía
        agregarFila();
    })();
    </script>
</div>
