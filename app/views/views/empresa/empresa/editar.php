<?php
$puedeEditar = Auth::puede('empresa_editar', 'editar');
$ro          = $puedeEditar ? '' : 'readonly';   // atributo readonly
$roClass     = $puedeEditar ? '' : 'bg-light';   // clase visual de solo lectura

// URLs de archivos actuales
$logoUrl = !empty($empresa['logo'])
    ? (str_starts_with($empresa['logo'], '/storage/')
        ? APP_URL . '/empresa-img/logo'
        : APP_URL . '/public/assets/img/' . $empresa['logo'])
    : null;

$organigramaUrl = !empty($empresa['organigrama']) && str_starts_with($empresa['organigrama'], '/storage/')
    ? APP_URL . '/empresa-img/organigrama' : null;

$mapaUrl = !empty($empresa['mapa_procesos']) && str_starts_with($empresa['mapa_procesos'], '/storage/')
    ? APP_URL . '/empresa-img/mapa' : null;

$orgEsPdf  = $organigramaUrl && str_ends_with(strtolower($empresa['organigrama']), '.pdf');
$mapaEsPdf = $mapaUrl        && str_ends_with(strtolower($empresa['mapa_procesos']), '.pdf');
?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-building me-2"></i><?= e($pageTitle) ?></h2>
        <!--<?php if (!$puedeEditar): ?>-->
        <!--<span class="badge bg-secondary ms-2">-->
        <!--    <i class="bi bi-eye me-1"></i>Solo lectura-->
        <!--</span>-->
        <!--<?php endif; ?>-->
    </div>
</div>

<!--<?php if (!$puedeEditar): ?>-->
<!--<div class="alert alert-info d-flex gap-2 align-items-center py-2 mb-3">-->
<!--    <i class="bi bi-info-circle-fill"></i>-->
<!--    Solo puede visualizar esta información. Contacte al administrador si necesita realizar cambios.-->
<!--</div>-->
<!--<?php endif; ?>-->

<?php
// Si no puede editar: renderizar como vista simple sin <form>
// Si puede editar: envolver en <form> con POST
if ($puedeEditar):
?>
<form action="<?= e(APP_URL) ?>/empresa/editar" method="POST"
      enctype="multipart/form-data" novalidate data-novalidate>
    <?= csrfField() ?>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Columna izquierda: datos textuales ──────────────── -->
    <div class="col-lg-7">

        <!-- Identificación -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-1"></i>Identificación
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nombre de la Empresa
                        <?php if ($puedeEditar): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <input type="text" class="form-control <?= $roClass ?>"
                           name="nombre_empresa"
                           value="<?= e($empresa['nombre_empresa'] ?? '') ?>"
                           maxlength="500" <?= $ro ?>
                           <?= $puedeEditar ? 'required' : '' ?>>
                </div>
                <div class="mb-0">
                    <label class="form-label">URL Corporativa</label>
                    <input type="url" class="form-control <?= $roClass ?>"
                           name="URL"
                           value="<?= e($empresa['URL'] ?? '') ?>"
                           placeholder="https://ejemplo.com.co" <?= $ro ?>>
                </div>
            </div>
        </div>

        <!-- Direccionamiento estratégico -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-compass me-1"></i>Direccionamiento Estratégico
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Misión</label>
                    <textarea class="form-control <?= $roClass ?>"
                              name="mision" rows="4" <?= $ro ?>><?= e($empresa['mision'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Visión</label>
                    <textarea class="form-control <?= $roClass ?>"
                              name="vision" rows="4" <?= $ro ?>><?= e($empresa['vision'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Política de Calidad</label>
                    <textarea class="form-control <?= $roClass ?>"
                              name="politica_calidad" rows="5" <?= $ro ?>><?= e($empresa['politica_calidad'] ?? '') ?></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Objetivos de Calidad</label>
                    <textarea class="form-control <?= $roClass ?>"
                              name="objetivos_calidad" rows="5" <?= $ro ?>><?= e($empresa['objetivos_calidad'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Columna derecha: archivos ──────────────────────── -->
    <div class="col-lg-5">

        <!-- Logo -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-image me-1"></i>Logo de la Empresa</div>
            <div class="card-body">
                <?php if ($logoUrl): ?>
                <div class="mb-3 text-center">
                    <img src="<?= e($logoUrl) ?>" alt="Logo"
                         class="img-fluid rounded border"
                         style="max-height:120px; object-fit:contain;">
                    <div class="form-text mt-1">Logo actual</div>
                </div>
                <?php endif; ?>

                <?php if ($puedeEditar): ?>
                <label class="form-label"><?= $logoUrl ? 'Reemplazar logo' : 'Subir logo' ?></label>
                <input type="file" class="form-control" name="logo"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       id="inp_logo">
                <div class="form-text">JPG, PNG, WEBP — máx. 5 MB</div>
                <img id="prev_logo" src="#" alt="Vista previa"
                     class="img-fluid rounded mt-2 border d-none"
                     style="max-height:100px; object-fit:contain;">
                <?php elseif (!$logoUrl): ?>
                <p class="text-muted mb-0" style="font-size:13px;">Sin logo registrado.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Organigrama -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-diagram-2 me-1"></i>Organigrama</div>
            <div class="card-body">
                <?php if ($organigramaUrl): ?>
                <div class="mb-3">
                    <?php if ($orgEsPdf): ?>
                    <a href="<?= e($organigramaUrl) ?>" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-file-pdf me-1"></i>Ver organigrama (PDF)
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

                <?php if ($puedeEditar): ?>
                <label class="form-label"><?= $organigramaUrl ? 'Reemplazar organigrama' : 'Subir organigrama' ?></label>
                <input type="file" class="form-control" name="organigrama"
                       accept="image/jpeg,image/png,image/webp,application/pdf"
                       id="inp_org">
                <div class="form-text">JPG, PNG, WEBP o PDF — máx. 20 MB</div>
                <img id="prev_org" src="#" alt="Vista previa"
                     class="img-fluid rounded mt-2 border d-none"
                     style="max-height:120px; object-fit:contain;">
                <?php elseif (!$organigramaUrl): ?>
                <p class="text-muted mb-0" style="font-size:13px;">Sin organigrama registrado.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mapa de Procesos -->
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-map me-1"></i>Mapa de Procesos</div>
            <div class="card-body">
                <?php if ($mapaUrl): ?>
                <div class="mb-3">
                    <?php if ($mapaEsPdf): ?>
                    <a href="<?= e($mapaUrl) ?>" target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-file-pdf me-1"></i>Ver mapa de procesos (PDF)
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

                <?php if ($puedeEditar): ?>
                <label class="form-label"><?= $mapaUrl ? 'Reemplazar mapa' : 'Subir mapa de procesos' ?></label>
                <input type="file" class="form-control" name="mapa_procesos"
                       accept="image/jpeg,image/png,image/webp,application/pdf"
                       id="inp_mapa">
                <div class="form-text">JPG, PNG, WEBP o PDF — máx. 20 MB</div>
                <img id="prev_mapa" src="#" alt="Vista previa"
                     class="img-fluid rounded mt-2 border d-none"
                     style="max-height:120px; object-fit:contain;">
                <?php elseif (!$mapaUrl): ?>
                <p class="text-muted mb-0" style="font-size:13px;">Sin mapa de procesos registrado.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div><!-- /row -->

<?php if ($puedeEditar): ?>
<div class="d-flex gap-2 mb-5">
    <button type="submit" class="btn btn-lim-primary btn-lg">
        <i class="bi bi-save me-1"></i>Guardar Cambios
    </button>
</div>
</form>

<script>
function previewImagen(inputId, prevId) {
    const input = document.getElementById(inputId);
    const prev  = document.getElementById(prevId);
    if (!input || !prev) return;
    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) { prev.classList.add('d-none'); return; }
        if (!file.type.startsWith('image/')) {
            prev.classList.add('d-none');
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
        reader.onload = e => { prev.src = e.target.result; prev.classList.remove('d-none'); };
        reader.readAsDataURL(file);
    });
}
previewImagen('inp_logo', 'prev_logo');
previewImagen('inp_org',  'prev_org');
previewImagen('inp_mapa', 'prev_mapa');
</script>
<?php endif; ?>
