<?php
$isEdit    = $item !== null;
$idPadreAct = (int)($item['id_padre'] ?? 0);

// Identificar qué nivel tiene el padre actual (para preseleccionar)
$nivelActual   = 0; // 0=raíz, 1=hijo de raíz, 2=nieto
$raizSelec     = 0;
$hijoSelec     = 0;

if ($idPadreAct) {
    // Buscar si el padre es raíz o hijo
    foreach ($jerarquia as $raiz) {
        if ($raiz['id_modulo'] === $idPadreAct) {
            $raizSelec   = $raiz['id_modulo'];
            $nivelActual = 1;
            break;
        }
        foreach ($raiz['hijos'] as $hijo) {
            if ($hijo['id_modulo'] === $idPadreAct) {
                $raizSelec   = $raiz['id_modulo'];
                $hijoSelec   = $hijo['id_modulo'];
                $nivelActual = 2;
                break 2;
            }
        }
    }
}
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-grid me-2"></i><?= e($pageTitle) ?></h2>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-body">
    <form action="<?= e(APP_URL) ?>/modulos/<?= $isEdit ? 'editar/'.$item['id_modulo'] : 'crear' ?>"
          method="POST">
        <?= csrfField() ?>

        <!-- ── Datos básicos ──────────────────────────────────────── -->
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre"
                       value="<?= e($item['nombre'] ?? '') ?>" required
                       placeholder="Ej: Gestión de Proveedores">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Código</label>
                <input type="text" class="form-control font-monospace" name="codigo"
                       id="inputCodigo"
                       value="<?= e($item['codigo'] ?? '') ?>"
                       placeholder="gestion_proveedores">
                <div class="form-text">Se genera automáticamente desde el nombre.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">URL de la ruta</label>
                <input type="text" class="form-control font-monospace" name="url"
                       value="<?= e($item['url'] ?? '') ?>"
                       placeholder="/proveedores">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Ícono</label>
                <div class="input-group">
                    <span class="input-group-text" style="min-width:40px;">
                        <i class="bi <?= e($item['icono'] ?? 'bi-circle') ?>" id="previewIcon"></i>
                    </span>
                    <input type="text" class="form-control" name="icono" id="inputIcono"
                           value="<?= e($item['icono'] ?? 'bi-circle') ?>"
                           oninput="document.getElementById('previewIcon').className='bi '+this.value">
                </div>
                <div class="form-text">
                    <a href="https://icons.getbootstrap.com" target="_blank">Ver íconos Bootstrap →</a>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Orden</label>
                <input type="number" class="form-control" name="orden"
                       value="<?= $item['orden'] ?? 0 ?>" min="0" max="99">
            </div>
        </div>

        <!-- ── Ubicación en el menú (selects dependientes) ──────────── -->
        <div class="card bg-light mb-3">
            <div class="card-header py-2">
                <strong><i class="bi bi-diagram-3 me-1"></i>Ubicación en el Menú</strong>
                <small class="text-muted ms-2">Seleccione el nivel donde aparecerá este módulo</small>
            </div>
            <div class="card-body">
                <!-- Nivel 1: Módulo Raíz -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <span class="badge bg-primary me-1">Nivel 1</span> Módulo Principal
                    </label>
                    <select class="form-select" id="selRaiz" onchange="actualizarHijos()">
                        <option value="0">— Sin padre (este módulo será raíz) —</option>
                        <?php foreach ($jerarquia as $raiz): ?>
                        <option value="<?= $raiz['id_modulo'] ?>"
                                <?= $raizSelec === $raiz['id_modulo'] ? 'selected' : '' ?>>
                            <i class="bi <?= $raiz['icono'] ?? '' ?>"></i>
                            <?= e($raiz['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nivel 2: Sub-módulo (depende del Nivel 1) -->
                <div class="mb-3" id="divNivel2" style="<?= $raizSelec ? '' : 'display:none;' ?>">
                    <label class="form-label fw-semibold">
                        <span class="badge bg-info text-dark me-1">Nivel 2</span> Sub-módulo
                        <small class="text-muted">(opcional — si es hijo directo del principal)</small>
                    </label>
                    <select class="form-select" id="selHijo" onchange="actualizarNietos()">
                        <option value="0">— Hijo directo del módulo principal —</option>
                        <?php foreach ($jerarquia as $raiz): ?>
                            <?php foreach ($raiz['hijos'] ?? [] as $hijo): ?>
                            <option value="<?= $hijo['id_modulo'] ?>"
                                    data-raiz="<?= $raiz['id_modulo'] ?>"
                                    <?= $hijoSelec === $hijo['id_modulo'] ? 'selected' : '' ?>
                                    style="display:none;">
                                &nbsp;&nbsp;↳ <?= e($hijo['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nivel 3: Sub-sub-módulo (depende del Nivel 2) -->
                <div class="mb-2" id="divNivel3" style="display:none;">
                    <label class="form-label fw-semibold">
                        <span class="badge bg-warning text-dark me-1">Nivel 3</span> Sub-sub-módulo
                        <small class="text-muted">(opcional — si es nieto)</small>
                    </label>
                    <select class="form-select" id="selNieto" onchange="actualizarPadre()">
                        <option value="0">— Hijo directo del sub-módulo —</option>
                        <?php foreach ($jerarquia as $raiz): ?>
                            <?php foreach ($raiz['hijos'] ?? [] as $hijo): ?>
                                <?php foreach ($hijo['hijos'] ?? [] as $nieto): ?>
                                <option value="<?= $nieto['id_modulo'] ?>"
                                        data-hijo="<?= $hijo['id_modulo'] ?>"
                                        style="display:none;">
                                    &nbsp;&nbsp;&nbsp;&nbsp;↳ <?= e($nieto['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Campo oculto que se envía al servidor -->
                <input type="hidden" name="id_padre" id="idPadreFinal"
                       value="<?= $idPadreAct ?: 0 ?>">

                <!-- Resumen visual de la ubicación -->
                <div class="alert alert-info py-2 mt-2" id="resumenUbicacion" style="font-size:12px;">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="textoResumen">
                    <?php
                    if (!$idPadreAct) {
                        echo 'Este módulo aparecerá como <strong>menú principal</strong>.';
                    } elseif ($nivelActual === 1) {
                        echo 'Aparecerá como <strong>sub-menú</strong> de un módulo principal.';
                    } else {
                        echo 'Aparecerá como <strong>opción anidada</strong> dentro de un sub-menú.';
                    }
                    ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Estado -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Estado</label>
            <select class="form-select" name="estado" style="max-width:200px;">
                <option value="ACTIVO"   <?= ($item['estado'] ?? 'ACTIVO') === 'ACTIVO'   ? 'selected' : '' ?>>✅ Activo</option>
                <option value="INACTIVO" <?= ($item['estado'] ?? '') === 'INACTIVO' ? 'selected' : '' ?>>🔴 Inactivo</option>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-lim-primary">
                <i class="bi bi-save me-1"></i><?= $isEdit ? 'Guardar Cambios' : 'Crear Módulo' ?>
            </button>
            <a href="<?= e(APP_URL) ?>/modulos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
</div>
</div>

<script>
// Datos de jerarquía para JS
const jerarquia = <?= json_encode(
    array_map(fn($r) => [
        'id'     => $r['id_modulo'],
        'nombre' => $r['nombre'],
        'hijos'  => array_map(fn($h) => [
            'id'     => $h['id_modulo'],
            'nombre' => $h['nombre'],
            'hijos'  => array_map(fn($n) => [
                'id'     => $n['id_modulo'],
                'nombre' => $n['nombre'],
            ], $h['hijos'] ?? []),
        ], $r['hijos'] ?? []),
    ], $jerarquia)
) ?>;

function actualizarHijos() {
    const raizId = parseInt(document.getElementById('selRaiz').value);
    const selHijo = document.getElementById('selHijo');
    const divN2   = document.getElementById('divNivel2');
    const divN3   = document.getElementById('divNivel3');

    // Mostrar/ocultar nivel 2
    divN2.style.display = raizId ? '' : 'none';
    divN3.style.display = 'none';

    // Filtrar opciones de hijos
    Array.from(selHijo.options).forEach(opt => {
        if (opt.value === '0') return;
        opt.style.display = (parseInt(opt.dataset.raiz) === raizId) ? '' : 'none';
    });
    selHijo.value = '0';

    actualizarPadre();
}

function actualizarNietos() {
    const hijoId  = parseInt(document.getElementById('selHijo').value);
    const selNieto = document.getElementById('selNieto');
    const divN3   = document.getElementById('divNivel3');

    // Mostrar nivel 3 solo si seleccionó un hijo
    divN3.style.display = hijoId ? '' : 'none';

    Array.from(selNieto.options).forEach(opt => {
        if (opt.value === '0') return;
        opt.style.display = (parseInt(opt.dataset.hijo) === hijoId) ? '' : 'none';
    });
    selNieto.value = '0';

    actualizarPadre();
}

function actualizarPadre() {
    const raizId  = parseInt(document.getElementById('selRaiz').value);
    const hijoId  = parseInt(document.getElementById('selHijo').value);
    const nietoId = parseInt(document.getElementById('selNieto')?.value ?? 0);
    const campo   = document.getElementById('idPadreFinal');
    const resumen = document.getElementById('textoResumen');

    let padre = 0;
    let txt   = '';

    if (!raizId) {
        padre = 0;
        txt   = 'Este módulo aparecerá como <strong>menú principal</strong> (sin padre).';
    } else if (!hijoId) {
        padre = raizId;
        const nomRaiz = jerarquia.find(r => r.id === raizId)?.nombre ?? '';
        txt   = `Hijo directo de <strong>${nomRaiz}</strong> → aparece en el sub-menú principal.`;
    } else if (!nietoId) {
        padre = hijoId;
        const nomHijo = jerarquia.flatMap(r=>r.hijos).find(h=>h.id===hijoId)?.nombre ?? '';
        txt   = `Hijo de <strong>${nomHijo}</strong> → aparece anidado en el sub-menú.`;
    } else {
        padre = nietoId;
        const nomNieto = jerarquia.flatMap(r=>r.hijos).flatMap(h=>h.hijos).find(n=>n.id===nietoId)?.nombre ?? '';
        txt   = `Hijo de <strong>${nomNieto}</strong> → nivel 4.`;
    }

    campo.value = padre || '';
    resumen.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + txt;
}

// Auto-generar código desde nombre
document.querySelector('[name="nombre"]')?.addEventListener('input', function() {
    const cod = document.getElementById('inputCodigo');
    if (!cod.dataset.manual) {
        cod.value = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
    }
});
document.getElementById('inputCodigo')?.addEventListener('input', function() {
    this.dataset.manual = '1';
});

// Inicializar si viene en modo edición
document.addEventListener('DOMContentLoaded', function() {
    actualizarHijos();
    <?php if ($raizSelec): ?>
    document.getElementById('selRaiz').value = '<?= $raizSelec ?>';
    actualizarHijos();
    <?php if ($hijoSelec): ?>
    document.getElementById('selHijo').value = '<?= $hijoSelec ?>';
    actualizarNietos();
    <?php endif; ?>
    <?php endif; ?>
});
</script>
