<?php $isEdit = true; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria-interna">Auditoría Interna</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="<?= e(APP_URL) ?>/auditoria-interna" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- CA-2: banner solo lectura -->
<div class="alert alert-success py-2 mb-3" style="font-size:12px;">
    <i class="bi bi-lock me-1"></i>
    <strong>Auditoría Finalizada — Solo Lectura.</strong> No se pueden realizar modificaciones.
</div>
<div class="row g-4">
<div class="col-lg-7">
<div class="card mb-4">
    <div class="card-header">Datos del Programa</div>
    <div class="card-body">
        <div class="ver-readonly">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Año <span class="text-danger">*</span></label>
                    <input disabled type="number" class="form-control bg-light" name="anio" min="2020" max="2099"
                           value="<?= $isEdit ? e($item['anio']) : date('Y') ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Estado</label>
                    <select disabled class="form-select bg-light" name="estado">
                        <?php
                        $hallazgosAb = $isEdit
                            ? (new App\Models\AuditoriaInternaModel())->contarHallazgosAbiertos($item['id'])
                            : 0;
                        foreach (['PROGRAMADA','EN_CURSO','FINALIZADA','CANCELADA'] as $est):
                            $disabled = ($est === 'FINALIZADA' && $hallazgosAb > 0) ? 'disabled' : '';
                            $label    = ucfirst(strtolower(str_replace('_',' ',$est)));
                            if ($est === 'FINALIZADA' && $hallazgosAb > 0) {
                                $label .= " ⚠️ ($hallazgosAb abiertos)";
                            }
                        ?>
                        <option value="<?= $est ?>" <?= ($isEdit && $item['estado']===$est)?'selected':'' ?>
                                <?= $disabled ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if ($hallazgosAb > 0): ?>
                        <option disabled>── Cierre hallazgos para finalizar ──</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción <span class="text-danger">*</span></label>
                <input disabled type="text" class="form-control bg-light" name="descripcion" required
                       value="<?= $isEdit ? e($item['descripcion']) : '' ?>" maxlength="500">
            </div>
            <div class="mb-3">
                <label class="form-label">Objetivo de la Auditoría</label>
                <textarea disabled class="form-control bg-light" name="objetivo" rows="2"><?= $isEdit ? e($item['objetivo']) : '' ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Alcance</label>
                <textarea disabled class="form-control bg-light" name="alcance" rows="2"><?= $isEdit ? e($item['alcance']) : '' ?></textarea>
            </div>
            <!-- CA-1: Auditor Líder filtrado por rol AUDITOR -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Auditor Líder <span class="text-danger">*</span>
                    </label>
                    <select disabled class="form-select bg-light" name="id_auditor_lider" id="selAuditorLider"
                            required onchange="actualizarNombreAuditor()">
                        <option value="">-- Seleccione auditor --</option>
                        <?php foreach ($auditores as $a): ?>
                        <option value="<?= (int)$a['id_usuario'] ?>"
                                data-nombre="<?= e($a['nombre_completo']) ?>"
                                data-correo="<?= e($a['correo_empleado'] ?? '') ?>"
                                <?= ($isEdit && (int)($item['id_auditor_lider'] ?? 0) === (int)$a['id_usuario']) ? 'selected' : '' ?>>
                            <?= e($a['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (empty($auditores)): ?>
                        <option disabled>— No hay usuarios con rol AUDITOR —</option>
                        <?php endif; ?>
                    </select>
                    <input disabled type="hidden" name="auditor_lider" id="hidAuditorLider"
                           value="<?= $isEdit ? e($item['auditor_lider'] ?? '') : '' ?>">
                    <div class="form-text" id="infoAuditor"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Equipo Auditor</label>
                    <input disabled type="text" class="form-control bg-light" name="auditores"
                           placeholder="Otros auditores (separados por coma)"
                           value="<?= $isEdit ? e($item['auditores'] ?? '') : '' ?>">
                </div>
            </div>

            <!-- CA-2: Tipo de auditoría -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo de Auditoría</label>
                    <select disabled class="form-select bg-light" name="tipo_auditoria">
                        <?php
                        $tipos = [
                            'CALIDAD'       => '🏆 Calidad (SGC ISO)',
                            'ASEGURAMIENTO' => '🔒 Aseguramiento de Procesos',
                            'SEGUIMIENTO'   => '📋 Seguimiento',
                            'ESPECIAL'      => '⚡ Especial',
                        ];
                        $tipoAct = $isEdit ? ($item['tipo_auditoria'] ?? 'CALIDAD') : 'CALIDAD';
                        foreach ($tipos as $val => $label):
                        ?>
                        <option value="<?= $val ?>" <?= $tipoAct === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- CA-3: Objetivos específicos -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Objetivos Específicos de la Auditoría</label>
                <textarea disabled class="form-control bg-light" name="objetivos_especificos" rows="3"
                          placeholder="Defina los objetivos específicos que se evaluarán en esta auditoría..."><?= $isEdit ? e($item['objetivos_especificos'] ?? '') : '' ?></textarea>
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Fecha Inicio</label>
                    <input disabled type="date" class="form-control bg-light" name="fecha_inicio"
                           value="<?= $isEdit ? e($item['fecha_inicio']??'') : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha Fin</label>
                    <input disabled type="date" class="form-control bg-light" name="fecha_fin"
                           value="<?= $isEdit ? e($item['fecha_fin']??'') : '' ?>">
                </div>
            </div>
            <div class="d-flex gap-2">
                
                <a href="<?= e(APP_URL) ?>/auditoria-interna" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function actualizarNombreAuditor() {
    const sel   = document.getElementById('selAuditorLider');
    const opt   = sel.options[sel.selectedIndex];
    const hid   = document.getElementById('hidAuditorLider');
    const info  = document.getElementById('infoAuditor');
    if (opt && opt.value) {
        hid.value = opt.dataset.nombre || '';
        if (opt.dataset.correo) {
            info.innerHTML = '<i class="bi bi-envelope me-1"></i>' + opt.dataset.correo;
        }
    } else {
        hid.value = '';
        info.textContent = '';
    }
}
document.addEventListener('DOMContentLoaded', actualizarNombreAuditor);
</script>

<?php if ($isEdit): ?>
<div class="col-lg-5">
<!-- Registrar hallazgo -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Registrar Hallazgo</div>
    <div class="card-body">
        <?php if (in_array($item['estado'] ?? '', ['FINALIZADA','CANCELADA'])): ?>
        <div class="alert alert-warning py-2 mb-0" style="font-size:13px;">
            <i class="bi bi-lock me-1"></i>
            El programa está <strong><?= e($item['estado']) ?></strong> — no se pueden agregar hallazgos.
        </div>
        <?php else: ?>
        <form action="<?= e(APP_URL) ?>/auditoria-interna/hallazgo/<?= (int)$item['id'] ?>" method="POST">
            <?= csrfField() ?>
            <div class="mb-2">
                <label class="form-label" style="font-size:12px;">Tipo</label>
                <select disabled class="form-select form-select-sm" name="tipo">
                    <option value="NO_CONFORMIDAD">No Conformidad</option>
                    <option value="HALLAZGO" selected>Hallazgo</option>
                    <option value="OBSERVACION">Observación</option>
                    <option value="OPORTUNIDAD">Oportunidad de Mejora</option>
                    <option value="FORTALEZA">Fortaleza</option>
                </select>
            </div>
            <div class="row mb-2">
                <div class="col-md-5">
                    <label class="form-label" style="font-size:12px;">Cláusula ISO</label>
                    <input disabled type="text" class="form-control form-control-sm" name="clausula_iso" placeholder="Ej: 7.5.3">
                </div>
                <div class="col-md-7">
                    <label class="form-label" style="font-size:12px;">Proceso Auditado</label>
                    <select disabled class="form-select form-select-sm" name="proceso_auditado">
                        <option value="">— Seleccione proceso —</option>
                        <?php foreach ($procesos ?? [] as $pr): ?>
                        <option value="<?= e($pr['proceso']) ?>">
                            <?= e($pr['proceso']) ?> (<?= e($pr['macroproceso']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label" style="font-size:12px;">Descripción <span class="text-danger">*</span></label>
                <textarea disabled class="form-control form-control-sm" name="descripcion" rows="2" required></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label" style="font-size:12px;">Acción Correctiva</label>
                <textarea disabled class="form-control form-control-sm" name="accion_correctiva" rows="2"></textarea>
            </div>
            <div class="row mb-2">
                <div class="col-md-7">
                    <label class="form-label" style="font-size:12px;">Responsable</label>
                    <input disabled type="text" class="form-control form-control-sm" name="responsable">
                </div>
                <div class="col-md-5">
                    <label class="form-label" style="font-size:12px;">F. Cierre</label>
                    <input disabled type="date" class="form-control form-control-sm" name="fecha_cierre">
                </div>
            </div>
            
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lista hallazgos -->
<?php if (!empty($hallazgos)): ?>
<div class="card">
    <div class="card-header">Hallazgos (<?= count($hallazgos) ?>)</div>
    <div class="list-group list-group-flush">
        <?php foreach ($hallazgos as $h): ?>
        <div class="list-group-item py-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge <?= $h['tipo']==='NO_CONFORMIDAD' ? 'bg-danger' : ($h['tipo']==='FORTALEZA' ? 'bg-success' : 'bg-warning text-dark') ?> me-1" style="font-size:10px;">
                        <?= str_replace('_',' ',$h['tipo']) ?>
                    </span>
                    <?php if ($h['clausula_iso']): ?>
                    <code style="font-size:10px;"><?= e($h['clausula_iso']) ?></span>
                    <?php endif; ?>
                </div>
                <?= badgeEstado($h['estado']) ?>
            </div>
            <p class="mb-0 mt-1" style="font-size:12px;"><?= e(truncar($h['descripcion'],100)) ?></p>
            <?php if ($h['responsable']): ?>
            <small class="text-muted"><i class="bi bi-person me-1"></i><?= e($h['responsable_nombre']??$h['responsable']??'—') ?></small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
