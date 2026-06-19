<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<?php
// Puede gestionar actividades si el plan no está CANCELADO ni FINALIZADO
// Solo BORRADOR permite crear/editar/eliminar actividades
$puedeEditar = $plan['estado'] === 'BORRADOR';
$estadosAct  = ['PENDIENTE','EN_CURSO','COMPLETADA','CANCELADA'];
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-calendar-week me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/plan">Planes</a></li>
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$plan['id'] ?>"><?= e($plan['codigo']) ?></a></li>
            <li class="breadcrumb-item active">Cronograma</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <?= badgeEstado($plan['estado']) ?>
        <a href="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$plan['id'] ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al Plan
        </a>
    </div>
</div>

<div class="row g-3" id="seccionForm">
    <!-- Formulario agregar actividad -->
    <?php if ($puedeEditar): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Agregar Actividad</div>
            <div class="card-body">
                <form method="POST"
                      action="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$plan['id'] ?>/actividades">
                    <?= csrfField() ?>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="fecha" required
                                   value="<?= e(isset($plan['fecha_inicio']) ? date('Y-m-d', strtotime($plan['fecha_inicio'])) : date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Hora inicio</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="hIniH" onchange="syncTime('ini')">
                                    <?php for($h=1;$h<=12;$h++): ?><option value="<?= $h ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option><?php endfor; ?>
                                </select>
                                <select class="form-select form-select-sm" id="hIniM" onchange="syncTime('ini')">
                                    <?php foreach(['00','15','30','45'] as $m): ?><option value="<?= $m ?>"><?= $m ?></option><?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm" id="hIniAP" onchange="syncTime('ini')">
                                    <option>AM</option><option>PM</option>
                                </select>
                            </div>
                            <input type="hidden" name="hora_inicio" id="horaInicio">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Hora fin</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="hFinH" onchange="syncTime('fin')">
                                    <?php for($h=1;$h<=12;$h++): ?><option value="<?= $h ?>"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?></option><?php endfor; ?>
                                </select>
                                <select class="form-select form-select-sm" id="hFinM" onchange="syncTime('fin')">
                                    <?php foreach(['00','15','30','45'] as $m): ?><option value="<?= $m ?>"><?= $m ?></option><?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm" id="hFinAP" onchange="syncTime('fin')">
                                    <option>AM</option><option>PM</option>
                                </select>
                            </div>
                            <input type="hidden" name="hora_fin" id="horaFin">
                        </div>
                        <div class="col-12">
                            <div id="divDuracion" class="text-muted" style="font-size:12px;display:none;">
                                ⏱ Duración: <strong id="lblDuracion"></strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Actividad <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" name="actividad" rows="2"
                                      required placeholder="Descripción de la actividad..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Auditado</label>
                            <input type="text" class="form-control form-control-sm" name="auditado"
                                   placeholder="Nombre, cargo o área auditada">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Proceso Auditado</label>
                            <select class="form-select form-select-sm" name="id_proceso_actividad">
                                <option value="">— Sin proceso —</option>
                                <?php foreach ($procesos as $proc): ?>
                                <option value="<?= (int)$proc['id_proceso'] ?>">
                                    <?= e($proc['proceso']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" style="font-size:12px;">Procesos vinculados al plan</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Auditor</label>
                            <select class="form-select form-select-sm" name="id_auditor">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($empleados as $emp): ?>
                                <option value="<?= (int)$emp['id_empleado'] ?>"
                                    <?= (int)$emp['id_empleado'] === (int)($plan['id_auditor_lider']??0) ? 'selected' : '' ?>>
                                    <?= e($emp['nombre_completo']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-lim-primary btn-sm w-100">
                                <i class="bi bi-plus-circle me-1"></i>Agregar Actividad
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cronograma ABAJO, ancho completo -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <strong><i class="bi bi-table me-1"></i>Cronograma de Actividades</strong>
                <span class="badge bg-primary"><?= count($actividades) ?> actividad(es)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividades)): ?>
                <div class="text-center text-muted py-4" style="font-size:13px;">
                    <i class="bi bi-calendar2-x d-block mb-1" style="font-size:1.5rem;"></i>
                    Sin actividades registradas.
                    <?= $puedeEditar ? 'Use el formulario para agregar.' : '' ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Actividad</th>
                            <th>Auditado</th>
                            <th>Proceso</th>
                            <th>Auditor</th>
                            <th class="text-center">Estado</th>
                            <?php if ($puedeEditar): ?><th class="text-center d-print-none">Acc.</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividades as $a): ?>
                        <tr>
                            <td style="white-space:normal;"><?= fechaEs($a['fecha']) ?></td>
                            <td style="white-space:normal;">
                                <?= $a['hora_inicio'] ? substr($a['hora_inicio'],0,5) : '—' ?>
                                <?= $a['hora_fin'] ? '→'.substr($a['hora_fin'],0,5) : '' ?>
                                <?php if ($a['duracion_minutos']): ?>
                                <br><small class="text-muted"><?= (int)($a['duracion_minutos']/60) ?>h<?= $a['duracion_minutos']%60 ?>m</small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($a['actividad']) ?></td>
                            <td><?= e($a['auditado'] ?? '—') ?></td>
                            <td><?= e($a['proceso_nombre'] ?? '—') ?></td>
                            <td><?= e($a['auditor_nombre_completo'] ?? '—') ?></td>
                            <td class="text-center"><?= badgeEstado($a['estado']) ?></td>
                            <?php if ($puedeEditar): ?>
                            <td class="text-center d-print-none">
                                <button class="btn btn-xs btn-outline-primary py-0 px-1"
                                        title="Editar"
                                        onclick="editarActividad(<?= htmlspecialchars(json_encode($a)) ?>)">
                                    <i class="bi bi-pencil" style="font-size:12px;"></i>
                                </button>
                                <form method="POST" style="display:inline;"
                                      action="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$plan['id'] ?>/actividades/eliminar/<?= (int)$a['id'] ?>">
                                    <?= csrfField() ?>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1"
                                            title="Eliminar"
                                            onclick="swalConfirmForm(event, 'Eliminar esta actividad del cronograma.', '¿Eliminar Actividad?')">
                                        <i class="bi bi-trash" style="font-size:12px;"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal editar actividad -->
<div class="modal fade" id="modalEditarAct" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--lim-blue);color:#fff;">
        <h6 class="modal-title mb-0"><i class="bi bi-pencil me-2"></i>Editar Actividad</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditarAct" method="POST">
        <?= csrfField() ?>
        <div class="modal-body">
          <div class="row g-3" id="seccionForm">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Fecha</label>
                <input type="date" class="form-control" name="fecha" id="editFecha" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Hora inicio</label>
                <input type="time" class="form-control" name="hora_inicio" id="editHoraIni">
            </div>
            <div class="col-md-4">
                <label class="form-label">Hora fin</label>
                <input type="time" class="form-control" name="hora_fin" id="editHoraFin">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Actividad</label>
                <textarea class="form-control" name="actividad" id="editActividad" rows="2" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Auditado</label>
                <input type="text" class="form-control" name="auditado" id="editAuditado">
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado" id="editEstado">
                    <?php foreach ($estadosAct as $est): ?>
                    <option value="<?= $est ?>"><?= $est ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Proceso</label>
                <select class="form-select" name="id_proceso_actividad" id="editProceso">
                    <option value="">— Sin proceso —</option>
                    <?php foreach ($procesos as $proc): ?>
                    <option value="<?= (int)$proc['id_proceso'] ?>"><?= e($proc['proceso']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Auditor</label>
                <select class="form-select" name="id_auditor" id="editAuditor">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($empleados as $emp): ?>
                    <option value="<?= (int)$emp['id_empleado'] ?>"><?= e($emp['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-lim-primary btn-sm">
                <i class="bi bi-save me-1"></i>Guardar Cambios
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function to24h(h, m, ap) {
    h = parseInt(h); m = parseInt(m);
    if (ap === 'AM' && h === 12) h = 0;
    if (ap === 'PM' && h !== 12) h += 12;
    return (h<10?'0'+h:h) + ':' + (m<10?'0'+m:m) + ':00';
}
function syncTime(tipo) {
    if (tipo === 'ini') {
        var v = to24h(document.getElementById('hIniH').value,
                      document.getElementById('hIniM').value,
                      document.getElementById('hIniAP').value);
        document.getElementById('horaInicio').value = v;
    } else {
        var v = to24h(document.getElementById('hFinH').value,
                      document.getElementById('hFinM').value,
                      document.getElementById('hFinAP').value);
        document.getElementById('horaFin').value = v;
    }
    calcDuracion();
}
function calcDuracion() {
    var ini = document.getElementById('horaInicio').value;
    var fin = document.getElementById('horaFin').value;
    var div = document.getElementById('divDuracion');
    var lbl = document.getElementById('lblDuracion');
    if (ini && fin) {
        var diff = (new Date('1970-01-01T'+fin) - new Date('1970-01-01T'+ini)) / 60000;
        if (diff > 0) {
            lbl.textContent = Math.floor(diff/60)+'h '+(diff%60)+'m';
            div.style.display = 'block'; return;
        }
    }
    div.style.display = 'none';
}
// Inicializar hidden inputs
document.addEventListener('DOMContentLoaded', function() { syncTime('ini'); syncTime('fin'); });

// Poblar modal de edición
function editarActividad(a) {
    var base = '<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$plan['id'] ?>/actividades/editar/';
    document.getElementById('formEditarAct').action = base + a.id;
    document.getElementById('editFecha').value     = a.fecha || '';
    document.getElementById('editHoraIni').value   = a.hora_inicio ? a.hora_inicio.substr(0,5) : '';
    document.getElementById('editHoraFin').value   = a.hora_fin    ? a.hora_fin.substr(0,5)    : '';
    document.getElementById('editActividad').value = a.actividad || '';
    document.getElementById('editAuditado').value  = a.auditado || '';
    document.getElementById('editEstado').value    = a.estado || 'PENDIENTE';
    document.getElementById('editProceso').value   = a.id_proceso_actividad || '';
    document.getElementById('editAuditor').value   = a.id_auditor || '';
    new bootstrap.Modal(document.getElementById('modalEditarAct')).show();
}
</script>
