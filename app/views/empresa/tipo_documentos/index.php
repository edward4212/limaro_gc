<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-file-earmark-text me-2"></i>Tipos de Documento</h2></div>
    <?php if (Auth::puede('tipo_documentos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/tipos-documento/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Tipo
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Tipo de Documento</th><th>Sigla</th><th>Documentos</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($tipos as $t): ?>
                <tr>
                    <td><?= e($t['id_tipo_documento']) ?></td>
                    <td><strong><?= e($t['tipo_documento']) ?></strong></td>
                    <td><code class="bg-light px-2 py-1 rounded"><?= e($t['sigla_tipo_documento']) ?></code></td>
                    <td><span class="badge bg-info text-dark"><?= e($t['total_docs']) ?></span></td>
                    <td><?= badgeEstado($t['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('tipo_documentos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/tipos-documento/editar/<?= $t['id_tipo_documento'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::puede('tipo_documentos', 'eliminar') && $t['estado'] === 'ACTIVO'): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/tipos-documento/eliminar/<?= $t['id_tipo_documento'] ?>','¿Inactivar este tipo de documento?')">
                            <i class="bi bi-slash-circle"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
