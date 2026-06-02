<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-journal-check me-2"></i>Acuerdos</h2></div>
    <?php if (Auth::puede('acuerdos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/acuerdos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Acuerdo
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Año</th><th>Número</th><th>Nombre</th><th>Tipo</th><th>Fecha Aprobación</th><th>Acta</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($acuerdos as $a): ?>
                <tr>
                    <td><?= e($a['año_acuerdo']) ?></td>
                    <td><strong><?= e($a['numero_acuerdo']) ?></strong></td>
                    <td><?= e(truncar($a['nombre_acuerdo'], 60)) ?></td>
                    <td><?= e($a['tipo_documento']) ?></td>
                    <td><?= fechaEs($a['fecha_aprobacion']) ?></td>
                    <td><?= e($a['acta_aprobacion']) ?></td>
                    <td>
                        <?php if (!empty($a['id_archivo'])): ?>
                        <a href="<?= e(APP_URL) ?>/archivo/<?= $a['id_archivo'] ?>"
                           class="btn btn-sm btn-outline-success py-0" title="Descargar PDF">
                            <i class="bi bi-file-pdf"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::puede('acuerdos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/acuerdos/editar/<?= $a['id_acuerdo'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::puede('acuerdos', 'eliminar')): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/acuerdos/eliminar/<?= $a['id_acuerdo'] ?>','¿Eliminar este acuerdo?')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
