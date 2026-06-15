<?php $esEdicion = isset($item) && $item !== null; ?>
<div class="page-header">
    <div>
        <h2>
            <i class="bi bi-person-<?= $esEdicion ? 'gear' : 'plus' ?> me-2"></i>
            <?= e($pageTitle) ?>
        </h2>
    </div>
</div>

<form action="<?= e(APP_URL) ?>/usuarios/<?= $esEdicion ? 'editar/' . $item['id_usuario'] : 'crear' ?>"
      method="POST">
    <?= csrfField() ?>

    <?php if ($esEdicion): ?>
    <!-- ── Modo edición: todos los campos editables ────────────────── -->

    <!-- Información Personal del Empleado -->
    <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="bi bi-person me-1"></i>Información Personal</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre_completo"
                           value="<?= e($item['nombre_completo'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="correo"
                           value="<?= e($item['correo_empleado'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cargo <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_cargo" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($cargos as $c): ?>
                        <option value="<?= $c['id_cargo'] ?>"
                                <?= ($item['id_cargo'] ?? 0) == $c['id_cargo'] ? 'selected' : '' ?>>
                            <?= e($c['cargo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono"
                           value="<?= e($item['telefono'] ?? '') ?>"
                           placeholder="Ej: 3001234567">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Documento de Identidad</label>
                    <input type="text" class="form-control" name="documento_identidad"
                           value="<?= e($item['documento_identidad'] ?? '') ?>"
                           placeholder="Número de cédula">
                </div>
            </div>
        </div>
    </div>

    <!-- Estado y Seguridad -->
    <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="bi bi-shield me-1"></i>Estado y Seguridad</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado del Usuario</label>
                    <select class="form-select" name="estado">
                        <option value="ACTIVO"   <?= ($item['estado'] ?? '') === 'ACTIVO'   ? 'selected' : '' ?>>✅ Activo</option>
                        <option value="INACTIVO" <?= ($item['estado'] ?? '') === 'INACTIVO' ? 'selected' : '' ?>>🔴 Inactivo</option>
                        <option value="CREADO"   <?= ($item['estado'] ?? '') === 'CREADO'   ? 'selected' : '' ?>>🟡 Creado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" class="form-control"
                           value="<?= e($item['usuario'] ?? '') ?>"
                           readonly style="background:#f8f9fa;"
                           title="El nombre de usuario no se puede cambiar">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Activación</label>
                    <input type="text" class="form-control"
                           value="<?= !empty($item['fecha_activacion']) ? fechaEs($item['fecha_activacion']) : '—' ?>"
                           readonly style="background:#f8f9fa;">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Vencimiento</label>
                    <input type="text" class="form-control"
                           value="<?= !empty($item['fecha_vencimiento']) ? fechaEs($item['fecha_vencimiento']) : '—' ?>"
                           readonly style="background:#f8f9fa;">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="clave_requiere_reset" value="1"
                               id="chkReset"
                               <?= !empty($item['clave_requiere_reset']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="chkReset">
                            <strong>Forzar cambio de contraseña</strong>
                            <span class="text-muted" style="font-size:12px;">
                                — El usuario deberá cambiar su contraseña en el próximo inicio de sesión
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- ── Modo creación ─────────────────────────────────────────────── -->
    <div class="card mb-3">
        <div class="card-header py-2"><strong>Información Personal</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre_completo"
                           value="<?= e(old('nombre_completo', '')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="correo"
                           value="<?= e(old('correo', '')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cargo <span class="text-danger">*</span></label>
                    <select class="form-select" name="id_cargo" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($cargos as $c): ?>
                        <option value="<?= $c['id_cargo'] ?>"
                                <?= old('id_cargo') == $c['id_cargo'] ? 'selected' : '' ?>>
                            <?= e($c['cargo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="usuario"
                           value="<?= e(old('usuario', '')) ?>" required
                           placeholder="ej: juan.garcia">
                </div>
            </div>
        </div>
    </div>

    <!-- CA-3: NO mostrar contraseña en pantalla — se envía por correo -->
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-envelope-lock me-1"></i>Contraseña Temporal</strong>
            <span class="badge bg-success" style="font-size:12px;">
                <i class="bi bi-shield-check me-1"></i>Enviada por correo automáticamente
            </span>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0 py-2" style="font-size:13px;">
                <i class="bi bi-info-circle me-2"></i>
                Se generará una contraseña aleatoria segura y se enviará
                <strong>únicamente al correo del usuario</strong>.
                El usuario deberá cambiarla en su primer inicio de sesión.
                La contraseña <strong>no se mostrará en pantalla</strong>.
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Roles Asignados -->
    <div class="card mb-3">
        <div class="card-header py-2">
            <strong><i class="bi bi-person-badge me-1"></i>Roles Asignados <span class="text-danger">*</span></strong>
        </div>
        <div class="card-body">
            <div class="row g-2">
            <?php foreach ($roles as $r): ?>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="roles[]" value="<?= $r['id_rol'] ?>"
                               id="rol_<?= $r['id_rol'] ?>"
                               <?= in_array($r['id_rol'], $item['roles_ids'] ?? []) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rol_<?= $r['id_rol'] ?>">
                            <?= e($r['rol']) ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-lim-primary">
            <i class="bi bi-<?= $esEdicion ? 'save' : 'person-plus' ?> me-1"></i>
            <?= $esEdicion ? 'Guardar Cambios' : 'Crear Usuario' ?>
        </button>
        <a href="<?= e(APP_URL) ?>/usuarios" class="btn btn-secondary">Cancelar</a>
    </div>

</form>

<?php if (!$esEdicion): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generar clave al cargar (se envía por correo, no se muestra)
});
</script>
<?php endif; ?>
