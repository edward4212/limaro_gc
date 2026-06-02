<div class="page-header">
    <div>
        <h2><i class="bi bi-shield-lock me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/roles">Roles</a></li>
            <li class="breadcrumb-item active"><?= e($rol['rol']) ?></li>
        </ol></nav>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Matriz de Permisos — <?= e($rol['rol']) ?></span>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="marcarTodos(true)">Marcar todo</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodos(false)">Desmarcar todo</button>
        </div>
    </div>
    <div class="card-body p-0">
        <form action="<?= e(APP_URL) ?>/roles/permisos/<?= e($rol['id_rol']) ?>" method="POST">
            <?= csrfField() ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Módulo</th>
                            <th class="text-center" width="80">Ver</th>
                            <th class="text-center" width="80">Crear</th>
                            <th class="text-center" width="80">Editar</th>
                            <th class="text-center" width="80">Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulos as $padre): ?>
                        <!-- Módulo padre -->
                        <tr class="table-light">
                            <td class="ps-3 fw-bold" colspan="5">
                                <i class="bi <?= e($padre['icono'] ?? 'bi-circle') ?> me-2"></i>
                                <?= e($padre['nombre']) ?>
                            </td>
                        </tr>
                        <?php $pid = $padre['id_modulo']; $pp = $permisos[$pid] ?? []; ?>
                        <tr>
                            <td class="ps-4 text-muted" style="font-size:12px;"><?= e($padre['nombre']) ?> (raíz)</td>
                            <?php foreach (['ver','crear','editar','eliminar'] as $perm): ?>
                            <td class="text-center">
                                <input type="checkbox" name="permisos[<?= $pid ?>][<?= $perm ?>]" value="1"
                                       <?= !empty($pp[$perm]) ? 'checked' : '' ?>>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Submodulos -->
                        <?php foreach ($padre['hijos'] as $hijo): ?>
                        <?php $hid = $hijo['id_modulo']; $hp = $permisos[$hid] ?? []; ?>
                        <tr>
                            <td class="ps-5" style="font-size:12px;">
                                <i class="bi <?= e($hijo['icono'] ?? 'bi-dot') ?> me-1 text-muted"></i>
                                <?= e($hijo['nombre']) ?>
                                <?php if ($hijo['url'] ?? null): ?><small class="text-muted"> — <?= e($hijo['url']) ?></small><?php endif; ?>
                            </td>
                            <?php foreach (['ver','crear','editar','eliminar'] as $perm): ?>
                            <td class="text-center">
                                <input type="checkbox" name="permisos[<?= $hid ?>][<?= $perm ?>]" value="1"
                                       <?= !empty($hp[$perm]) ? 'checked' : '' ?>>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Sub-submodulos -->
                        <?php foreach (($hijo['hijos'] ?? []) as $nieto): ?>
                        <?php $nid = $nieto['id_modulo']; $np = $permisos[$nid] ?? []; ?>
                        <tr>
                            <td class="ps-5" style="font-size:12px;padding-left:72px !important;">
                                <i class="bi bi-dot text-muted"></i>
                                <?= e($nieto['nombre']) ?>
                            </td>
                            <?php foreach (['ver','crear','editar','eliminar'] as $perm): ?>
                            <td class="text-center">
                                <input type="checkbox" name="permisos[<?= $nid ?>][<?= $perm ?>]" value="1"
                                       <?= !empty($np[$perm]) ? 'checked' : '' ?>>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar Permisos
                </button>
                <a href="<?= e(APP_URL) ?>/roles" class="btn btn-secondary ms-2">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<script>
function marcarTodos(v) {
    document.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = v);
}
</script>
