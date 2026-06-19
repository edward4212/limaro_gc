<div class="page-header">
    <div>
        <h2><i class="bi bi-mortarboard me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/competencia">Competencia y Capacitación</a></li>
            <li class="breadcrumb-item active"><?= e($cargo['cargo']) ?></li>
        </ol></nav>
    </div>
</div>

<form action="<?= e(APP_URL) ?>/competencia/cargo/<?= (int)$cargo['id_cargo'] ?>" method="POST">
    <?= csrfField() ?>
<div class="card mb-4">
    <div class="card-header">Requisitos de Formación Académica y Experiencia</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Formación Académica</label>
                <textarea class="form-control" name="formacion_academica" rows="3"
                    placeholder="Ej: Bachiller, técnico, tecnólogo o profesional en áreas administrativas..."><?= e($perfil['formacion_academica'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Experiencia</label>
                <textarea class="form-control" name="experiencia" rows="3"
                    placeholder="Ej: Mínimo seis (6) meses de experiencia en..."><?= e($perfil['experiencia'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Formación / Entrenamiento</label>
                <textarea class="form-control" name="formacion_entrenamiento" rows="4"
                    placeholder="Ej: Programas ofimáticos, atención al cliente..."><?= e($perfil['formacion_entrenamiento'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Habilidades</label>
                <textarea class="form-control" name="habilidades" rows="4"
                    placeholder="Ej: Ofimática, análisis financiero, comunicación asertiva..."><?= e($perfil['habilidades'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/competencia" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar Perfil</button>
</div>
</form>
