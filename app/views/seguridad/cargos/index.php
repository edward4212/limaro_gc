<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>
<div class="page-header">
    <div><h2><i class="bi bi-person-badge me-2"></i>Cargos</h2></div>
    <?php if (Auth::puede('cargos', 'crear')): ?>
    <a href="<?= e(APP_URL) ?>/cargos/crear" class="btn btn-lim-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Cargo
    </a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>#</th><th>Cargo</th><th>Empleados</th><th>Manual</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($cargos as $c): ?>
                <tr>
                    <td><?= e($c['id_cargo']) ?></td>
                    <td><strong><?= e($c['cargo']) ?></strong></td>
                    <td><span class="badge bg-info text-dark"><?= e($c['total_empleados']) ?></span></td>
                    <td>
                        <?php if ($c['id_archivo'] ?? null): ?>
                        <a href="<?= e(APP_URL) ?>/archivo/<?= $c['id_archivo'] ?>"
                           class="btn btn-sm btn-outline-success py-0">
                            <i class="bi bi-file-pdf"></i> Manual
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= badgeEstado($c['estado']) ?></td>
                    <td>
                        <?php if (Auth::puede('cargos', 'editar')): ?>
                        <a href="<?= e(APP_URL) ?>/cargos/editar/<?= $c['id_cargo'] ?>"
                           class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (Auth::puede('cargos', 'eliminar') && $c['estado'] === 'ACTIVO'): ?>
                        <button class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#modalConfirm"
                                onclick="setModalConfirm('<?= e(APP_URL) ?>/cargos/eliminar/<?= $c['id_cargo'] ?>','¿Inactivar cargo?')">
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
