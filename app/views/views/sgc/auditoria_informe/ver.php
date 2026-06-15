<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-file-earmark-text me-2"></i><?= e($item['codigo']) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/informe">Informes</a></li>
            <li class="breadcrumb-item active"><?= e($item['codigo']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?= badgeEstado($item['estado']) ?>
        <?php if ($item['estado']==='BORRADOR' && Auth::puede('audit_informe','editar')): ?>
        <a href="<?= e(APP_URL) ?>/auditoria/informe/editar/<?= (int)$item['id'] ?>"
           class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <?php endif; ?>
        <?php if ($item['estado']==='BORRADOR'): ?>
        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/revisar/<?= (int)$item['id'] ?>" style="display:inline;">
            <?= csrfField() ?>
            <button type="button" class="btn btn-warning btn-sm"
                    onclick="swalConfirmForm(event,'Se notificará a los Coordinadores de Calidad.','Enviar a Revisión')">
                <i class="bi bi-send me-1"></i>Enviar a Revisión
            </button>
        </form>
        <?php endif; ?>
        <?php if ($item['estado']==='EN_REVISION' && Auth::hasRole([1,2])): ?>
        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/aprobar/<?= (int)$item['id'] ?>" style="display:inline;">
            <?= csrfField() ?>
            <button type="button" class="btn btn-success btn-sm"
                    onclick="swalConfirmForm(event,'¿Aprobar el informe <?= e(addslashes($item['codigo'])) ?>?','¿Aprobar Informe?')">
                <i class="bi bi-check2-circle me-1"></i>Aprobar
            </button>
        </form>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDevolver">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver
        </button>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="<?= e(APP_URL) ?>/auditoria/informe" class="btn btn-secondary btn-sm d-print-none">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<?php if (!empty($item['comentario_devolucion'])): ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
    <i class="bi bi-arrow-counterclockwise me-1"></i>
    <strong>Devuelto con comentario:</strong> <?= nl2br(e($item['comentario_devolucion'])) ?>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><strong>Información General</strong></div>
            <div class="card-body">
                <?php foreach([
                    'Código'          => $item['codigo'],
                    'Tipo Auditoría'  => $item['tipo_auditoria']??'—',
                    'Fecha Informe'   => $item['fecha_informe']?fechaEs($item['fecha_informe']):'—',
                    'Auditor Líder'   => $item['auditor_nombre']??'—',
                    'Programa'        => $item['programa_codigo']??'—',
                    'Plan'            => ($item['plan_codigo']??'—').' '.($item['plan_titulo']??''),
                ] as $l=>$v): ?>
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted"><?= $l ?>:</div>
                    <div class="col-sm-9"><?= e($v) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach([
            'Sumario Ejecutivo'       => 'sumario_ejecutivo',
            'Objetivo'                => 'objetivo',
            'Alcance'                 => 'alcance',
            'Criterios de Auditoría'  => 'criterio_auditoria',
            'Antecedentes'            => 'antecedentes',
            'Contextualización'       => 'contextualizacion',
            'Resultados de la Auditoría' => 'resultados_auditoria',
            'Evaluación General'      => 'evaluacion',
            'Opinión del Auditor'     => 'opinion',
        ] as $label => $campo): ?>
        <?php if (!empty($item[$campo])): ?>
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="fw-semibold text-muted mb-1" style="font-size:12px;"><?= $label ?></div>
                <p class="mb-0" style="font-size:13px;"><?= nl2br(e($item[$campo])) ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>



<!-- ══════════════════ HU-AI-11: COMPONENTES CONTROL INTERNO ══════════════════ -->
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <!-- Tabla de componentes -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-shield-check me-1"></i>Componentes de Control Interno</strong>
                <?php if($item['estado']==='BORRADOR'): ?>
                <button class="btn btn-lim-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalComponente">
                    <i class="bi bi-plus-circle me-1"></i>Agregar
                </button>
                <?php endif; ?>
            </div>
            <?php if(!empty($componentes)): ?>
            <?php
            $colores = ['EFECTIVO'=>'success','PARCIALMENTE'=>'warning','INEFECTIVO'=>'danger','NO_EVALUADO'=>'secondary'];
            $iconos  = ['EFECTIVO'=>'bi-check-circle','PARCIALMENTE'=>'bi-dash-circle','INEFECTIVO'=>'bi-x-circle','NO_EVALUADO'=>'bi-circle'];
            ?>
            <!-- Resumen semáforo -->
            <?php if(!empty($resumen)): ?>
            <div class="card-body py-2 border-bottom">
                <div class="d-flex gap-3 flex-wrap">
                    <?php foreach(['EFECTIVO'=>'Efectivos','PARCIALMENTE'=>'Parciales','INEFECTIVO'=>'Inefectivos','NO_EVALUADO'=>'No Eval.'] as $k=>$l): ?>
                    <?php $val = $resumen[strtolower($k).'s'] ?? ($k==='INEFECTIVO'?$resumen['inefectivos']:($k==='NO_EVALUADO'?$resumen['no_evaluados']:0)); ?>
                    <div class="text-center">
                        <i class="bi <?= $iconos[$k] ?> text-<?= $colores[$k] ?>" style="font-size:1.2rem;"></i>
                        <div class="fw-bold text-<?= $colores[$k] ?>"><?= (int)($resumen[strtolower($k).'s']??0) ?></div>
                        <div class="text-muted" style="font-size:12px;"><?= $l ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:12px;">
                <thead><tr><th>#</th><th>Componente</th><th>Descripción</th><th class="text-center">Calificación</th><th>Observaciones</th><?php if($item['estado']==='BORRADOR'): ?><th class="d-print-none"></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach($componentes as $i => $comp): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= e($comp['componente']) ?></strong></td>
                    <td><?= e($comp['descripcion']??'—') ?></td>
                    <td class="text-center">
                        <?php $cal=$comp['calificacion']??'NO_EVALUADO'; ?>
                        <span class="badge bg-<?= $colores[$cal]??'secondary' ?>">
                            <i class="bi <?= $iconos[$cal]??'bi-circle' ?> me-1"></i><?= $cal ?>
                        </span>
                    </td>
                    <td style="max-width:200px;"><?= e($comp['observaciones']??'—') ?></td>
                    <?php if($item['estado']==='BORRADOR'): ?>
                    <td class="d-print-none">
                        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/<?= (int)$item['id'] ?>/componentes/eliminar/<?= (int)$comp['id'] ?>" style="display:inline;">
                            <?= csrfField() ?>
                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1"
                                    onclick="swalConfirmForm(event,'¿Eliminar este componente?','Eliminar')">
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
            <?php else: ?>
            <div class="card-body text-center text-muted py-3" style="font-size:12px;">
                Sin componentes registrados. <?= $item['estado']==='BORRADOR'?'Use el botón Agregar.':'' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════════ HU-AI-13: DISTRIBUCIÓN ══════════════════ -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-send me-1"></i>Distribución del Informe</strong>
                <?php if(in_array($item['estado'],['APROBADO','DISTRIBUIDO'])): ?>
                <button class="btn btn-lim-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDistribuir">
                    <i class="bi bi-plus-circle me-1"></i>Enviar
                </button>
                <?php endif; ?>
            </div>
            <?php if(!empty($distribuciones)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach($distribuciones as $d): ?>
                <li class="list-group-item py-2 px-3" style="font-size:12px;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= e($d['nombre_destinatario']) ?></strong>
                            <?php if($d['cargo_destinatario']): ?><br><span class="text-muted"><?= e($d['cargo_destinatario']) ?></span><?php endif; ?>
                            <br><small class="text-muted"><?= e($d['correo_destinatario']??'') ?> · <?= e($d['medio']) ?></small>
                            <br><small class="text-muted"><?= $d['fecha_envio']?fechaEs(substr($d['fecha_envio'],0,10)):'—' ?></small>
                        </div>
                        <div class="text-end">
                            <?php if($d['confirmacion_recibo']): ?>
                            <span class="badge bg-success" style="font-size:12px;"><i class="bi bi-check2"></i> Recibido</span>
                            <?php else: ?>
                            <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/<?= (int)$item['id'] ?>/distribucion/confirmar/<?= (int)$d['id'] ?>" style="display:inline;">
                                <?= csrfField() ?>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:12px;"
                                        onclick="swalConfirmForm(event,'¿Confirmar recibo del informe?','Confirmar Recibo')">
                                    Confirmar
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3" style="font-size:12px;">
                <?= in_array($item['estado'],['APROBADO','DISTRIBUIDO'])
                    ? 'Sin destinatarios. Use el botón Enviar.'
                    : 'Disponible cuando el informe esté APROBADO.' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Agregar Componente -->
<?php if($item['estado']==='BORRADOR'): ?>
<div class="modal fade" id="modalComponente" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--lim-blue);color:#fff;">
        <h6 class="modal-title mb-0"><i class="bi bi-shield-check me-2"></i>Agregar Componente de Control Interno</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/<?= (int)$item['id'] ?>/componentes">
        <?= csrfField() ?>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Componente <span class="text-danger">*</span></label>
                    <select class="form-select" name="componente" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach([
                            'Ambiente de Control','Evaluación de Riesgos','Actividades de Control',
                            'Información y Comunicación','Actividades de Monitoreo',
                            'Talento Humano','Direccionamiento Estratégico','Administración del Riesgo',
                            'Autoevaluación','Auditoría Interna','Planes de Mejoramiento'
                        ] as $comp): ?>
                        <option value="<?= $comp ?>"><?= $comp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Calificación</label>
                    <select class="form-select" name="calificacion">
                        <option value="NO_EVALUADO">No Evaluado</option>
                        <option value="EFECTIVO">✅ Efectivo</option>
                        <option value="PARCIALMENTE">⚠️ Parcialmente Efectivo</option>
                        <option value="INEFECTIVO">❌ Inefectivo</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="2" placeholder="Descripción del componente evaluado..."></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="2" placeholder="Observaciones y hallazgos específicos..."></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-lim-primary btn-sm"><i class="bi bi-save me-1"></i>Agregar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal Distribuir -->
<?php if(in_array($item['estado'],['APROBADO','DISTRIBUIDO'])): ?>
<div class="modal fade" id="modalDistribuir" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--lim-blue);color:#fff;">
        <h6 class="modal-title mb-0"><i class="bi bi-send me-2"></i>Distribuir Informe</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/<?= (int)$item['id'] ?>/distribuir">
        <?= csrfField() ?>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Empleado (opcional)</label>
                    <select class="form-select" name="id_empleado" id="selEmpDist"
                            onchange="llenarDestinatario(this)">
                        <option value="">— O ingrese manualmente —</option>
                        <?php foreach($empleados as $emp): ?>
                        <option value="<?= (int)$emp['id_empleado'] ?>"
                                data-nombre="<?= e($emp['nombre_completo']) ?>"
                                data-correo="<?= e($emp['correo_empleado']??'') ?>"
                                data-cargo="<?= e($emp['cargo']??'') ?>">
                            <?= e($emp['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre_destinatario" id="inpNombreDist" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Cargo</label>
                    <input type="text" class="form-control" name="cargo_destinatario" id="inpCargoDist">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Correo</label>
                    <input type="email" class="form-control" name="correo_destinatario" id="inpCorreoDist">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Medio</label>
                    <select class="form-select" name="medio">
                        <option value="CORREO">📧 Correo</option>
                        <option value="FISICO">📄 Físico</option>
                        <option value="DIGITAL">💾 Digital</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-lim-primary btn-sm"><i class="bi bi-send me-1"></i>Enviar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function llenarDestinatario(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        document.getElementById('inpNombreDist').value = opt.dataset.nombre || '';
        document.getElementById('inpCorreoDist').value = opt.dataset.correo || '';
        document.getElementById('inpCargoDist').value  = opt.dataset.cargo  || '';
    }
}
</script>
<?php endif; ?>

<!-- Modal Devolver -->
<?php if ($item['estado']==='EN_REVISION' && Auth::hasRole([1,2])): ?>
<div class="modal fade" id="modalDevolver" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title mb-0">Devolver Informe a BORRADOR</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formDevolver" method="POST" action="<?= e(APP_URL) ?>/auditoria/informe/devolver/<?= (int)$item['id'] ?>">
            <?= csrfField() ?>
            <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
            <textarea name="comentario_devolucion" class="form-control" rows="3" id="txtMotivoInf"
                      placeholder="Indique qué debe corregirse..."></textarea>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" onclick="
            var m=document.getElementById('txtMotivoInf').value.trim();
            if(m.length<10){Swal.fire({icon:'warning',title:'Motivo requerido',text:'Mínimo 10 caracteres.',confirmButtonColor:'#1B3A6B'});return;}
            document.getElementById('formDevolver').submit();">
            Devolver
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
