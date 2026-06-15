<?php $tarea = $tarea ?? []; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-patch-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/tareas/aprobar">Mis Aprobaciones</a>
            </li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea']) ?></li>
        </ol></nav>
    </div>
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<!-- CA-1: Documento elaborado — solo lectura, no se vuelve a subir -->
<?php if (!empty($tarea['archivos'])): ?>
<div class="card mt-3">
    <div class="card-header py-2 d-flex align-items-center gap-2"
         style="background:#e8f5e9; border-left:4px solid #28a745;">
        <i class="bi bi-file-earmark-check text-success"></i>
        <strong>Documento para Aprobación</strong>
        <span class="badge bg-success"><?= count($tarea['archivos']) ?></span>
        <span class="badge bg-secondary ms-auto">Solo lectura</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Archivo</th><th style="width:130px" class="text-center">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($tarea['archivos'] as $ar):
                $visType = esVisualizableInline($ar['nombre_original'] ?? '');
            ?>
            <tr>
                <td style="font-size:12px;">
                    <i class="bi <?= iconoArchivo($ar['nombre_original'] ?? '') ?> me-1"></i>
                    <?= e($ar['nombre_original'] ?? 'Archivo') ?>
                    <small class="text-muted ms-1"><?= round(($ar['tamano_bytes'] ?? 0)/1024) ?> KB</small>
                </td>
                <td class="text-center">
                    <?php if ($visType !== 'none'): ?>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$ar['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 me-1" title="Ver">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php endif; ?>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$ar['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Descargar">
                        <i class="bi bi-download"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning mt-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No hay documento elaborado adjunto. Contacte al elaborador o revisor.
</div>
<?php endif; ?>

<?php if (!empty($archivosAnexos)): ?>
<div class="card mt-3">
    <div class="card-header py-2">
        <i class="bi bi-paperclip text-warning me-1"></i>
        <strong>Anexos Originales de la Solicitud</strong>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
            <?php foreach ($archivosAnexos as $anx): ?>
            <tr>
                <td style="font-size:12px;">
                    <i class="bi <?= iconoArchivo($anx['nombre_original'] ?? '') ?> me-1"></i>
                    <?= e($anx['nombre_original'] ?? 'Anexo') ?>
                </td>
                <td class="text-center" style="width:90px;">
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$anx['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 me-1">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$anx['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-download"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de aprobación -->
<div class="card mt-3">
    <div class="card-header">
        <i class="bi bi-shield-check me-2"></i>Decisión de Aprobación
    </div>
    <div class="card-body">

        <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
            <i class="bi bi-info-circle me-1"></i>
            Al <strong>Aprobar y Publicar</strong>, se crea una nueva versión <span class="badge bg-success">VIGENTE</span>
            del documento y las versiones anteriores pasan a <span class="badge bg-danger">OBSOLETO</span>.
        </div>
        <form action="<?= e(APP_URL) ?>/tareas/aprobar/<?= e($tarea['id_tarea']) ?>"
              method="POST">
            <?= csrfField() ?>


        <?php if ($esTipoCreacion ?? false): ?>
        <?php if (!empty($docExistente)): ?>
        <!-- Documento ya existe en v0 — mostrar readonly -->
        <div class="alert alert-success py-2 mb-3" style="font-size:13px;">
            <i class="bi bi-check-circle me-1"></i>
            Documento registrado en <strong>Versión 0</strong> por el elaborador.
            Al aprobar se generará la <strong>Versión 1 (VIGENTE)</strong>.
        </div>
        <div class="row g-3 mb-3 p-3 border rounded border-success" style="background:#f0fdf4;">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Nombre del Documento</label>
                <input type="text" class="form-control bg-light"
                       value="<?= e($docExistente['nombre_documento']) ?>" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Código</label>
                <input type="text" class="form-control bg-light"
                       value="<?= e($docExistente['codigo']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Macroproceso</label>
                <input type="text" class="form-control bg-light"
                       value="<?= e($docExistente['macroproceso'] ?? '—') ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Proceso</label>
                <input type="text" class="form-control bg-light"
                       value="<?= e($docExistente['proceso'] ?? '—') ?>" disabled>
            </div>
            <?php if ($docExistente['subproceso'] ?? null): ?>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Subproceso</label>
                <input type="text" class="form-control bg-light"
                       value="<?= e($docExistente['subproceso']) ?>" disabled>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Solicitud de <strong>Creación de Documento</strong>.
            Complete los datos para registrar el nuevo documento en el sistema.
        </div>
        <div class="row g-3 mb-3 p-3 border rounded" style="background:#f8f9fa;">
            <div class="col-12">
                <h6 class="fw-bold">
                    <i class="bi bi-file-earmark-plus me-1 text-primary"></i>Datos del Nuevo Documento
                </h6>
            </div>

            <!-- Nombre del documento -->
            <div class="col-12">
                <label class="form-label fw-semibold">
                    Nombre del Documento <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" name="nombre_documento"
                       placeholder="Ej: Procedimiento de Crédito Individual"
                       value="<?= e(old('nombre_documento', '')) ?>" required>
                <div class="form-text">El código se genera automáticamente según tipo y proceso.</div>
            </div>

            <!-- Tipo Documento: viene de la solicitud -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Tipo de Documento</label>
                <input type="text" class="form-control"
                       value="<?= e($tarea['tipo_documento'] ?? '') ?>"
                       readonly style="background:#e9ecef;" title="Definido en la solicitud">
                <input type="hidden" name="id_tipo_documento"
                       value="<?= e($tarea['id_tipo_documento'] ?? '') ?>">
            </div>

            <!-- Macroproceso (filtro) -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Macroproceso</label>
                <select class="form-select" id="selectMacroApr" onchange="filtrarProcesosApr()">
                    <option value="">-- Filtrar por macroproceso --</option>
                    <?php foreach ($macroprocesos as $mp): ?>
                    <option value="<?= (int)$mp['id_macroproceso'] ?>">
                        <?= e($mp['macroproceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Proceso -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    Proceso <span class="text-danger">*</span>
                </label>
                <select class="form-select" name="id_proceso" id="selectProcesoApr"
                        required onchange="filtrarSubprocesosApr()">
                    <option value="">-- Seleccione proceso --</option>
                    <?php foreach ($procesos as $pr): ?>
                    <option value="<?= (int)$pr['id_proceso'] ?>"
                            data-macro="<?= (int)$pr['id_macroproceso'] ?>">
                        <?= e($pr['proceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Subproceso (opcional, depende del proceso) -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    Subproceso <span class="text-muted" style="font-size:11px;">(opcional)</span>
                </label>
                <select class="form-select" name="id_subproceso" id="selectSubprocesoApr">
                    <option value="">-- Ninguno --</option>
                    <?php foreach ($subprocesos as $sp): ?>
                    <option value="<?= (int)$sp['id_subproceso'] ?>"
                            data-proceso="<?= (int)$sp['id_proceso'] ?>"
                            hidden>
                        <?= e($sp['subproceso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <script>
        function filtrarProcesosApr() {
            var macro = document.getElementById('selectMacroApr').value;
            var selP  = document.getElementById('selectProcesoApr');
            selP.value = '';
            for (var i = 1; i < selP.options.length; i++) {
                selP.options[i].hidden = macro ? selP.options[i].dataset.macro !== macro : false;
            }
            filtrarSubprocesosApr();
        }
        function filtrarSubprocesosApr() {
            var idProceso = document.getElementById('selectProcesoApr').value;
            var selS = document.getElementById('selectSubprocesoApr');
            selS.value = '';
            for (var i = 1; i < selS.options.length; i++) {
                var ocultar = idProceso ? selS.options[i].dataset.proceso !== idProceso : true;
                selS.options[i].hidden = ocultar;
            }
        }
        </script>
        <?php endif; // else docExistente ?>
        <?php endif; // esTipoCreacion ?>
            <div class="mb-4">
                <label class="form-label">Observaciones / Comentario de aprobación</label>
                <textarea class="form-control" name="comentario" rows="3"
                          placeholder="Observaciones sobre el documento aprobado..."></textarea>
            </div>
            <div class="d-flex gap-2">
                <!-- CA-2: Aprobar y publicar versión -->
                <button type="submit" name="accion" value="aprobar"
                        class="btn btn-success"
                        onclick="swalConfirm(event, '¿Aprobar y publicar versión VIGENTE? Esta acción no se puede deshacer.')">
                    <i class="bi bi-patch-check me-1"></i>Aprobar y Publicar Versión
                </button>
                <!-- CA-3: Devolver al revisor -->
                <button type="submit" name="accion" value="rechazar"
                        class="btn btn-danger"
                        <?php $msgDevolverRev = '¿Devolver al revisor' . (!empty($tarea['revisor']) ? ' (' . $tarea['revisor'] . ')' : '') . ' con las observaciones indicadas?'; ?>
                        onclick="swalConfirm(event, '<?= addslashes($msgDevolverRev) ?>')">
                    <i class="bi bi-arrow-return-left me-1"></i>
                    Devolver a Revisor
                    <?php if (!empty($tarea['revisor'])): ?>
                    <span class="badge bg-light text-dark ms-1 fw-normal" style="font-size:10px;">
                        <?= e($tarea['revisor']) ?>
                    </span>
                    <?php endif; ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php include APP_ROOT . '/app/views/tareas/_comentarios_tarea.php'; ?>
