<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?><div class="page-header">
    <div><h2><i class="bi bi-journal-text me-2"></i>Mis Solicitudes Radicadas</h2></div>
    <div class="d-flex gap-2">
       <?php if (Auth::puede('crear_documento', 'crear')): ?>
        <a href="<?= e(APP_URL) ?>/solicitudes/crear" class="btn btn-lim-primary btn-sm">
            <i class="bi bi-file-plus me-1"></i>Crear Documento
        </a>
        <?php endif; ?>
        <?php if (Auth::puede('actualizar_documento', 'crear')): ?>
        <a href="<?= e(APP_URL) ?>/solicitudes/actualizar" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-arrow-up me-1"></i>Actualizar Documento
        </a>
        <?php endif; ?>
        <?php if (Auth::puede('eliminar_documento', 'crear')): ?>
        <a href="<?= e(APP_URL) ?>/solicitudes/eliminar"
           class="btn btn-outline-warning btn-sm"
           onclick="return swalConfirmLink(event, '¿Eliminar este documento? Esta acción no se puede deshacer.')">
            <i class="bi bi-file-x me-1"></i>Inactivar Documento
        </a>
        <?php endif; ?>
    </div>
</div>
<!-- KPIs mis solicitudes -->
<?php if (!empty($resumen)):
$estadoConfig2 = [
    'CREADA'       => ['kpi-blue',  'bi-inbox',           'Radicadas'],
    'ASIGNADA'     => ['kpi-amber', 'bi-person-check',    'Asignadas'],
    'EN_DESARROLLO'=> ['kpi-teal',  'bi-hourglass-split', 'En Desarrollo'],
    'FINALIZADA'   => ['kpi-green', 'bi-check2-circle',   'Finalizadas'],
    'CANCELADA'    => ['kpi-rose',  'bi-x-circle',        'Canceladas'],
];
$kpis = [];
foreach ($estadoConfig2 as $est => [$tipo, $icono, $lbl]) {
    if ($resumen[$est] ?? 0)
        $kpis[] = ['label'=>$lbl,'valor'=>$resumen[$est],'icono'=>$icono,'tipo'=>$tipo];
}
$kpiTotal = ['label'=>'Mis Solicitudes','valor'=>array_sum($resumen)];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo Solicitud</th><th>Tipo Doc.</th><th>Prioridad</th><th>Estado</th><th>Fecha</th><th class="text-center">Anexo</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No has radicado solicitudes.</td></tr>
                <?php else: ?>
                <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td><?= e($s['id_solicitud']) ?></td>
                    <td><?= labelTipoSolicitud($s['tipo_solicitud'] ?? '') ?></td>
                    <td><?= e($s['tipo_documento']) ?></td>
                    <td><?= prioridadLabel($s['prioridad'] ?? 'NO_URGENTE_IMPORTANTE') ?></td>
                    <td><?= badgeEstado($s['estado_solicitud']) ?></td>
                    <td><?= fechaEs($s['fecha_solicitud']) ?></td>
                    <!-- CA-1/CA-2 HU-016: columna Anexo -->
                    <td class="text-center">
                        <?php if (!empty($s['id_archivo'])): ?>
                        <?php
                            $visType = esVisualizableInline($s['archivo_nombre'] ?? '');
                            $urlVer  = e(APP_URL) . '/archivo/' . (int)$s['id_archivo'] . '?inline=1';
                            $urlDl   = e(APP_URL) . '/archivo/' . (int)$s['id_archivo'];
                        ?>
                        <a href="<?= $urlVer ?>" target="_blank"
                           class="btn btn-sm btn-outline-danger py-0 px-1 me-1"
                           title="Ver adjunto">
                            <i class="bi bi-eye" style="font-size:12px;"></i>
                        </a>
                        <a href="<?= $urlDl ?>"
                           class="btn btn-sm btn-outline-primary py-0 px-1"
                           title="Descargar adjunto: <?= e($s['archivo_nombre'] ?? '') ?>">
                            <i class="bi <?= iconoArchivo($s['archivo_nombre'] ?? '') ?>"
                               style="font-size:12px;"></i>
                        </a>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center" style="white-space:nowrap;">
                        <a href="<?= e(APP_URL) ?>/solicitudes/ver/<?= $s['id_solicitud'] ?>"
                           class="btn btn-sm btn-outline-info py-0 px-2" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (
                            !in_array($s['estado_solicitud'], ['FINALIZADA','FINALIZADA_SIN_TRAMITE']) &&
                            (Auth::hasRole([1, 'ADMINISTRADOR', 'Administrador del Sistema']) || Auth::puede('solicitudes_gestion','editar'))
                        ): ?>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger py-0 px-2 ms-1"
                                title="Finalizar sin trámite"
                                data-bs-toggle="modal" data-bs-target="#modalFSTTable"
                                onclick="prepFST(<?= $s['id_solicitud'] ?>)">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- HU-027: Modal Finalizar sin Trámite (tabla) -->
<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="modal fade" id="modalFSTTable" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);color:#fff;">
        <h6 class="modal-title mb-0"><i class="bi bi-slash-circle me-2"></i>Finalizar sin Trámite</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Esta acción cerrará la solicitud sin procesarla. <strong>No podrá reabrirse.</strong>
        </div>
        <form id="formFSTTable" method="POST">
          <?= csrfField() ?>
          <div class="mb-1">
            <label class="form-label fw-semibold">
              Motivo <span class="text-danger">*</span>
              <small class="text-muted fw-normal">(mínimo 10 caracteres)</small>
            </label>
            <textarea name="motivo" class="form-control" rows="3"
                      placeholder="Explique el motivo..."
                      id="motivoFSTTable"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" onclick="submitFSTTable()">
          <i class="bi bi-slash-circle me-1"></i>Finalizar sin Trámite
        </button>
      </div>
    </div>
  </div>
</div>
<script>
function prepFST(id) {
    document.getElementById('formFSTTable').action =
        '<?= e(APP_URL) ?>/solicitudes/finalizar-sin-tramite/' + id;
    document.getElementById('motivoFSTTable').value = '';
}
function submitFSTTable() {
    var m = document.getElementById('motivoFSTTable').value.trim();
    if (m.length < 10) {
        Swal.fire({icon:'warning', title:'Motivo requerido',
            text:'El motivo debe tener al menos 10 caracteres.', confirmButtonColor:'#1B3A6B'});
        return;
    }
    document.getElementById('formFSTTable').submit();
}
</script>
