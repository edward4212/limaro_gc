<div class="page-header">
    <div>
        <h2><i class="bi bi-journal-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/competencia">Competencia y Capacitación</a></li>
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/competencia/capacitaciones">Capacitaciones</a></li>
            <li class="breadcrumb-item active">Registrar</li>
        </ol></nav>
    </div>
</div>

<form action="<?= e(APP_URL) ?>/competencia/capacitaciones/crear" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Empleado <span class="text-danger">*</span></label>
                <select class="form-select" name="id_empleado" required>
                    <option value="">— Seleccione —</option>
                    <?php foreach ($empleados as $emp): ?>
                    <option value="<?= (int)$emp['id_empleado'] ?>">
                        <?= e($emp['nombre_completo']) ?> — <?= e($emp['cargo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre del Curso <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre_curso" required>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo" id="selTipoCap" onchange="toggleEntidad()">
                    <option value="INTERNA">🎓 Interna (Moodle)</option>
                    <option value="EXTERNA">🌐 Externa</option>
                </select>
            </div>
            <div class="col-md-5" id="grupoEntidad" style="display:none;">
                <label class="form-label">Entidad Capacitadora</label>
                <input type="text" class="form-control" name="entidad_capacitadora">
            </div>
            <div class="col-md-2">
                <label class="form-label">Horas</label>
                <input type="number" class="form-control" name="horas" min="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">Resultado</label>
                <select class="form-select" name="resultado">
                    <option value="APROBADO">Aprobado</option>
                    <option value="EN_CURSO">En Curso</option>
                    <option value="NO_APROBADO">No Aprobado</option>
                </select>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Fecha de Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha de Finalización <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="fecha_finalizacion" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Certificado (PDF)</label>
                <input type="file" class="form-control" name="certificado" accept=".pdf">
                <div class="form-text">Máx. 20 MB.</div>
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="observaciones" rows="2"></textarea>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/competencia/capacitaciones" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
</div>
</form>

<script>
function toggleEntidad() {
    var esExterna = document.getElementById('selTipoCap').value === 'EXTERNA';
    document.getElementById('grupoEntidad').style.display = esExterna ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleEntidad);
</script>
