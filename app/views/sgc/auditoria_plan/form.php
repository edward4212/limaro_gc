<?php
$isEdit = !is_null($item);
$procSeleccionados = array_column($item['procesos'] ?? [], 'id_proceso');
?>
<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-calendar3 me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/plan">Planes de Auditoría</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
    <a href="<?= e(APP_URL) ?>/auditoria/plan" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<form action="<?= e(APP_URL) ?>/auditoria/plan/<?= $isEdit ? 'editar/'.(int)$item['id'] : 'crear' ?>" method="POST">
    <?= csrfField() ?>

    <div class="row g-3">
        <!-- Encabezado -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i>Encabezado del Plan</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Código <small class="text-muted">(autogenerado)</small></label>
                            <input type="text" class="form-control bg-light fw-bold" name="codigo"
                                   value="<?= e($codigo) ?>" readonly style="font-family:monospace;color:var(--lim-blue);">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Año <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="anio" required
                                   min="2020" max="2035" value="<?= e(old('anio', $item['anio'] ?? date('Y'))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tipo de Auditoría <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_auditoria" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ([
                                    'CALIDAD'           => 'Auditoría de Calidad',
                                    'ASEGURAMIENTO'     => 'Auditoría de Aseguramiento',
                                    'CUMPLIMIENTO'      => 'Auditoría de Cumplimiento',
                                    'FINANCIERA'        => 'Auditoría Financiera',
                                    'OPERACIONAL'       => 'Auditoría Operacional',
                                    'SISTEMAS'          => 'Auditoría de Sistemas',
                                    'INTEGRAL'          => 'Auditoría Integral',
                                ] as $val => $label): ?>
                                <option value="<?= $val ?>"
                                    <?= old('tipo_auditoria', $item['tipo_auditoria'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="titulo" required maxlength="500"
                                   value="<?= e(old('titulo', $item['titulo'] ?? '')) ?>"
                                   placeholder="Ej: Plan de Auditoría Interna Coopaipe 2026">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio"
                                   value="<?= e(old('fecha_inicio', $item['fecha_inicio'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Fecha Fin</label>
                            <input type="date" class="form-control" name="fecha_fin"
                                   value="<?= e(old('fecha_fin', $item['fecha_fin'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Auditor Líder</label>
                            <input type="text" class="form-control" name="id_auditor_lider"
                                   list="listaAuditores" id="inputAuditor"
                                   value="<?= e(old('id_auditor_lider', $item['auditor_nombre'] ?? '')) ?>"
                                   placeholder="Buscar auditor...">
                            <datalist id="listaAuditores">
                                <?php foreach ($auditores as $a): ?>
                                <option value="<?= e($a['nombre_completo']) ?>" data-id="<?= (int)$a['id_empleado'] ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <input type="hidden" name="id_auditor_lider" id="hidAuditorId"
                                   value="<?= e(old('id_auditor_lider', $item['id_auditor_lider'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Objetivo y alcance -->
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-bullseye me-1"></i>Objetivo, Alcance y Criterios §9.2.1</div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Objetivo General <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="objetivo_general" rows="3" required
                                  placeholder="Objetivo general de la auditoría..."><?= e(old('objetivo_general', $item['objetivo_general'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Objetivos Específicos</label>
                        <textarea class="form-control" name="objetivos_especificos" rows="2"
                                  placeholder="Objetivos específicos (uno por línea)..."><?= e(old('objetivos_especificos', $item['objetivos_especificos'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alcance <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="alcance" rows="3" required
                                  placeholder="Qué está incluido y excluido de la auditoría..."><?= e(old('alcance', $item['alcance'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Criterios</label>
                        <textarea class="form-control" name="criterios" rows="3"
                                  placeholder="Normas, procedimientos, requisitos aplicables..."><?= e(old('criterios', $item['criterios'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Procesos auditados -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-diagram-3 me-1"></i>Procesos Auditados</div>
                <div class="card-body" style="max-height:280px;overflow-y:auto;">
                    <?php foreach ($procesos as $proc): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="id_procesos[]"
                               value="<?= (int)$proc['id_proceso'] ?>"
                               id="proc_<?= $proc['id_proceso'] ?>"
                               <?= in_array($proc['id_proceso'], $procSeleccionados) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="proc_<?= $proc['id_proceso'] ?>"
                               style="font-size:12px;">
                            <span style="font-size:10px;"><?= e($proc['sigla_proceso'] ?? '') ?></span>
                            <?= e($proc['proceso']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer d-print-none" style="font-size:11px;">
                    <a href="#" onclick="document.querySelectorAll('[name=\'id_procesos[]\']').forEach(c=>c.checked=true);return false;">
                        Seleccionar todos
                    </a> ·
                    <a href="#" onclick="document.querySelectorAll('[name=\'id_procesos[]\']').forEach(c=>c.checked=false);return false;">
                        Ninguno
                    </a>
                </div>
            </div>
        </div>

        <!-- Equipo auditor -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-people me-1"></i>Equipo Auditor</div>
                <div class="card-body">
                    <label class="form-label" style="font-size:12px;">
                        Integrantes del equipo (nombres separados por línea)
                    </label>
                    <textarea class="form-control" name="equipo_auditor" rows="7"
                              placeholder="Nombre auditor 1&#10;Nombre auditor 2&#10;..."><?= e(old('equipo_auditor', $item['equipo_auditor'] ?? '')) ?></textarea>
                    <div class="form-text">El auditor líder se registra arriba. Aquí el equipo de apoyo.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="<?= e(APP_URL) ?>/auditoria/plan" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-lim-primary">
            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Actualizar Plan' : 'Crear Plan' ?>
        </button>
    </div>
</form>

<script>
// Sincronizar ID del auditor al seleccionar del datalist
document.getElementById('inputAuditor').addEventListener('change', function() {
    var opts = document.getElementById('listaAuditores').options;
    for (var i = 0; i < opts.length; i++) {
        if (opts[i].value === this.value) {
            document.getElementById('hidAuditorId').value = opts[i].dataset.id || '';
            return;
        }
    }
});
</script>
