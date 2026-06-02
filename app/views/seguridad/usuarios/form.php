<?php
$isEdit      = !is_null($item);
$action      = $isEdit
    ? e(APP_URL) . '/usuarios/editar/' . $item['id_usuario']
    : e(APP_URL) . '/usuarios/crear';
$rolesActivos = $isEdit ? ($item['roles_ids'] ?? []) : [];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-person-plus me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/usuarios">Usuarios</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-header"><?= e($pageTitle) ?></div>
    <div class="card-body">
        <form action="<?= $action ?>" method="POST" novalidate>
            <?= csrfField() ?>

            <?php if ($isEdit): ?>
            <!-- ── Modo edición: solo roles y estado ────────────── -->
            <div class="alert alert-info py-2">
                <i class="bi bi-person me-2"></i>
                <strong><?= e($item['usuario']) ?></strong> — <?= e($item['nombre_completo']) ?>
                <span class="ms-2 text-muted"><?= e($item['cargo'] ?? '') ?></span>
            </div>

            <?php else: ?>
            <!-- ── Modo creación ─────────────────────────────────── -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre_completo"
                           value="<?= old('nombre_completo') ?>" maxlength="200" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="correo"
                           value="<?= old('correo') ?>" maxlength="200" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Cargo <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_cargo" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($cargos as $c): ?>
                        <option value="<?= e($c['id_cargo']) ?>"><?= e($c['cargo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="usuario"
                           value="<?= old('usuario') ?>" maxlength="50" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Clave <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="clave" required>
                    <div class="form-text">Mín. 8 chars, mayús, minús, número y símbolo.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmar Clave <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="clave_confirm" required>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Roles (compartido crear/editar) ─────────────── -->
            <div class="mb-3">
                <label class="form-label">
                    Roles asignados <span class="text-danger">*</span>
                    <span class="text-muted fw-normal">(puede seleccionar varios)</span>
                </label>
                <div class="border rounded p-3" style="max-height:220px; overflow-y:auto; background:#f9f9f9;">
                    <?php if (empty($roles)): ?>
                    <p class="text-muted mb-0">No hay roles activos disponibles.</p>
                    <?php else: ?>
                    <?php foreach ($roles as $r): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox"
                               name="roles[]"
                               value="<?= e($r['id_rol']) ?>"
                               id="rol_<?= e($r['id_rol']) ?>"
                            <?= in_array((int)$r['id_rol'], array_map('intval', $rolesActivos)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rol_<?= e($r['id_rol']) ?>">
                            <strong><?= e($r['rol']) ?></strong>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="form-text">
                    <i class="bi bi-info-circle"></i>
                    Los permisos son la <strong>unión</strong> de todos los roles seleccionados.
                    Si dos roles tienen permisos distintos sobre el mismo módulo, el más permisivo aplica.
                </div>
            </div>

            <!-- Estado en modo edición -->
            <?php if ($isEdit): ?>
            <div class="mb-4 col-md-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <option value="ACTIVO"   <?= ($item['estado'] ?? '') === 'ACTIVO'   ? 'selected' : '' ?>>Activo</option>
                    <option value="INACTIVO" <?= ($item['estado'] ?? '') === 'INACTIVO' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-lim-primary">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
                <a href="<?= e(APP_URL) ?>/usuarios" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
