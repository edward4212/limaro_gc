<?php
// Función recursiva para renderizar módulos a cualquier profundidad
function renderModulo(array $modulo, array $permisos, int $nivel = 0): void {
    $id  = $modulo['id_modulo'];
    $pp  = $permisos[$id] ?? [];
    $pad = $nivel === 0 ? 'ps-3' : ($nivel === 1 ? 'ps-4' : 'ps-5');
    $rowClass = $nivel === 0 ? 'table-primary fw-bold' : ($nivel === 1 ? 'table-light' : '');
    $fontSize = $nivel >= 2 ? 'font-size:12px;' : '';
    $indent   = str_repeat('&nbsp;&nbsp;&nbsp;', $nivel);
?>
<tr class="<?= $rowClass ?>">
    <td class="<?= $pad ?>" style="<?= $fontSize ?>">
        <?= $indent ?>
        <?php if ($nivel === 0): ?>
        <i class="bi <?= e($modulo['icono'] ?? 'bi-circle') ?> me-1"></i>
        <?php elseif ($nivel === 1): ?>
        <i class="bi bi-chevron-right me-1 text-muted" style="font-size:12px;"></i>
        <?php else: ?>
        <i class="bi bi-dot me-1 text-muted"></i>
        <?php endif; ?>
        <?= e($modulo['nombre']) ?>
        <?php if (!empty($modulo['url'])): ?>
        <small class="text-muted ms-1" style="font-size:12px;"><?= e($modulo['url']) ?></small>
        <?php endif; ?>
    </td>
    <?php foreach (['ver','crear','editar','eliminar'] as $perm): ?>
    <td class="text-center">
        <input type="checkbox"
               class="perm-check"
               name="permisos[<?= $id ?>][<?= $perm ?>]"
               value="1"
               <?= !empty($pp[$perm]) ? 'checked' : '' ?>>
    </td>
    <?php endforeach; ?>
</tr>
<?php
    // Renderizar hijos recursivamente
    foreach ($modulo['hijos'] ?? [] as $hijo) {
        renderModulo($hijo, $permisos, $nivel + 1);
    }
}
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-shield-lock me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/roles">Roles</a></li>
            <li class="breadcrumb-item active"><?= e($rol['rol']) ?></li>
        </ol></nav>
    </div>
    <a href="<?= e(APP_URL) ?>/roles" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a Roles
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Matriz de Permisos — <strong><?= e($rol['rol']) ?></strong></span>
        <div class="d-flex gap-1 flex-wrap">
            <button type="button" class="btn btn-sm btn-success" onclick="soloVer()">
                <i class="bi bi-eye me-1"></i>Solo Ver
            </button>
            <button type="button" class="btn btn-sm btn-primary" onclick="marcarTodos(true)">
                <i class="bi bi-check2-all me-1"></i>Todo
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodos(false)">
                <i class="bi bi-x-circle me-1"></i>Nada
            </button>
            <a href="<?= e(APP_URL) ?>/roles/sincronizar/<?= e($rol['id_rol']) ?>"
               class="btn btn-sm btn-warning"
               onclick="swalConfirm(event, '¿Agregar acceso de lectura a los módulos faltantes?')">
                <i class="bi bi-arrow-repeat me-1"></i>Sincronizar
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <form action="<?= e(APP_URL) ?>/roles/permisos/<?= e($rol['id_rol']) ?>" method="POST">
            <?= csrfField() ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Módulo / Ruta</th>
                            <th class="text-center" width="70">Ver</th>
                            <th class="text-center" width="70">Crear</th>
                            <th class="text-center" width="70">Editar</th>
                            <th class="text-center" width="80">Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulos as $padre): ?>
                        <?php renderModulo($padre, $permisos, 0); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar Permisos
                </button>
                <a href="<?= e(APP_URL) ?>/roles" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function marcarTodos(val) {
    document.querySelectorAll('.perm-check').forEach(c => c.checked = val);
}
function soloVer() {
    document.querySelectorAll('.perm-check').forEach(c => {
        c.checked = c.name.includes('[ver]');
    });
}
</script>
