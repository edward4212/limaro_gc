<?php
// Helpers para mostrar archivos actuales
$logoUrl        = !empty($empresa['logo'])
    ? (str_starts_with($empresa['logo'], '/storage/') ? APP_URL . '/public' . $empresa['logo'] : APP_URL . '/public/assets/img/' . $empresa['logo'])
    : null;

$organigramaUrl = !empty($empresa['organigrama'])
    ? (str_starts_with($empresa['organigrama'], '/storage/') ? APP_URL . '/public' . $empresa['organigrama'] : null)
    : null;

$mapaUrl        = !empty($empresa['mapa_procesos'])
    ? (str_starts_with($empresa['mapa_procesos'], '/storage/') ? APP_URL . '/public' . $empresa['mapa_procesos'] : null)
    : null;

// Detectar si organigrama/mapa son PDF
$orgEsPdf = $organigramaUrl && str_ends_with(strtolower($empresa['organigrama']), '.pdf');
$mapaEsPdf = $mapaUrl && str_ends_with(strtolower($empresa['mapa_procesos']), '.pdf');
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-building me-2"></i><?= e($pageTitle) ?></h2>
    </div>
</div>

<form action="<?= e(APP_URL) ?>/empresa/editar" method="POST" enctype="multipart/form-data" novalidate>
    <?= csrfField() ?>

    <div class="row g-4">

        <!-- ── Columna izquierda: datos textuales ──────────────────── -->
        <div class="col-lg-7">

            <!-- Identificación -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i>Identificación</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre_empresa"
                               value="<?= e($empresa['nombre_empresa'] ?? '') ?>"
                               maxlength="500" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">URL Corporativa</label>
                        <input type="url" class="form-control" name="URL"
                               value="<?= e($empresa['URL'] ?? '') ?>"
                               placeholder="https://ejemplo.com.co">
                    </div>
                </div>
            </div>

            <!-- Direccionamiento estratégico -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-compass me-1"></i>Direccionamiento Estratégico</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Misión</label>
                        <textarea class="form-control" name="mision" rows="4"><?= e($empresa['mision'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visión</label>
                        <textarea class="form-control" name="vision" rows="4"><?= e($empresa['vision'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Política de Calidad</label>
                        <textarea class="form-control" name="politica_calidad" rows="5"><?= e($empresa['politica_calidad'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Objetivos de Calidad</label>
                        <textarea class="form-control" name="objetivos_calidad" rows="5"><?= e($empresa['objetivos_calidad'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Columna derecha: archivos ──────────────────────────── -->
        <div class="col-lg-5">

            <!-- Logo -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-image me-1"></i>Logo de la Empresa</div>
                <div class="card-body">
                    <?php if ($logoUrl): ?>
                    <div class="mb-3 text-center">
                        <img src="<?= e($logoUrl) ?>" alt="Logo actual"
                             class="img-fluid rounded border"
                             style="max-height:120px; object-fit:contain;">
                        <div class="form-text mt-1">Logo actual</div>
                    </div>
                    <?php endif; ?>
                    <label class="form-label"><?= $logoUrl ? 'Reemplazar logo' : 'Subir logo' ?></label>
                    <input type="file" class="form-control" name="logo"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           id="inp_logo">
                    <div class="form-text">JPG, PNG, WEBP — máx. 5 MB</div>
                    <!-- Preview antes de guardar -->
                    <img id="prev_logo" src="#" alt="Vista previa"
                         class="img-fluid rounded mt-2 border d-none"
                         style="max-height:100px; object-fit:contain;">
                </div>
            </div>

            <!-- Organigrama -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-diagram-2 me-1"></i>Organigrama</div>
                <div class="card-body">
                    <?php if ($organigramaUrl): ?>
                    <div class="mb-3">
                        <?php if ($orgEsPdf): ?>
                        <a href="<?= e($organigramaUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-pdf me-1"></i>Ver organigrama actual (PDF)
                        </a>
                        <?php else: ?>
                        <a href="<?= e($organigramaUrl) ?>" target="_blank">
                            <img src="<?= e($organigramaUrl) ?>" alt="Organigrama"
                                 class="img-fluid rounded border"
                                 style="max-height:140px; object-fit:contain;">
                        </a>
                        <?php endif; ?>
                        <div class="form-text mt-1">Organigrama actual</div>
                    </div>
                    <?php endif; ?>
                    <label class="form-label"><?= $organigramaUrl ? 'Reemplazar organigrama' : 'Subir organigrama' ?></label>
                    <input type="file" class="form-control" name="organigrama"
                           accept="image/jpeg,image/png,image/webp,application/pdf"
                           id="inp_org">
                    <div class="form-text">JPG, PNG, WEBP o PDF — máx. 20 MB</div>
                    <img id="prev_org" src="#" alt="Vista previa"
                         class="img-fluid rounded mt-2 border d-none"
                         style="max-height:120px; object-fit:contain;">
                </div>
            </div>

            <!-- Mapa de Procesos -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-map me-1"></i>Mapa de Procesos</div>
                <div class="card-body">
                    <?php if ($mapaUrl): ?>
                    <div class="mb-3">
                        <?php if ($mapaEsPdf): ?>
                        <a href="<?= e($mapaUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-pdf me-1"></i>Ver mapa actual (PDF)
                        </a>
                        <?php else: ?>
                        <a href="<?= e($mapaUrl) ?>" target="_blank">
                            <img src="<?= e($mapaUrl) ?>" alt="Mapa de Procesos"
                                 class="img-fluid rounded border"
                                 style="max-height:140px; object-fit:contain;">
                        </a>
                        <?php endif; ?>
                        <div class="form-text mt-1">Mapa de procesos actual</div>
                    </div>
                    <?php endif; ?>
                    <label class="form-label"><?= $mapaUrl ? 'Reemplazar mapa' : 'Subir mapa de procesos' ?></label>
                    <input type="file" class="form-control" name="mapa_procesos"
                           accept="image/jpeg,image/png,image/webp,application/pdf"
                           id="inp_mapa">
                    <div class="form-text">JPG, PNG, WEBP o PDF — máx. 20 MB</div>
                    <img id="prev_mapa" src="#" alt="Vista previa"
                         class="img-fluid rounded mt-2 border d-none"
                         style="max-height:120px; object-fit:contain;">
                </div>
            </div>

        </div>
    </div><!-- /row -->

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-lim-primary btn-lg">
            <i class="bi bi-save me-1"></i>Guardar Cambios
        </button>
    </div>

</form>

<script>
// Preview de imágenes antes de subir
function previewImagen(inputId, prevId) {
    const input = document.getElementById(inputId);
    const prev  = document.getElementById(prevId);
    if (!input || !prev) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) { prev.classList.add('d-none'); return; }

        // Solo mostrar preview si es imagen (no PDF)
        if (!file.type.startsWith('image/')) {
            prev.classList.add('d-none');
            // Mostrar nombre del archivo PDF seleccionado
            let info = document.getElementById(prevId + '_info');
            if (!info) {
                info = document.createElement('div');
                info.id = prevId + '_info';
                info.className = 'form-text mt-1 text-success';
                prev.after(info);
            }
            info.textContent = '✔ Archivo seleccionado: ' + file.name;
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            prev.src = e.target.result;
            prev.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
}

previewImagen('inp_logo', 'prev_logo');
previewImagen('inp_org',  'prev_org');
previewImagen('inp_mapa', 'prev_mapa');
</script>
