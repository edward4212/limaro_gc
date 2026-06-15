<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-diagram-2 me-2"></i>Procesos</h2>
    </div>
    <?php if (Auth::puede('procesos','crear')): ?>
    <a href="<?= e(APP_URL) ?>/procesos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Proceso
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Macroproceso</th>
                    <th>Proceso</th>
                    <th style="width:70px" class="text-center">Sigla</th>
                    <th>Objetivo</th>
                    <th style="width:80px" class="text-center">Estado</th>
                    <th style="width:110px" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($procesos as $p):
                $nDoc = $conteosDocumentos[$p['id_proceso']] ?? 0;
            ?>
            <tr>
                <td style="font-size:12px;"><?= (int)$p['id_proceso'] ?></td>
                <td style="font-size:12px;"><?= e($p['nombre_macroproceso'] ?? '—') ?></td>
                <td style="font-size:13px;font-weight:600;"><?= e($p['proceso']) ?></td>
                <td class="text-center">
                    <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px;">
                        <?= e($p['sigla_proceso'] ?? '—') ?>
                    </code>
                </td>
                <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= e(truncar($p['objetivo'] ?? '', 60)) ?>
                </td>
                <td class="text-center">
                    <span class="badge <?= $p['estado'] === 'ACTIVO' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $p['estado'] ?>
                    </span>
                </td>
                <td class="text-center">
                    <?php if (Auth::puede('procesos','editar')): ?>
                    <a href="<?= e(APP_URL) ?>/procesos/editar/<?= $p['id_proceso'] ?>"
                       class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>

                    <?php if (Auth::puede('procesos','eliminar') && $p['estado'] === 'ACTIVO'): ?>
                        <?php if ($nDoc > 0): ?>
                        <!-- CA-2: botón bloqueado si tiene documentos activos -->
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary py-0 px-2" disabled
                                title="No se puede inactivar: tiene <?= $nDoc ?> documento(s) activo(s)">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                        <span class="badge bg-warning text-dark" style="font-size:12px;"
                              title="Documentos activos vinculados">
                            <?= $nDoc ?> doc.
                        </span>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                onclick="setModalConfirm(
                                    '<?= e(APP_URL) ?>/procesos/eliminar/<?= $p['id_proceso'] ?>',
                                    '¿Inactivar el proceso: <?= e(addslashes($p['proceso'])) ?>?'
                                )" title="Inactivar">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($procesos)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-diagram-2 fs-3 d-block mb-2"></i>
                No hay procesos registrados.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
