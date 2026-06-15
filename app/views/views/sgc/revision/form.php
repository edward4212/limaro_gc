<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL).'/revision-direccion/editar/'.$item['id']
    : e(APP_URL).'/revision-direccion/crear';
$entradas_9_3_2 = [
    'desempeno_procesos'     => '§9.3.2.a — Desempeño y eficacia del SGC',
    'satisfaccion_partes'    => '§9.3.2.b — Satisfacción del cliente y partes interesadas',
    'resultados_auditorias'  => '§9.3.2.c — Resultados de auditorías',
    'no_conformidades'       => '§9.3.2.d — No conformidades y acciones correctivas',
    'objetivos_calidad'      => '§9.3.2.e — Seguimiento y medición de objetivos',
    'riesgos_opor'           => '§9.3.2.f — Riesgos y oportunidades identificados',
    'recursos'               => '§9.3.2.g — Adecuación de los recursos',
];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-people me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/revision-direccion">Revisión Dirección</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<form action="<?= $action ?>" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
<?php $esAprobada = ($isEdit && ($item['estado'] ?? '') === 'APROBADA'); ?>
<?php if ($esAprobada): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        Esta revisión está <strong>APROBADA</strong> — los campos están en
        <strong>solo lectura</strong>. No se permite ninguna modificación.
    </div>
</div>
<?php endif; ?>
<fieldset <?= $esAprobada ? 'disabled' : '' ?>>
<div class="row g-4">
<div class="col-lg-6">
<div class="card mb-4">
    <div class="card-header">Datos Generales</div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Año</label>
                <input type="number" class="form-control" name="anio" min="2020" max="2099"
                       value="<?= $isEdit ? e($item['anio']) : date('Y') ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Fecha <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="fecha_revision" required
                       value="<?= $isEdit ? e($item['fecha_revision']??'') : date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="BORRADOR" <?= (!$isEdit || ($item['estado']??'')==='BORRADOR')?'selected':'' ?>>Borrador</option>
                    <option value="APROBADA" <?= ($isEdit && ($item['estado']??'')==='APROBADA')?'selected':'' ?>>Aprobada</option>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Convocado por <span class="text-danger">*</span></label>
            <select class="form-select" name="id_usuario_convocador"
                    id="selConvocador" onchange="actualizarConvocador()" required>
                <option value="">-- Seleccione usuario --</option>
                <?php foreach ($usuarios ?? [] as $u): ?>
                <option value="<?= (int)$u['id_usuario'] ?>"
                        data-nombre="<?= e($u['nombre_completo']) ?>"
                        <?= ($isEdit && (int)($item['id_usuario_convocador'] ?? 0) === (int)$u['id_usuario']) ? 'selected' : '' ?>>
                    <?= e($u['nombre_completo']) ?> — <?= e($u['usuario']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="convocado_por" id="hidConvocador"
                   value="<?= $isEdit ? e($item['convocado_por'] ?? '') : '' ?>">
            <script>
            function actualizarConvocador() {
                var sel = document.getElementById('selConvocador');
                var opt = sel.options[sel.selectedIndex];
                document.getElementById('hidConvocador').value = opt ? (opt.dataset.nombre || '') : '';
            }
            document.addEventListener('DOMContentLoaded', actualizarConvocador);
            </script>
        </div>
        <div class="mb-3">
            <label class="form-label">Participantes</label>
            <textarea class="form-control" name="participantes" rows="3"
                      placeholder="Nombres y cargos de los participantes"><?= $isEdit ? e($item['participantes']??'') : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Acta (PDF/imagen)</label>
            <input type="file" class="form-control" name="archivo_acta" accept=".pdf,.jpg,.png">
            <?php if ($isEdit && !empty($item['archivo_acta'])): ?>
            <div class="form-text">
                <a href="<?= e(APP_URL) ?>/archivo-sgc?path=<?= urlencode($item['archivo_acta']) ?>" target="_blank">
                    <i class="bi bi-file-pdf me-1"></i>Acta actual
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Salidas §9.3.3 -->
<div class="card">
    <div class="card-header"><strong>Salidas §9.3.3 — Resultados y Decisiones</strong></div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Oportunidades de mejora del SGC</label>
            <textarea class="form-control" name="mejoras_sgc" rows="3"><?= $isEdit ? e($item['mejoras_sgc']??'') : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Necesidades de cambio en el SGC (salidas)</label>
            <textarea class="form-control" name="salidas" rows="3"><?= $isEdit ? e($item['salidas']??'') : '' ?></textarea>
        </div>
        <div class="mb-0">
            <label class="form-label">Recursos necesarios</label>
            <textarea class="form-control" name="recursos_necesarios" rows="2"><?= $isEdit ? e($item['recursos_necesarios']??'') : '' ?></textarea>
        </div>
    </div>
</div>
</div>
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><strong>Entradas §9.3.2 — Temas Revisados</strong></div>
    <div class="card-body">
        <?php foreach ($entradas_9_3_2 as $campo => $etiqueta): ?>
        <div class="mb-3">
            <label class="form-label" style="font-size:13px;"><?= e($etiqueta) ?></label>
            <textarea class="form-control form-control-sm" name="<?= $campo ?>" rows="2"><?= $isEdit ? e($item[$campo]??'') : '' ?></textarea>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
</div><!-- /row -->
<div class="d-flex gap-2 mt-3 mb-5">
    <?php if (!$esAprobada): ?>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
    <?php endif; ?>
    <a href="<?= e(APP_URL) ?>/revision-direccion" class="btn btn-secondary">Cancelar</a>
</div>
</fieldset>
</form>
