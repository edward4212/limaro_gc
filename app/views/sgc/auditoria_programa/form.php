<?php
$isEdit = !is_null($item);
$planEstadoForm = $item['plan_estado'] ?? ($isEdit ? 'BORRADOR' : 'BORRADOR');
$puedeEditarObjetivos = $planEstadoForm === 'BORRADOR';
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-text me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/programa">Programas</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
    <a href="<?= e(APP_URL) ?>/auditoria/programa" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<form action="<?= e(APP_URL) ?>/auditoria/programa/<?= $isEdit?'editar/'.(int)$item['id']:'crear' ?>" method="POST">
    <?= csrfField() ?>
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-link me-1"></i>Encabezado del Programa</div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Código y Año -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Código</label>
                    <input type="text" class="form-control bg-light fw-bold" name="codigo"
                           value="<?= e($codigo) ?>" readonly style="font-family:monospace;color:var(--lim-blue);">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Año <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="anio" required
                           min="2020" max="2035" value="<?= e(old('anio',$item['anio']??date('Y'))) ?>">
                </div>

                <!-- Plan vinculado — VA PRIMERO, controla los demás -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar3 me-1"></i>Plan de Auditoría Vinculado
                    </label>
                    <?php if ($isEdit && !empty($item['id_plan'])): ?>
                    <!-- En edición con plan vinculado: solo lectura -->
                    <input type="hidden" name="id_plan" value="<?= (int)$item['id_plan'] ?>">
                    <input type="text" class="form-control bg-light fw-bold"
                           value="<?= e(($item['plan_codigo']??'').' — '.($item['plan_titulo']??'')) ?>"
                           readonly style="color:var(--lim-blue);">
                    <div class="form-text" style="font-size:11px;">
                        <i class="bi bi-lock me-1"></i>El plan vinculado no puede cambiarse.
                    </div>
                    <?php else: ?>
                    <select class="form-select" name="id_plan" id="selPlan" onchange="cargarDatosPlan(this.value)">
                        <option value="">— Sin plan (autónomo) —</option>
                        <?php foreach ($planes as $pl):
                            $yaUsado = in_array($pl['id'], $planesUsados ?? []);
                        ?>
                        <option value="<?= (int)$pl['id'] ?>"
                            <?= old('id_plan',$item['id_plan']??'')==$pl['id']?'selected':'' ?>
                            <?= $yaUsado ? 'disabled style="color:#aaa;"' : '' ?>>
                            <?= e($pl['codigo'].' — '.($pl['titulo']??'')) ?>
                            <?= $yaUsado ? ' (ya tiene programa)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" style="font-size:11px;">
                        <i class="bi bi-info-circle me-1"></i>Al seleccionar un plan, los campos se llenan automáticamente.
                    </div>
                    <?php endif; ?>
                    <!-- Backups hidden para campos disabled -->
                    <input type="hidden" id="hidTipo"    name="tipo_auditoria_hidden">
                    <input type="hidden" id="hidProceso" name="id_proceso_hidden">
                    <input type="hidden" id="hidAuditor" name="id_auditor_lider_hidden">
                </div>

                <!-- Tipo Auditoría -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tipo Auditoría</label>
                    <select class="form-select" name="tipo_auditoria" id="selTipo" disabled>
                        <option value="">— Seleccione —</option>
                        <?php foreach (['CALIDAD'=>'Calidad','ASEGURAMIENTO'=>'Aseguramiento','CUMPLIMIENTO'=>'Cumplimiento','FINANCIERA'=>'Financiera','OPERACIONAL'=>'Operacional','SISTEMAS'=>'Sistemas','INTEGRAL'=>'Integral'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= old('tipo_auditoria',$item['tipo_auditoria']??'')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Proceso Auditado -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Proceso Auditado</label>
                    <select class="form-select" name="id_proceso" id="selProceso" disabled>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($procesos as $p): ?>
                        <option value="<?= (int)$p['id_proceso'] ?>"
                            <?= old('id_proceso',$item['id_proceso']??'')==$p['id_proceso']?'selected':'' ?>>
                            <?= e($p['proceso']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Fecha Auditoría -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Fecha Auditoría</label>
                    <input type="date" class="form-control bg-light" name="fecha_auditoria" id="inpFecha" readonly
                           value="<?= e(old('fecha_auditoria',$item['fecha_auditoria']??'')) ?>">
                </div>

                <!-- Auditor Líder -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Auditor Líder</label>
                    <select class="form-select" name="id_auditor_lider" id="selAuditor" disabled>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($empleados as $emp): ?>
                        <option value="<?= (int)$emp['id_empleado'] ?>"
                            <?= old('id_auditor_lider',$item['id_auditor_lider']??'')==$emp['id_empleado']?'selected':'' ?>>
                            <?= e($emp['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Objetivo -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Objetivo <span class="text-danger">*</span></label>
                    <textarea class="form-control bg-light" name="objetivo" id="txtObjetivo" rows="3" required readonly><?= e(old('objetivo',$item['objetivo']??'')) ?></textarea>
                </div>

                <!-- Alcance -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Alcance</label>
                    <textarea class="form-control bg-light" name="alcance" id="txtAlcance" rows="3" readonly><?= e(old('alcance',$item['alcance']??'')) ?></textarea>
                </div>

                <!-- Equipo Auditor -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Equipo Auditor</label>
                    <textarea class="form-control bg-light" name="auditores" id="txtEquipo" rows="2" readonly
                              placeholder="Integrantes (uno por línea)..."><?= e(old('auditores',$item['auditores']??'')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Objetivos Específicos con sus campos -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check me-1"></i>Objetivos Específicos y Actividades de Auditoría</span>
            <?php if ($puedeEditarObjetivos): ?>
            <button type="button" class="btn btn-lim-primary btn-sm" onclick="agregarObjetivo()">
                <i class="bi bi-plus-circle me-1"></i>Agregar Objetivo
            </button>
            <?php else: ?>
            <span class="badge bg-secondary" style="font-size:11px;">
                <i class="bi bi-lock me-1"></i>Solo lectura — Plan en <?= e($planEstadoForm) ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="card-body" id="contenedorObjetivos">
            <div class="text-muted text-center py-3" id="msgVacio" style="font-size:12px;">
                <i class="bi bi-plus-circle d-block mb-1" style="font-size:1.3rem;"></i>
                Use el botón para agregar objetivos específicos.
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= e(APP_URL) ?>/auditoria/programa" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-lim-primary">
            <i class="bi bi-save me-1"></i><?= $isEdit?'Actualizar':'Crear Programa' ?>
        </button>
    </div>
</form>

<script>
// Cargar objetivos existentes al editar
var objCount = 0;
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($actividades)): ?>
    var existentes = <?= json_encode($actividades ?? []) ?>;
    existentes.forEach(function(a) { agregarObjetivo(a); });
    <?php endif; ?>
    <?php if ($isEdit && !empty($item['id_plan'])): ?>
    // Bloquear campos del plan en modo edición
    bloquearCamposPlan(true);
    <?php endif; ?>
});

function agregarObjetivo(datos) {
    objCount++;
    var i = objCount;
    var d = datos || {};
    document.getElementById('msgVacio').style.display = 'none';
    var soloLectura = <?= $puedeEditarObjetivos ? 'false' : 'true' ?>;
    var readonlyAttr = soloLectura ? 'readonly' : '';
    var disabledStyle = soloLectura ? 'background:#f8f9fa;' : '';
    var html = `
    <div class="border rounded p-3 mb-3 bg-light" id="obj_${i}">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong style="font-size:13px;"><i class="bi bi-target me-1"></i>Objetivo Específico #${i}</strong>
            <?php echo $puedeEditarObjetivos ? '' : ''; ?>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                    style="<?php echo !$puedeEditarObjetivos ? 'display:none' : '' ?>"
                    onclick="document.getElementById('obj_${i}').remove(); checkVacio()">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <input type="hidden" name="obj_id[]" value="${d.id||''}">
        <div class="row g-2">
            <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:12px;">Objetivo Específico <span class="text-danger">*</span></label>
                <textarea class="form-control form-control-sm" name="obj_objetivo[]" rows="2" required ${readonlyAttr} style="${disabledStyle}"
                          placeholder="Describir el objetivo específico...">${d.objetivo_especifico||''}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-size:12px;">Criterio</label>
                <textarea class="form-control form-control-sm" name="obj_criterio[]" rows="2" ${readonlyAttr} style="${disabledStyle}"
                          placeholder="Norma, cláusula ISO, procedimiento...">${d.criterio||''}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-size:12px;">Actividad</label>
                <textarea class="form-control form-control-sm" name="obj_actividad[]" rows="2" ${readonlyAttr} style="${disabledStyle}"
                          placeholder="Actividad de auditoría a realizar...">${d.actividad||''}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-size:12px;">Riesgo</label>
                <textarea class="form-control form-control-sm" name="obj_riesgo[]" rows="2" ${readonlyAttr} style="${disabledStyle}"
                          placeholder="Riesgo identificado...">${d.riesgo||''}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-size:12px;">Procedimiento de Auditoría</label>
                <textarea class="form-control form-control-sm" name="obj_procedimiento[]" rows="2" ${readonlyAttr} style="${disabledStyle}"
                          placeholder="Técnica o procedimiento a aplicar...">${d.procedimiento_auditoria||''}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px;">Observación</label>
                <textarea class="form-control form-control-sm" name="obj_observacion[]" rows="2" ${readonlyAttr} style="${disabledStyle}"
                          placeholder="Observaciones...">${d.observacion||''}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px;">Referenciación</label>
                <input type="text" class="form-control form-control-sm" name="obj_referenciacion[]"
                       placeholder="Art. 4.1, ISO 9001:2015 §8..." value="${d.referenciacion||''}">
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px;">Papel de Trabajo</label>
                <input type="text" class="form-control form-control-sm" name="obj_papel[]"
                       placeholder="PT-001, PT-002..." value="${d.papel_de_trabajo||''}">
            </div>
        </div>
    </div>`;
    var cont = document.getElementById('contenedorObjetivos');
    cont.insertAdjacentHTML('beforeend', html);
}

function checkVacio() {
    var items = document.querySelectorAll('#contenedorObjetivos .border');
    document.getElementById('msgVacio').style.display = items.length === 0 ? 'block' : 'none';
}

function bloquearCamposPlan(bloquear) {
    var ids = ['selTipo','selProceso','selAuditor'];
    ids.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.disabled = bloquear;
    });
    var readonlyIds = ['inpFecha','txtObjetivo','txtAlcance','txtEquipo'];
    readonlyIds.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.readOnly = bloquear;
            el.classList.toggle('bg-light', bloquear);
        }
    });
}

function cargarDatosPlan(id) {
    if (!id) {
        bloquearCamposPlan(false);
        return;
    }
    fetch('<?= e(APP_URL) ?>/auditoria/plan/' + id + '/datos')
        .then(r => r.json())
        .then(d => {
            // Habilitar temporalmente para asignar valores
            bloquearCamposPlan(false);

            var selTipo = document.getElementById('selTipo');
            if (selTipo && d.tipo_auditoria) {
                selTipo.value = d.tipo_auditoria;
                document.getElementById('hidTipo').value = d.tipo_auditoria;
            }

            var inpFecha = document.getElementById('inpFecha');
            if (inpFecha && d.fecha_inicio) inpFecha.value = d.fecha_inicio.substr(0,10);

            var selAud = document.getElementById('selAuditor');
            if (selAud && d.id_auditor_lider) {
                selAud.value = d.id_auditor_lider;
                document.getElementById('hidAuditor').value = d.id_auditor_lider;
            }

            var txtObj = document.getElementById('txtObjetivo');
            if (txtObj && d.objetivo) txtObj.value = d.objetivo;

            var txtAlc = document.getElementById('txtAlcance');
            if (txtAlc && d.alcance) txtAlc.value = d.alcance;

            var txtEq = document.getElementById('txtEquipo');
            if (txtEq && d.auditores) txtEq.value = d.auditores;

            if (d.procesos && d.procesos.length > 0) {
                var selProc = document.getElementById('selProceso');
                if (selProc) {
                    selProc.value = d.procesos[0].id_proceso || '';
                    document.getElementById('hidProceso').value = d.procesos[0].id_proceso || '';
                }
            }

            // Bloquear todos los campos del plan
            bloquearCamposPlan(true);

            Swal.fire({icon:'success', title:'Plan cargado',
                text:'Campos cargados y bloqueados desde el plan.',
                timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
        })
        .catch(() => {});
}
</script>
