<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-grid-3x3-gap me-2"></i>Análisis DOFA</h2>
        <p class="text-muted mb-0" style="font-size:13px;">
            Contexto de la organización — ISO 9001:2015 Cláusula 4.1
        </p>
    </div>
    <?php if (Auth::puede('contexto_foda','crear')): ?>
    <a href="<?= e(APP_URL) ?>/contexto/foda/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Elemento
    </a>
    <?php endif; ?>
</div>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <?php
    $configs = [
        'FORTALEZA'  => ['bg-success',  'bi-shield-check',   'Fortalezas',   'F'],
        'OPORTUNIDAD'=> ['bg-info',     'bi-graph-up-arrow', 'Oportunidades','O'],
        'DEBILIDAD'  => ['bg-warning text-dark','bi-exclamation-triangle','Debilidades','D'],
        'AMENAZA'    => ['bg-danger',   'bi-lightning',      'Amenazas',     'A'],
    ];
    foreach ($configs as $tipo => [$cls, $ico, $label, $letra]):
    ?>
    <div class="col-6 col-md-3">
        <a href="?tipo=<?= $tipo ?>" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle <?= $cls ?> d-flex align-items-center justify-content-center"
                     style="width:44px;height:44px;min-width:44px;">
                    <i class="bi <?= $ico ?> text-white" style="font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:700;line-height:1;"><?= $resumen[$tipo] ?? 0 ?></div>
                    <div style="font-size:12px;color:#6b7280;"><?= $label ?></div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Tipo</label>
                <select class="form-select form-select-sm" name="tipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach (['FORTALEZA','OPORTUNIDAD','DEBILIDAD','AMENAZA'] as $t): ?>
                    <option value="<?= $t ?>" <?= $filtro_tipo === $t ? 'selected' : '' ?>><?= ucfirst(strtolower($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Impacto</label>
                <select class="form-select form-select-sm" name="impacto" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach (['ALTO','MEDIO','BAJO'] as $imp): ?>
                    <option value="<?= $imp ?>" <?= $filtro_impacto === $imp ? 'selected' : '' ?>><?= ucfirst(strtolower($imp)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($filtro_tipo || $filtro_impacto): ?>
            <a href="/contexto/foda" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x me-1"></i>Limpiar
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla FODA por cuadrantes -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th width="110">Tipo</th>
                    <th>Descripción</th>
                    <th width="80">Impacto</th>
                    <th>Estrategia Asociada</th>
                    <th width="100">Registrado</th>
                    <th width="80" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                [$cls, $ico, $label] = $configs[$item['tipo']];
                $impactoBadge = match($item['impacto']) {
                    'ALTO'  => 'bg-danger',
                    'MEDIO' => 'bg-warning text-dark',
                    'BAJO'  => 'bg-success',
                    default => 'bg-secondary',
                };
            ?>
            <tr>
                <td>
                    <span class="badge <?= $cls ?>" style="font-size:12px;">
                        <i class="bi <?= $ico ?> me-1"></i><?= $label ?>
                    </span>
                </td>
                <td style="font-size:13px;"><?= nl2br(e($item['descripcion'])) ?></td>
                <td class="text-center">
                    <span class="badge <?= $impactoBadge ?>" style="font-size:12px;">
                        <?= $item['impacto'] ?>
                    </span>
                </td>
                <td style="font-size:12px;color:#6b7280;"><?= e($item['estrategia'] ?? '—') ?></td>
                <td style="font-size:12px;">
                    <?= date('d/m/Y', strtotime($item['fecha_registro'])) ?>
                </td>
                <td class="text-center">
                    <?php if (Auth::puede('contexto_foda','editar')): ?>
                    <a href="<?= e(APP_URL) ?>/contexto/foda/editar/<?= $item['id'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (Auth::puede('contexto_foda','eliminar')): ?>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="setModalConfirm('<?= e(APP_URL) ?>/contexto/foda/eliminar/<?= $item['id'] ?>','¿Eliminar este elemento FODA?')">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr><td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-grid-3x3-gap fs-3 d-block mb-2"></i>
                No hay elementos registrados. <a href="<?= e(APP_URL) ?>/contexto/foda/crear">Agregar primero</a>.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
