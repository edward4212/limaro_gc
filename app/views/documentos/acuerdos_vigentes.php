<div class="page-header">
    <div><h2><i class="bi bi-bookmark-check me-2"></i>Acuerdos Vigentes</h2></div>
</div>

<!-- Filtro año -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1">Filtrar por año:</label>
                <select class="form-select form-select-sm" name="año">
                    <option value="">Todos los años</option>
                    <?php foreach ($años as $a): ?>
                    <option value="<?= e($a) ?>" <?= ($filtroAño == $a) ? 'selected' : '' ?>><?= e($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-lim-primary btn-sm">Filtrar</button>
                <a href="<?= e(APP_URL) ?>/acuerdos/vigentes" class="btn btn-secondary btn-sm ms-1">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr><th>Año</th><th>Número</th><th>Nombre</th><th>Tipo</th><th>Fecha Aprobación</th><th>Archivo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($acuerdos as $a): ?>
                <tr>
                    <td><?= e($a['año_acuerdo']) ?></td>
                    <td><strong><?= e($a['numero_acuerdo']) ?></strong></td>
                    <td><?= e($a['nombre_acuerdo']) ?></td>
                    <td><?= e($a['tipo_documento']) ?></td>
                    <td><?= fechaEs($a['fecha_aprobacion']) ?></td>
                    <td>
                        <?php if (!empty($a['id_archivo'])): ?>
                        <a href="<?= e(APP_URL) ?>/archivo/<?= $a['id_archivo'] ?>"
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download me-1"></i>Descargar PDF
                        </a>
                        <?php else: ?>
                        <span class="text-muted">Sin archivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
