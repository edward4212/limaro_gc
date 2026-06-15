<?php
// Uso: include con variables $campos = ['desde','hasta','estado',...]
// y $opciones = ['estados' => [...], 'procesos' => [...], ...]
?>
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" action="" class="row g-2 align-items-end">
            <?php if (in_array('desde', $campos ?? ['desde','hasta'])): ?>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Desde</label>
                <input type="date" class="form-control form-control-sm" name="desde"
                       value="<?= e($filtros['desde'] ?? '') ?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Hasta</label>
                <input type="date" class="form-control form-control-sm" name="hasta"
                       value="<?= e($filtros['hasta'] ?? '') ?>">
            </div>
            <?php endif; ?>

            <?php if (!empty($opciones['procesos'])): ?>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:12px;">Proceso</label>
                <select class="form-select form-select-sm" name="id_proceso">
                    <option value="">Todos</option>
                    <?php foreach ($opciones['procesos'] as $p): ?>
                    <option value="<?= e($p['id_proceso']) ?>"
                        <?= ($filtros['id_proceso'] ?? '') == $p['id_proceso'] ? 'selected' : '' ?>>
                        <?= e($p['proceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($opciones['tipos'])): ?>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:12px;">Tipo</label>
                <select class="form-select form-select-sm" name="id_tipo">
                    <option value="">Todos</option>
                    <?php foreach ($opciones['tipos'] as $t): ?>
                    <option value="<?= e($t['id_tipo_documento']) ?>"
                        <?= ($filtros['id_tipo'] ?? '') == $t['id_tipo_documento'] ? 'selected' : '' ?>>
                        <?= e($t['tipo_documento']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($opciones['estados'])): ?>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Estado</label>
                <select class="form-select form-select-sm" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($opciones['estados'] as $est): ?>
                    <option value="<?= e($est) ?>"
                        <?= ($filtros['estado'] ?? '') === $est ? 'selected' : '' ?>>
                        <?= e($est) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($opciones['anio'])): ?>
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;">Año</label>
                <input type="number" class="form-control form-control-sm" name="anio"
                       min="2020" max="2099" style="width:85px"
                       value="<?= e($filtros['anio'] ?? date('Y')) ?>">
            </div>
            <?php endif; ?>

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-lim-primary">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="?" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>
