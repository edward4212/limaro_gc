<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-file-earmark-text me-2"></i>Documentos Registrados</h2></div>
    <?php if (Auth::puede('documentos_registrados', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/documentos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Documento
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Proceso</th>
                    <th>Subproceso</th>
                    <th>Versión</th>
                    <th>Estado V.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No hay documentos registrados.</td></tr>
                <?php else: ?>
                <?php foreach ($documentos as $d): ?>
                <tr>
                    <td><code><?= e($d['codigo']) ?></code>
                        <!--<?php if (!empty($d['codigo_anterior'])): ?>-->
                        <!--<br><small class="text-muted"><del><?= e($d['codigo_anterior']) ?></del></small>-->
                        <!--<?php endif; ?>-->
                    </td>
                    <td><?= e(truncar($d['nombre_documento'], 55)) ?></td>
                    <td><span class="badge bg-secondary"><?= e($d['sigla_tipo_documento']) ?></span></td>
                    <td>
                        <small><?= e($d['macroproceso']) ?></small><br>
                        <strong style="font-size:12px;"><?= e($d['proceso']) ?></strong>
                    </td>
                    <td>
                        <?php if (!empty($d['nombre_subproceso'])): ?>
                        <span class="badge bg-info text-dark"><?= e($d['nombre_subproceso']) ?></span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary">v<?= e($d['version_actual'] ?? 0) ?></span>
                    </td>
                    <td><?= badgeEstado($d['estado_version'] ?? 'CREADO') ?></td>
                    <td>
                        <!-- Historial de versiones (pasa from=documentos para volver aquí) -->
                        <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$d['id_documento'] ?>?from=documentos"
                           class="btn btn-sm btn-outline-info py-0" title="Historial versiones">
                            <i class="bi bi-layers"></i>
                        </a>
                        <?php if (Auth::puede('documentos_registrados', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/documentos/editar/<?= (int)$d['id_documento'] ?>"
                           class="btn btn-sm btn-outline-primary py-0" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::puede('documentos_registrados', 'eliminar')): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm(
                                    '<?= e(APP_URL) ?>/documentos/eliminar/<?= (int)$d['id_documento'] ?>',
                                    '¿Inactivar: <?= e(addslashes($d['nombre_documento'])) ?>?'
                                )" title="Inactivar">
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
