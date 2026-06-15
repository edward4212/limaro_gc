<?php $tarea = $tarea ?? []; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-pencil me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/tareas/elaborar">Mis Tareas</a>
            </li>
            <li class="breadcrumb-item active">Tarea #<?= e($tarea['id_tarea']) ?></li>
        </ol></nav>
    </div>
</div>

<?php include APP_ROOT . '/app/views/tareas/_detalle_tarea.php'; ?>

<!-- CA-2: Documentos anexos de la solicitud original -->
<?php if (!empty($archivosAnexos)): ?>
<div class="card mt-3">
    <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="bi bi-paperclip text-warning"></i>
        <strong>Documentos Anexos de la Solicitud</strong>
        <span class="badge bg-warning text-dark"><?= count($archivosAnexos) ?></span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>Archivo</th><th style="width:130px" class="text-center">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($archivosAnexos as $anx):
                $visType = esVisualizableInline($anx['nombre_original'] ?? '');
            ?>
            <tr>
                <td style="font-size:12px;">
                    <i class="bi <?= iconoArchivo($anx['nombre_original'] ?? '') ?> me-1"></i>
                    <?= e($anx['nombre_original'] ?? 'Archivo') ?>
                    <small class="text-muted ms-1"><?= round(($anx['tamano_bytes'] ?? 0)/1024) ?> KB</small>
                </td>
                <td class="text-center">
                    <?php if ($visType !== 'none'): ?>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$anx['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 me-1"
                       title="Ver">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php endif; ?>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$anx['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2"
                       title="Descargar">
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

<form action="<?= e(APP_URL) ?>/tareas/elaborar/<?= e($tarea['id_tarea']) ?>"
      method="POST" enctype="multipart/form-data" data-novalidate>
<?= csrfField() ?>

<!-- ── CREACION: campos de metadatos del documento ─────────────────── -->
<?php if (!empty($esTipoCreacion)): ?>
<div class="card mt-3 border-info">
    <div class="card-header bg-info bg-opacity-10">
        <i class="bi bi-file-earmark-plus me-1 text-info"></i>
        <strong>Datos del Nuevo Documento</strong>
        <span class="badge bg-info ms-2" style="font-size:10px;">Versión 0 — BORRADOR</span>
    </div>
    <div class="card-body">
    <?php if (!empty($docExistente)): ?>
    <div class="alert alert-success py-2 mb-3" style="font-size:12px;">
        <i class="bi bi-check-circle me-1"></i>
        Documento en <strong>Versión 0 — BORRADOR</strong>
    </div>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Código del Documento</label>
            <input class="form-control bg-light fw-bold" style="font-family:monospace;color:var(--lim-blue);"
                   value="<?= e($docExistente['codigo']) ?>" disabled readonly>
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Nombre del Documento</label>
            <input class="form-control bg-light" value="<?= e($docExistente['nombre_documento']) ?>" disabled readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">Proceso</label>
            <input class="form-control bg-light" value="<?= e($docExistente['proceso'] ?? '—') ?>" disabled readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tipo Documento</label>
            <input class="form-control bg-light" value="<?= e($docExistente['tipo_documento'] ?? '—') ?>" disabled readonly>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-3" id="bloqueCreacion">
        <div class="col-12">
            <label class="form-label fw-semibold">Nombre del Documento <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nombre_documento" required
                   placeholder="Ej: Manual de Gestión de Riesgo Operativo">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Macroproceso</label>
            <select class="form-select" id="selMacroElab" onchange="filtrarProcesosElab()">
                <option value="">— Seleccione —</option>
                <?php foreach ($macroprocesos ?? [] as $mp): ?>
                <option value="<?= $mp['id_macroproceso'] ?>"><?= e($mp['macroproceso']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Proceso <span class="text-danger">*</span></label>
            <select class="form-select" name="id_proceso" id="selProcesoElab"
                    onchange="filtrarSubprocesosElab()" disabled>
                <option value="">— Primero seleccione macroproceso —</option>
                <?php foreach ($procesos ?? [] as $pr): ?>
                <option value="<?= $pr['id_proceso'] ?>" data-macro="<?= $pr['id_macroproceso'] ?>">
                    <?= e($pr['proceso']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Subproceso <small class="text-muted">(opcional)</small></label>
            <select class="form-select" name="id_subproceso" id="selSubprocesoElab" disabled>
                <option value="">— Sin subproceso —</option>
                <?php foreach ($subprocesos ?? [] as $sp): ?>
                <option value="<?= $sp['id_subproceso'] ?>" data-proc="<?= $sp['id_proceso'] ?>">
                    <?= e($sp['subproceso']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="alert alert-info mt-3 py-2" style="font-size:12px;">
        <i class="bi bi-info-circle me-1"></i>
        Al enviar a revisión se creará el documento en <strong>Versión 0 (BORRADOR)</strong>.
        Al aprobarse se generará la <strong>Versión 1 (VIGENTE)</strong>.
    </div>
    <script>
    function filtrarProcesosElab() {
        var macro = document.getElementById('selMacroElab').value;
        var selP  = document.getElementById('selProcesoElab');
        var selS  = document.getElementById('selSubprocesoElab');
        // CA-3: habilitar proceso solo si hay macroproceso seleccionado
        selP.disabled = !macro;
        selS.disabled = true;
        selP.value = '';
        selS.value = '';
        Array.from(selP.options).forEach(function(o) {
            if (!o.value) return;
            var mostrar = !macro || o.dataset.macro === macro;
            o.hidden = !mostrar; o.disabled = !mostrar;
        });
        Array.from(selS.options).forEach(function(o) {
            if (!o.value) return;
            o.hidden = true; o.disabled = true;
        });
    }
    function filtrarSubprocesosElab() {
        var proc = document.getElementById('selProcesoElab').value;
        var selS = document.getElementById('selSubprocesoElab');
        selS.disabled = !proc;
        selS.value = '';
        Array.from(selS.options).forEach(function(o) {
            if (!o.value) return;
            var mostrar = !proc || o.dataset.proc === proc;
            o.hidden = !mostrar; o.disabled = !mostrar;
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        filtrarProcesosElab();
    });
    </script>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de elaboración -->
<div class="card mt-3">
    <div class="card-header">
        <i class="bi bi-upload me-2"></i>Subir Documento y Enviar a Revisión
    </div>
    <div class="card-body">

            <!-- CA-1: un solo documento (ver si ya existe) -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Documento Elaborado
                    <span class="text-muted" style="font-size:12px;">(cualquier formato, máx. 50MB)</span>
                </label>
                <?php $archivoActual = $tarea['archivos'][0] ?? null; ?>
                <?php if ($archivoActual): ?>
                <div class="alert alert-success py-2 mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <div class="flex-grow-1" style="font-size:12px;">
                        <strong>Documento actual:</strong>
                        <?= e($archivoActual['nombre_original']) ?>
                        <span class="text-muted">(<?= round(($archivoActual['tamano_bytes'] ?? 0)/1024) ?> KB)</span>
                    </div>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$archivoActual['id_archivo'] ?>?inline=1"
                       target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 me-1">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$archivoActual['id_archivo'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
                <div class="form-text text-warning mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Si sube un nuevo archivo, <strong>reemplazará el actual con confirmación</strong>.
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" name="archivo"
                       id="inputArchivoElab"
                       <?= $archivoActual ? 'onchange="confirmarReemplazo(this)"' : '' ?>>
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i>
                    PDF, Word, Excel, imagen u otros formatos. Solo se guarda el más reciente.
                </div>
            </div>

            <!-- CA-3: SELECT de revisores -->
            <div class="mb-3">
                <label class="form-label">
                    Asignar Revisor
                    <span class="text-danger">*</span>
                    <span class="text-muted" style="font-size:11px;">(requerido para enviar a revisión)</span>
                </label>
                <select class="form-select" name="id_empleado_revisor">
                    <option value="">-- Seleccione revisor --</option>
                    <?php foreach ($revisores ?? [] as $rev): ?>
                    <option value="<?= e($rev['id_empleado']) ?>">
                        <?= e($rev['nombre_completo']) ?>
                        <?php if (!empty($rev['cargo'])): ?> — <?= e($rev['cargo']) ?><?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($revisores)): ?>
                <div class="form-text text-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    No hay usuarios con rol Revisor activos. Contacte al administrador.
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Comentario / Descripción de cambios</label>
                <textarea class="form-control" name="comentario" rows="3"
                          placeholder="Describa los cambios realizados..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="accion" value="guardar"
                        class="btn btn-secondary">
                    <i class="bi bi-save me-1"></i>Guardar borrador
                </button>
                <button type="submit" name="accion" value="enviar"
                        class="btn btn-lim-primary"
                        onclick="swalConfirm(event, '¿Enviar a revisión? El revisor recibirá un correo de notificación.')">
                    <i class="bi bi-send me-1"></i>Enviar a Revisión
                </button>
            </div>
        </form>
    </div>
</div>
<?php include APP_ROOT . '/app/views/tareas/_comentarios_tarea.php'; ?>
