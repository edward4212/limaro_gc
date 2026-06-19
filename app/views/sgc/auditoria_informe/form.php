<?php $isEdit = !is_null($item); ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-file-earmark-text me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/informe">Informes</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
    <a href="<?= e(APP_URL) ?>/auditoria/informe" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<form action="<?= e(APP_URL) ?>/auditoria/informe/<?= $isEdit?'editar/'.(int)$item['id']:'crear' ?>" method="POST">
    <?= csrfField() ?>

    <!-- Encabezado -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-info-circle me-1"></i>Encabezado del Informe</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Código</label>
                    <input type="text" class="form-control bg-light fw-bold" name="codigo"
                           value="<?= e($codigo) ?>" readonly style="font-family:monospace;color:var(--lim-blue);">
                </div>
                <!-- Programa VA AQUÍ — después del código -->
                <div class="col-md-10">
                    <label class="form-label fw-semibold"><i class="bi bi-journal-text me-1"></i>Programa de Auditoría Vinculado</label>
                    <?php if ($isEdit && !empty($item['id_programa'])): ?>
                    <input type="hidden" name="id_programa" value="<?= (int)$item['id_programa'] ?>">
                    <input type="hidden" name="id_plan"     value="<?= (int)$item['id_plan'] ?>">
                    <input type="text" class="form-control bg-light fw-bold"
                           value="<?= e(($item['programa_codigo']??'').' — '.($item['plan_titulo']??'')) ?>" readonly
                           style="color:var(--lim-blue);">
                    <div class="form-text"><i class="bi bi-lock me-1"></i>El programa vinculado no puede cambiarse.</div>
                    <?php else: ?>
                    <select class="form-select" name="id_programa" id="selPrograma" onchange="cargarDatosPrograma(this.value)">
                        <option value="">— Seleccione programa —</option>
                        <?php foreach($programas as $pg): ?>
                        <option value="<?= (int)$pg['id'] ?>"
                            <?= old('id_programa',$item['id_programa']??'')==$pg['id']?'selected':'' ?>
                            data-plan="<?= (int)($pg['id_plan']??0) ?>">
                            <?= e(($pg['codigo']??'').' | Plan: '.($pg['plan_codigo']??'—').' — '.mb_strimwidth($pg['plan_titulo']??'',0,40,'…')) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="id_plan" id="hidPlan">
                    <div class="form-text"><i class="bi bi-info-circle me-1"></i>Solo programas con plan APROBADO o EN CURSO.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tipo Auditoría</label>
                    <select class="form-select" name="tipo_auditoria" id="selTipo">
                        <?php foreach(['CALIDAD'=>'Calidad','ASEGURAMIENTO'=>'Aseguramiento','CUMPLIMIENTO'=>'Cumplimiento','FINANCIERA'=>'Financiera','OPERACIONAL'=>'Operacional'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= old('tipo_auditoria',$item['tipo_auditoria']??'')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="hidTipo" name="tipo_auditoria_hidden">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Fecha Informe</label>
                    <input type="date" class="form-control" name="fecha_informe"
                           value="<?= e(old('fecha_informe',$item['fecha_informe']??date('Y-m-d'))) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Auditor Líder</label>
                    <select class="form-select" name="id_auditor_lider" id="selAuditor">
                        <option value="">— Seleccione —</option>
                        <?php foreach($empleados as $emp): ?>
                        <option value="<?= (int)$emp['id_empleado'] ?>"
                            <?= old('id_auditor_lider',$item['id_auditor_lider']??'')==$emp['id_empleado']?'selected':'' ?>>
                            <?= e($emp['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- Contenido del informe -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-card-text me-1"></i>Contenido del Informe</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Sumario Ejecutivo <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="sumario_ejecutivo" rows="3" required
                              placeholder="Resumen ejecutivo de los resultados de la auditoría..."><?= e(old('sumario_ejecutivo',$item['sumario_ejecutivo']??'')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Objetivo</label>
                    <textarea class="form-control" name="objetivo" rows="2" id="txtObjetivo"><?= e(old('objetivo',$item['objetivo']??'')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Alcance</label>
                    <textarea class="form-control" name="alcance" rows="2" id="txtAlcance"><?= e(old('alcance',$item['alcance']??'')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Criterios de Auditoría</label>
                    <textarea class="form-control" name="criterio_auditoria" rows="2"
                              placeholder="Normas, procedimientos o requisitos aplicados..."><?= e(old('criterio_auditoria',$item['criterio_auditoria']??'')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Antecedentes</label>
                    <textarea class="form-control" name="antecedentes" rows="2"
                              placeholder="Contexto previo de la auditoría..."><?= e(old('antecedentes',$item['antecedentes']??'')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Contextualización</label>
                    <textarea class="form-control" name="contextualizacion" rows="2"
                              placeholder="Entorno y condiciones de la auditoría..."><?= e(old('contextualizacion',$item['contextualizacion']??'')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Resultados de la Auditoría</label>
                    <textarea class="form-control" name="resultados_auditoria" rows="4"
                              placeholder="Descripción detallada de los resultados obtenidos..."><?= e(old('resultados_auditoria',$item['resultados_auditoria']??'')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Evaluación General</label>
                    <textarea class="form-control" name="evaluacion" rows="3"
                              placeholder="Evaluación global del proceso auditado..."><?= e(old('evaluacion',$item['evaluacion']??'')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Opinión del Auditor</label>
                    <textarea class="form-control" name="opinion" rows="3"
                              placeholder="Conclusión y opinión del auditor..."><?= e(old('opinion',$item['opinion']??'')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= e(APP_URL) ?>/auditoria/informe" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-lim-primary">
            <i class="bi bi-save me-1"></i><?= $isEdit?'Actualizar Informe':'Crear Informe' ?>
        </button>
    </div>
</form>

<script>
function cargarDatosPrograma(id) {
    if (!id) return;
    fetch('<?= e(APP_URL) ?>/auditoria/informe/programa/' + id + '/datos')
        .then(r => r.json()).then(d => {
            // Tipo Auditoría
            var st = document.getElementById('selTipo');
            if (st && d.tipo_auditoria) { st.value = d.tipo_auditoria; document.getElementById('hidTipo').value = d.tipo_auditoria; }
            // Auditor Líder del plan
            var sa = document.getElementById('selAuditor');
            if (sa && d.id_auditor_lider) sa.value = d.id_auditor_lider;
            // Alcance del plan
            var talc = document.getElementById('txtAlcance');
            if (talc && d.alcance) talc.value = d.alcance;
            // Objetivo del plan
            var tobj = document.getElementById('txtObjetivo');
            if (tobj && d.objetivo) tobj.value = d.objetivo;
            // id_plan
            var hp = document.getElementById('hidPlan');
            if (hp && d.id_plan) hp.value = d.id_plan;

            Swal.fire({icon:'success', title:'Datos del plan cargados',
                text:'Tipo, Auditor Líder y Alcance cargados desde el plan.',
                timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
        }).catch(()=>{});
}
</script>
