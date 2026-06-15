<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-people me-2"></i>§4.2 Partes Interesadas</h2>
        <p class="text-muted mb-0" style="font-size:13px;">
            Partes interesadas pertinentes — ISO 9001:2015 Cláusula 4.2
        </p>
    </div>
    <?php if (Auth::puede('contexto_partes','crear')): ?>
    <a href="<?= e(APP_URL) ?>/contexto/partes-interesadas/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nueva Parte Interesada
    </a>
    <?php endif; ?>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <?php
    $internas  = $resumen['INTERNA']  ?? 0;
    $externas  = $resumen['EXTERNA']  ?? 0;
    $total     = $internas + $externas;
    ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                     style="width:44px;height:44px;min-width:44px;">
                    <i class="bi bi-building text-white" style="font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:700;line-height:1;"><?= $internas ?></div>
                    <div style="font-size:12px;color:#6b7280;">Partes Internas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center"
                     style="width:44px;height:44px;min-width:44px;">
                    <i class="bi bi-globe text-white" style="font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:700;line-height:1;"><?= $externas ?></div>
                    <div style="font-size:12px;color:#6b7280;">Partes Externas</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="background:var(--lim-blue);">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center"
                     style="width:44px;height:44px;min-width:44px;">
                    <i class="bi bi-people text-white" style="font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:26px;font-weight:700;line-height:1;color:#fff;"><?= $total ?></div>
                    <div style="font-size:12px;color:rgba(255,255,255,.7);">Total</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtro -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2">
            <select class="form-select form-select-sm" name="tipo" onchange="this.form.submit()" style="max-width:180px;">
                <option value="">Todas</option>
                <option value="INTERNA"  <?= $filtro_tipo === 'INTERNA'  ? 'selected' : '' ?>>🏢 Internas</option>
                <option value="EXTERNA"  <?= $filtro_tipo === 'EXTERNA'  ? 'selected' : '' ?>>🌐 Externas</option>
            </select>
            <?php if ($filtro_tipo): ?>
            <a href="/contexto/partes-interesadas" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th width="80">Tipo</th>
                    <th>Necesidades</th>
                    <th>Expectativas</th>
                    <th width="80" class="text-center">Influencia</th>
                    <th width="80" class="text-center">Interés</th>
                    <th width="80" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $p):
                $tipoBadge = $p['tipo'] === 'INTERNA' ? 'bg-primary' : 'bg-info';
                $nivelBadge = fn($n) => match($n) { 'ALTO'=>'bg-danger','MEDIO'=>'bg-warning text-dark','BAJO'=>'bg-success', default=>'bg-secondary' };
            ?>
            <tr>
                <td class="fw-semibold" style="font-size:13px;"><?= e($p['nombre']) ?></td>
                <td>
                    <span class="badge <?= $tipoBadge ?>" style="font-size:12px;">
                        <?= $p['tipo'] === 'INTERNA' ? '🏢' : '🌐' ?> <?= $p['tipo'] ?>
                    </span>
                </td>
                <td style="font-size:12px;"><?= e(truncar($p['necesidades'] ?? '—', 60)) ?></td>
                <td style="font-size:12px;"><?= e(truncar($p['expectativas'] ?? '—', 60)) ?></td>
                <td class="text-center">
                    <span class="badge <?= $nivelBadge($p['nivel_influencia']) ?>" style="font-size:12px;">
                        <?= $p['nivel_influencia'] ?>
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge <?= $nivelBadge($p['nivel_interes']) ?>" style="font-size:12px;">
                        <?= $p['nivel_interes'] ?>
                    </span>
                </td>
                <td class="text-center">
                    <?php if (Auth::puede('contexto_partes','editar')): ?>
                    <a href="<?= e(APP_URL) ?>/contexto/partes-interesadas/editar/<?= $p['id'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (Auth::puede('contexto_partes','eliminar')): ?>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="setModalConfirm('<?= e(APP_URL) ?>/contexto/partes-interesadas/eliminar/<?= $p['id'] ?>','¿Eliminar <?= e(addslashes($p['nombre'])) ?>?')">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-people fs-3 d-block mb-2"></i>
                No hay partes interesadas registradas. <a href="<?= e(APP_URL) ?>/contexto/partes-interesadas/crear">Agregar primera</a>.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
