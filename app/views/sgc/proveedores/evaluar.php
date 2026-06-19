<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/proveedores">Gestión de Proveedores</a></li>
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/proveedores/ver/<?= (int)$item['id'] ?>"><?= e($item['codigo']) ?></a></li>
            <li class="breadcrumb-item active">Evaluar</li>
        </ol></nav>
    </div>
</div>

<form action="<?= e(APP_URL) ?>/proveedores/<?= (int)$item['id'] ?>/evaluar" method="POST">
    <?= csrfField() ?>
<div class="row g-4">
<div class="col-lg-7">
<div class="card mb-4">
    <div class="card-header">Datos de la Evaluación</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Proveedor</label>
                <input type="text" class="form-control bg-light" value="<?= e($item['razon_social']) ?>" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de Evaluación</label>
                <input type="date" class="form-control" name="fecha_evaluacion" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label">Objeto del Contrato</label>
            <textarea class="form-control" name="objeto_contrato" rows="2"></textarea>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Criterios de Evaluación <small class="text-muted">(califique cada uno de 0.0 a 5.0)</small></div>
    <div class="card-body">
        <?php
        $criterios = [
            'cumplimiento_entrega'     => 'Cumplimiento y Entrega',
            'calidad_especificaciones' => 'Calidad y Cumplimiento de Especificaciones Técnicas',
            'documentacion_garantias'  => 'Documentación y Garantías',
            'servicio_postventa'       => 'Servicio Postventa',
            'precio'                   => 'Precio',
            'capacidad_instalada'      => 'Capacidad Instalada',
            'soporte_tecnico'          => 'Soporte Técnico',
        ];
        ?>
        <?php foreach ($criterios as $campo => $label): ?>
        <div class="row g-2 align-items-center mb-2">
            <div class="col-md-8"><?= $label ?></div>
            <div class="col-md-4">
                <input type="number" step="0.1" min="0" max="5" class="form-control form-control-sm criterio-input"
                       name="<?= $campo ?>" required oninput="calcularPromedio()">
            </div>
        </div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <strong>Promedio Estimado</strong>
            <span class="badge" id="badgePromedio" style="font-size:14px;">—</span>
        </div>
        <small class="text-muted">El cálculo final y autoritativo lo hace el servidor al guardar.</small>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Observaciones</div>
    <div class="card-body">
        <textarea class="form-control" name="observaciones" rows="3"></textarea>
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/proveedores/ver/<?= (int)$item['id'] ?>" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar Evaluación</button>
</div>
</div>

<div class="col-lg-5">
<div class="card mb-4">
    <div class="card-header">Guía de Calificación</div>
    <div class="card-body" style="font-size:12px;">
        <table class="table table-sm">
            <tr><td><span class="badge bg-success">4.5 – 5.0</span></td><td>Excelente</td></tr>
            <tr><td><span class="badge bg-primary">3.9 – 4.4</span></td><td>Bueno</td></tr>
            <tr><td><span class="badge bg-warning">3.0 – 3.8</span></td><td>Regular</td></tr>
            <tr><td><span class="badge bg-danger">0.0 – 2.9</span></td><td>No Confiable</td></tr>
        </table>
        <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Si el promedio resulta en "No Confiable", el proveedor se marcará automáticamente como
            <strong>RESTRINGIDO</strong> al guardar esta evaluación.
        </div>
    </div>
</div>
</div>
</div>
</form>

<script>
function calcularPromedio() {
    var inputs = document.querySelectorAll('.criterio-input');
    var valores = [];
    inputs.forEach(function(inp) {
        var val = parseFloat(inp.value);
        if (!isNaN(val)) valores.push(val);
    });
    var badge = document.getElementById('badgePromedio');
    if (valores.length < inputs.length) {
        badge.textContent = '—';
        badge.className = 'badge bg-secondary';
        return;
    }
    var promedio = valores.reduce(function(a,b){return a+b;}, 0) / valores.length;
    promedio = Math.round(promedio * 10) / 10;
    var color = promedio >= 4.5 ? 'bg-success' : promedio >= 3.9 ? 'bg-primary' : promedio >= 3.0 ? 'bg-warning' : 'bg-danger';
    badge.textContent = promedio.toFixed(1);
    badge.className = 'badge ' + color;
}
</script>
