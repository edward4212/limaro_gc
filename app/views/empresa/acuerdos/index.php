<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-journal-check me-2"></i>Acuerdos</h2></div>
    <div class="d-flex gap-2">
        <?php if (Auth::puede('acuerdos', 'ver')): ?>
        <a href="<?= e(APP_URL) ?>/acuerdos/descargar-zip"
           class="btn btn-outline-success btn-sm"
           title="Descargar todos los acuerdos vigentes con archivo como ZIP">
            <i class="bi bi-file-zip me-1"></i>Descargar ZIP
        </a>
        <?php endif; ?>
        <?php if (Auth::puede('acuerdos', 'crear')): ?>
        <a href="<?= e(APP_URL) ?>/acuerdos/crear" class="btn btn-lim-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Acuerdo
        </a>
        <?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;" data-order='[[0,"desc"],[1,"desc"]]'>
            <thead>
                <tr><th>Año</th><th>Número</th><th>Nombre</th><th>N° Acta</th><th>Fecha Aprobación</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($acuerdos as $a): ?>
                <tr>
                    <td><?= e($a['año_acuerdo']) ?></td>
                    <td><strong><?= e($a['numero_acuerdo']) ?></strong></td>
                    <td><?= e(truncar($a['nombre_acuerdo'], 60)) ?></td>
                    <td><span class="badge bg-info text-dark"><?= e($a['acta_aprobacion'] ?? '—') ?></span></td>
                    <td><?= fechaEs($a['fecha_aprobacion']) ?></td>
                    <td><?= badgeEstado($a['estado'] ?? 'ACTIVO') ?></td>
                    <td>
                        <?php if (!empty($a['id_archivo'])): ?>
                        <!-- CA-1 HU-007: Ver inline en el navegador -->
                        <a href="<?= e(APP_URL) ?>/acuerdos/ver/<?= $a['id_acuerdo'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-outline-danger py-0" title="Ver PDF en navegador">
                            <i class="bi bi-eye"></i>
                        </a>
                        <!-- Descargar: añadir ?download=1 -->
                        <a href="<?= e(APP_URL) ?>/archivo/<?= $a['id_archivo'] ?>"
                           class="btn btn-sm btn-outline-secondary py-0" title="Descargar PDF">
                            <i class="bi bi-download"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (($a['estado'] ?? 'ACTIVO') === 'ACTIVO' && Auth::puede('acuerdos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/acuerdos/editar/<?= $a['id_acuerdo'] ?>"
                           class="btn btn-sm btn-outline-primary py-0" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (($a['estado'] ?? 'ACTIVO') === 'ACTIVO' && Auth::puede('acuerdos', 'eliminar')): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/acuerdos/eliminar/<?= $a['id_acuerdo'] ?>','¿Inactivar este acuerdo?')">
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
