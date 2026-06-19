<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL).'/riesgos/editar/'.$item['id']
    : e(APP_URL).'/riesgos/crear';
$estados_riesgo = ['IDENTIFICADO','EN_TRATAMIENTO','CONTROLADO','CERRADO'];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-shield-exclamation me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/riesgos">Gestión de Riesgos</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<form action="<?= $action ?>" method="POST">
    <?= csrfField() ?>
<?php if ($soloReapertura ?? false): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-lock-fill fs-5"></i>
    <div>Este riesgo está <strong>CERRADO</strong>. Solo puede reabrirlo cambiando su estado;
        el resto de la información se podrá editar después, una vez reabierto.</div>
</div>
<div class="card mb-4">
    <div class="card-header">Reabrir Riesgo</div>
    <div class="card-body">
        <label class="form-label">Nuevo estado</label>
        <select class="form-select" name="estado" required>
            <option value="IDENTIFICADO">IDENTIFICADO</option>
            <option value="EN_TRATAMIENTO">EN TRATAMIENTO</option>
        </select>
    </div>
</div>
<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/riesgos/ver/<?= (int)$item['id'] ?>" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-warning"><i class="bi bi-unlock me-1"></i>Reabrir</button>
</div>
</form>
<?php else: ?>
<div class="row g-4">
<div class="col-lg-6">
<div class="card mb-4">
    <div class="card-header">Identificación</div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Código</label>
                <input type="text" class="form-control bg-light" value="<?= e($codigo) ?>" readonly>
                <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Proceso <span class="text-danger">*</span></label>
                <select class="form-select" name="id_proceso" required>
                    <option value="">— Seleccione —</option>
                    <?php foreach ($procesos as $p): ?>
                    <option value="<?= (int)$p['id_proceso'] ?>"
                        <?= ($isEdit && (int)$item['id_proceso'] === (int)$p['id_proceso']) ? 'selected' : '' ?>>
                        <?= e($p['proceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción del Riesgo <span class="text-danger">*</span></label>
            <textarea class="form-control" name="descripcion" rows="3" required
                placeholder="¿Qué podría suceder en este proceso?"><?= $isEdit ? e($item['descripcion']) : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Causa</label>
            <textarea class="form-control" name="causa" rows="2"
                placeholder="¿Por qué podría ocurrir?"><?= $isEdit ? e($item['causa'] ?? '') : '' ?></textarea>
        </div>
        <div class="mb-0">
            <label class="form-label">Consecuencia</label>
            <textarea class="form-control" name="consecuencia" rows="2"
                placeholder="¿Qué efecto tendría sobre el proceso o el SGC?"><?= $isEdit ? e($item['consecuencia'] ?? '') : '' ?></textarea>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Evaluación — Riesgo Inherente <small class="text-muted">(antes de controles)</small></div>
    <div class="card-body">
        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <label class="form-label">Probabilidad</label>
                <select class="form-select" name="probabilidad_inherente" id="probInh" onchange="calcularNivel()">
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($isEdit ? $item['probabilidad_inherente'] : 'MEDIO') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Impacto</label>
                <select class="form-select" name="impacto_inherente" id="impInh" onchange="calcularNivel()">
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($isEdit ? $item['impacto_inherente'] : 'MEDIO') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-0">
            <span>Nivel de riesgo resultante:</span>
            <span class="badge" id="badgeNivelInh" style="font-size:13px;">—</span>
            <small class="text-muted ms-auto">Se calcula automáticamente al guardar</small>
        </div>
    </div>
</div>
</div>

<div class="col-lg-6">
<div class="card mb-4">
    <div class="card-header">Tratamiento</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Controles / Acciones de Tratamiento</label>
            <textarea class="form-control" name="tratamiento" rows="3"
                placeholder="¿Qué se va a hacer para mitigar este riesgo?"><?= $isEdit ? e($item['tratamiento'] ?? '') : '' ?></textarea>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Responsable</label>
                <select class="form-select" name="id_responsable">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($empleados as $emp): ?>
                    <option value="<?= (int)$emp['id_empleado'] ?>"
                        <?= ($isEdit && (int)($item['id_responsable'] ?? 0) === (int)$emp['id_empleado']) ? 'selected' : '' ?>>
                        <?= e($emp['nombre_completo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha Planificada</label>
                <input type="date" class="form-control" name="fecha_tratamiento_planificada"
                    value="<?= $isEdit ? e($item['fecha_tratamiento_planificada'] ?? '') : '' ?>">
            </div>
            <?php if ($isEdit): ?>
            <div class="col-md-6">
                <label class="form-label">Fecha Real de Tratamiento</label>
                <input type="date" class="form-control" name="fecha_tratamiento_real"
                    value="<?= e($item['fecha_tratamiento_real'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <?php foreach ($estados_riesgo as $est): ?>
                    <option value="<?= $est ?>" <?= $item['estado'] === $est ? 'selected' : '' ?>>
                        <?= str_replace('_',' ',$est) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isEdit): ?>
<div class="card mb-4">
    <div class="card-header">
        Evaluación — Riesgo Residual <small class="text-muted">(después del tratamiento)</small>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 mb-3" style="font-size:12px;">
            <i class="bi bi-info-circle me-1"></i>
            Evalúe el riesgo residual una vez aplicado el tratamiento, para demostrar que el control fue efectivo.
            Déjelo en blanco si el tratamiento todavía no se ha aplicado.
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Probabilidad Residual</label>
                <select class="form-select" name="probabilidad_residual">
                    <option value="">— Sin evaluar —</option>
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($item['probabilidad_residual'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Impacto Residual</label>
                <select class="form-select" name="impacto_residual">
                    <option value="">— Sin evaluar —</option>
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($item['impacto_residual'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if (!empty($item['nivel_riesgo_residual'])): ?>
        <div class="alert alert-light border d-flex align-items-center gap-2 mt-3 mb-0">
            <span>Nivel residual actual:</span>
            <span class="badge bg-<?= ['ALTO'=>'danger','MEDIO'=>'warning','BAJO'=>'success'][$item['nivel_riesgo_residual']] ?? 'secondary' ?>">
                <?= e($item['nivel_riesgo_residual']) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/riesgos" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
</div>
</form>
<?php endif; ?>

<script>
function calcularNivel() {
    var probEl = document.getElementById('probInh');
    var impEl  = document.getElementById('impInh');
    var badge  = document.getElementById('badgeNivelInh');
    if (!probEl || !impEl || !badge) return; // modo reapertura: estos elementos no existen
    var matriz = {
        ALTO:  {ALTO:'ALTO',  MEDIO:'ALTO',  BAJO:'MEDIO'},
        MEDIO: {ALTO:'ALTO',  MEDIO:'MEDIO', BAJO:'BAJO'},
        BAJO:  {ALTO:'MEDIO', MEDIO:'BAJO',  BAJO:'BAJO'}
    };
    var colores = {ALTO:'bg-danger', MEDIO:'bg-warning', BAJO:'bg-success'};
    var nivel = matriz[probEl.value][impEl.value];
    badge.textContent = nivel;
    badge.className = 'badge ' + colores[nivel];
}
document.addEventListener('DOMContentLoaded', calcularNivel);
</script>
