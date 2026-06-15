<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-calendar3 me-2"></i><?= e($item['codigo']) ?> — <?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/auditoria/plan">Planes de Auditoría</a></li>
            <li class="breadcrumb-item active"><?= e($item['codigo']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?= badgeEstado($item['estado']) ?>
        <?php if ($item['estado'] === 'BORRADOR' && Auth::puede('audit_plan','editar')): ?>
        <!-- BORRADOR: editar + enviar a revisión -->
        <a href="<?= e(APP_URL) ?>/auditoria/plan/editar/<?= (int)$item['id'] ?>"
           class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Editar</a>
        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/plan/revisar/<?= (int)$item['id'] ?>"
              style="display:inline;">
            <?= csrfField() ?>
            <button type="button" class="btn btn-warning btn-sm"
                    onclick="swalConfirmForm(event,
                        'Se enviará el plan a los Coordinadores de Calidad para su revisión y aprobación.',
                        'Enviar a Revisión')">
                <i class="bi bi-send me-1"></i>Enviar a Revisión
            </button>
        </form>
        <?php endif; ?>
        <?php if ($item['estado'] === 'EN_REVISION' && Auth::hasRole([1,2])): ?>
        <!-- EN_REVISION (solo Coordinador/Admin): aprobar + devolver -->
        <form method="POST" action="<?= e(APP_URL) ?>/auditoria/plan/aprobar/<?= (int)$item['id'] ?>"
              style="display:inline;">
            <?= csrfField() ?>
            <button type="button" class="btn btn-success btn-sm"
                    onclick="swalConfirmForm(event,
                        'Se aprobará el plan <?= e(addslashes($item['codigo'])) ?>. Se notificará al equipo.',
                        '¿Aprobar Plan de Auditoría?')">
                <i class="bi bi-check2-circle me-1"></i>Aprobar
            </button>
        </form>
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalDevolver">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver a Borrador
        </button>
        <?php endif; ?>
        <?php if (in_array($item['estado'],['APROBADO','EN_CURSO']) && Auth::hasRole([1,2])): ?>
        <!-- APROBADO: retornar a borrador con justificación -->
        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalRetornar">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Retornar a Borrador
        </button>
        <?php endif; ?>
        <a href="<?= e(APP_URL) ?>/auditoria/plan/<?= (int)$item['id'] ?>/actividades"
           class="btn btn-outline-info btn-sm d-print-none">
            <i class="bi bi-calendar-week me-1"></i>Cronograma
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-print-none">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="<?= e(APP_URL) ?>/auditoria/plan" class="btn btn-secondary btn-sm d-print-none">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><strong>Información General</strong></div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted">Código:</div>
                    <div class="col-sm-9"><code><?= e($item['codigo']) ?></code></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted">Título:</div>
                    <div class="col-sm-9"><?= e($item['titulo']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted">Tipo Auditoría:</div>
                    <div class="col-sm-9"><?= e($item['tipo_auditoria'] ?? '—') ?></div>
                </div>
                <?php if (!empty($item['comentario_devolucion'])): ?>
                <div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    <strong>Devuelto con comentario:</strong>
                    <?= nl2br(e($item['comentario_devolucion'])) ?>
                </div>
                <?php endif; ?>
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted">Año:</div>
                    <div class="col-sm-9"><?= (int)$item['anio'] ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted">Auditor Líder:</div>
                    <div class="col-sm-9"><?= e($item['auditor_nombre'] ?? '—') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-3 fw-semibold text-muted">Fechas:</div>
                    <div class="col-sm-9">
                        <?= $item['fecha_inicio'] ? fechaEs($item['fecha_inicio']) : '—' ?>
                        <?= $item['fecha_fin'] ? ' → '.fechaEs($item['fecha_fin']) : '' ?>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="fw-semibold text-muted mb-1">Objetivo General:</div>
                    <p class="mb-0"><?= nl2br(e($item['objetivo_general'])) ?></p>
                </div>
                <?php if ($item['objetivos_especificos']): ?>
                <div class="mb-3">
                    <div class="fw-semibold text-muted mb-1">Objetivos Específicos:</div>
                    <p class="mb-0"><?= nl2br(e($item['objetivos_especificos'])) ?></p>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <div class="fw-semibold text-muted mb-1">Alcance:</div>
                    <p class="mb-0"><?= nl2br(e($item['alcance'])) ?></p>
                </div>
                <?php if ($item['criterios']): ?>
                <div class="mb-0">
                    <div class="fw-semibold text-muted mb-1">Criterios:</div>
                    <p class="mb-0"><?= nl2br(e($item['criterios'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Programa Asociado -->
        <?php if (!empty($programa)): ?>
        <div class="card mb-3 border-success">
            <div class="card-header py-2" style="background:var(--lim-blue);color:#fff;">
                <i class="bi bi-journal-text me-1"></i>Programa de Auditoría Vinculado
            </div>
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <code class="fw-bold" style="color:var(--lim-blue);"><?= e($programa['codigo']) ?></code>
                        <small class="text-muted ms-2">Estado: igual al plan</small>
                    </div>
                    <a href="<?= e(APP_URL) ?>/auditoria/programa/<?= (int)$programa['id'] ?>"
                       class="btn btn-sm btn-lim-primary py-0">
                        <i class="bi bi-eye me-1"></i>Ver Programa
                    </a>
                </div>
            </div>
        </div>
        <?php elseif ($item['estado'] === 'APROBADO'): ?>
        <div class="card mb-3 border-warning">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="text-warning" style="font-size:12px;">
                    <i class="bi bi-exclamation-triangle me-1"></i>Sin Programa de Auditoría
                </span>
                <a href="<?= e(APP_URL) ?>/auditoria/programa/crear"
                   class="btn btn-sm btn-warning py-0">
                    <i class="bi bi-plus-circle me-1"></i>Crear Programa
                </a>
            </div>
        </div>
        <?php elseif (in_array($item['estado'], ['BORRADOR','EN_REVISION'])): ?>
        <div class="card mb-3 border-secondary">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="text-muted" style="font-size:12px;">
                    <i class="bi bi-journal-text me-1"></i>Sin Programa de Auditoría vinculado
                </span>
                <a href="<?= e(APP_URL) ?>/auditoria/programa/crear"
                   class="btn btn-sm btn-outline-secondary py-0">
                    <i class="bi bi-plus-circle me-1"></i>Crear Programa
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Procesos auditados -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-diagram-3 me-1"></i>Procesos Auditados</div>
            <div class="card-body p-0">
                <?php if (empty($item['procesos'])): ?>
                <p class="text-muted text-center py-3 mb-0" style="font-size:12px;">Sin procesos asignados</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($item['procesos'] as $proc): ?>
                    <li class="list-group-item py-1 px-3" style="font-size:12px;">
                        <code style="font-size:10px;"><?= e($proc['sigla_proceso'] ?? '') ?></code>
                        <?= e($proc['proceso']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <!-- Equipo auditor -->
        <?php if ($item['equipo_auditor']): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-people me-1"></i>Equipo Auditor</div>
            <div class="card-body p-3" style="font-size:12px;">
                <?= nl2br(e($item['equipo_auditor'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


<!-- Modal Devolver a Borrador (desde EN_REVISION) -->
<?php if ($item['estado'] === 'EN_REVISION' && Auth::hasRole([1,2])): ?>
<div class="modal fade" id="modalDevolver" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title mb-0">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Devolver a Borrador
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 mb-3" style="font-size:12px;">
            <i class="bi bi-info-circle me-1"></i>
            El plan volverá a <strong>BORRADOR</strong> y el creador podrá editarlo.
            Se notificará por correo con el motivo.
        </div>
        <form id="formDevolver" method="POST"
              action="<?= e(APP_URL) ?>/auditoria/plan/devolver/<?= (int)$item['id'] ?>">
            <?= csrfField() ?>
            <label class="form-label fw-semibold">
                Motivo de devolución <span class="text-danger">*</span>
                <small class="text-muted fw-normal">(mínimo 15 caracteres)</small>
            </label>
            <textarea name="comentario_devolucion" class="form-control" rows="4"
                      id="txtMotivoDev"
                      placeholder="Indique qué debe corregirse o ajustarse en el plan..."></textarea>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" onclick="
            var m = document.getElementById('txtMotivoDev').value.trim();
            if (m.length < 15) {
                Swal.fire({icon:'warning', title:'Motivo requerido',
                    text:'El motivo debe tener al menos 15 caracteres.', confirmButtonColor:'#1B3A6B'});
                return;
            }
            document.getElementById('formDevolver').submit();">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver a Borrador
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal retornar a BORRADOR -->
<?php if (in_array($item['estado'],['APROBADO','EN_CURSO']) && Auth::hasRole([1,2])): ?>
<div class="modal fade" id="modalRetornar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h6 class="modal-title mb-0">Retornar a BORRADOR</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formRetornar" method="POST"
              action="<?= e(APP_URL) ?>/auditoria/plan/retornar/<?= (int)$item['id'] ?>">
          <?= csrfField() ?>
          <label class="form-label fw-semibold">
            Justificación <span class="text-danger">*</span>
            <small class="text-muted fw-normal">(mínimo 10 caracteres)</small>
          </label>
          <textarea name="justificacion" class="form-control" rows="3" id="txtJustif"
                    placeholder="Explique el motivo del retorno a borrador..."></textarea>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning btn-sm" onclick="
          var j=document.getElementById('txtJustif').value.trim();
          if(j.length<10){Swal.fire({icon:'warning',title:'Justificación requerida',text:'Mínimo 10 caracteres.',confirmButtonColor:'#1B3A6B'});return;}
          document.getElementById('formRetornar').submit();">
            Retornar a Borrador
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
