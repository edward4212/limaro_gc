<?php $isEdit = !is_null($item); ?>
<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-exclamation-triangle me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/hallazgos">Hallazgos</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
    <a href="<?= e(APP_URL) ?>/hallazgos" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Datos del Hallazgo</div>
    <div class="card-body">
        <form action="<?= e(APP_URL) ?>/hallazgos/crear" method="POST">
            <?= csrfField() ?>
            <div class="row g-3">

                <!-- Programa de Auditoría -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Programa de Auditoría <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_programa" id="selPrograma" required
                            onchange="cargarProcesosPrograma(this.value)">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($programas ?? [] as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"
                                data-plan="<?= (int)($p['id_plan']??0) ?>"
                                <?= old('id_programa') == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['programa_codigo']) ?> | Plan: <?= e($p['plan_codigo']) ?> — <?= e(mb_strimwidth($p['plan_titulo']??'',0,40,'…')) ?>
                            <?= !empty($p['informe_codigo']) ? ' | Informe: '.e($p['informe_codigo']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tipo y Cláusula -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                    <select class="form-select" name="tipo" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach([
                            'NO_CONFORMIDAD' => 'No Conformidad',
                            'OBSERVACION'    => 'Observación',
                            'OPORTUNIDAD'    => 'Oportunidad de Mejora',
                            'FORTALEZA'      => 'Fortaleza',
                        ] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= old('tipo')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cláusula ISO <small class="text-muted">(Ej: 8.4.1)</small></label>
                    <input type="text" class="form-control" name="clausula_iso"
                           value="<?= e(old('clausula_iso')) ?>" placeholder="Ej: 7.5.3" maxlength="50">
                </div>

                <!-- Proceso Auditado — cargado desde el plan -->
                <div class="col-md-4">
                    <label class="form-label">Proceso Auditado</label>
                    <select class="form-select" name="proceso_auditado" id="selProceso">
                        <option value="">— Seleccione programa primero —</option>
                    </select>
                    <input type="hidden" name="id_proceso" id="hidProceso">
                </div>

                <!-- Descripción -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción del Hallazgo <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="descripcion" rows="3" required
                              placeholder="Describa el hallazgo encontrado..."><?= e(old('descripcion')) ?></textarea>
                </div>

                <!-- Evidencia -->
                <div class="col-12">
                    <label class="form-label">Evidencia Objetiva</label>
                    <textarea class="form-control" name="evidencia" rows="2"
                              placeholder="Registros, documentos o hechos que sustentan el hallazgo..."><?= e(old('evidencia')) ?></textarea>
                </div>

                <!-- Causa y Criterios -->
                <div class="col-md-6">
                    <label class="form-label">Criterios de Auditoría</label>
                    <textarea class="form-control" name="criterios" rows="2"
                              placeholder="Normas o requisitos aplicados..."><?= e(old('criterios')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Causa Raíz</label>
                    <textarea class="form-control" name="causa" rows="2"
                              placeholder="Causa identificada del hallazgo..."><?= e(old('causa')) ?></textarea>
                </div>

                <!-- Riesgos y Recomendación -->
                <div class="col-md-6">
                    <label class="form-label">Riesgos Asociados</label>
                    <textarea class="form-control" name="riesgos" rows="2"
                              placeholder="Riesgos derivados si no se corrige..."><?= e(old('riesgos')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Recomendación</label>
                    <textarea class="form-control" name="recomendacion" rows="2"
                              placeholder="Acciones recomendadas para tratamiento..."><?= e(old('recomendacion')) ?></textarea>
                </div>

                <!-- Responsable — todos los usuarios activos -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Responsable</label>
                    <select class="form-select" name="id_responsable" id="selResponsable"
                            onchange="syncResponsable(this)">
                        <option value="">— Seleccione usuario —</option>
                        <?php foreach ($usuarios ?? [] as $u): ?>
                        <option value="<?= (int)$u['id_empleado'] ?>"
                                data-nombre="<?= e($u['nombre_completo']) ?>"
                                <?= old('id_responsable')==$u['id_empleado']?'selected':'' ?>>
                            <?= e($u['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="responsable" id="hidResponsable"
                           value="<?= e(old('responsable')) ?>">
                </div>

                <!-- Fecha límite -->
                <div class="col-md-4">
                    <label class="form-label">Fecha Límite de Cierre</label>
                    <input type="date" class="form-control" name="fecha_cierre"
                           value="<?= e(old('fecha_cierre')) ?>">
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= e(APP_URL) ?>/hallazgos" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Registrar Hallazgo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function cargarProcesosPrograma(idPrograma) {
    var sel = document.getElementById('selProceso');
    sel.innerHTML = '<option value="">Cargando...</option>';
    if (!idPrograma) {
        sel.innerHTML = '<option value="">— Seleccione programa primero —</option>';
        return;
    }
    fetch('<?= e(APP_URL) ?>/hallazgos/programa/' + idPrograma + '/procesos')
        .then(r => r.json())
        .then(procesos => {
            sel.innerHTML = '<option value="">— Seleccione proceso —</option>';
            procesos.forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.proceso;
                opt.dataset.id = p.id_proceso;
                opt.textContent = (p.sigla_proceso ? p.sigla_proceso + ' — ' : '') + p.proceso;
                sel.appendChild(opt);
            });
        }).catch(() => {
            sel.innerHTML = '<option value="">— Error cargando procesos —</option>';
        });
}

function syncResponsable(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('hidResponsable').value = opt ? (opt.dataset.nombre || '') : '';
}

// Sincronizar id_proceso al cambiar proceso auditado
document.getElementById('selProceso').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    document.getElementById('hidProceso').value = opt ? (opt.dataset.id || '') : '';
});
</script>
