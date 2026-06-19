<?php
$colorEstado = ['ACTIVO'=>'success','INACTIVO'=>'secondary','RESTRINGIDO'=>'danger'];
$colorResultado = ['EXCELENTE'=>'success','BUENO'=>'primary','REGULAR'=>'warning','NO_CONFIABLE'=>'danger'];
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-truck me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/proveedores">Gestión de Proveedores</a></li>
            <li class="breadcrumb-item active"><?= e($item['codigo']) ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <?php if ($item['estado'] !== 'RESTRINGIDO' && Auth::puede('proveedores_registro','crear')): ?>
        <a href="<?= e(APP_URL) ?>/proveedores/<?= (int)$item['id'] ?>/evaluar" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clipboard-check me-1"></i>Evaluar
        </a>
        <?php endif; ?>
        <?php if (Auth::puede('proveedores_registro','editar')): ?>
        <a href="<?= e(APP_URL) ?>/proveedores/editar/<?= (int)$item['id'] ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($item['estado'] === 'RESTRINGIDO'): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>Este proveedor está <strong>RESTRINGIDO</strong> por resultado de evaluación NO CONFIABLE.
        Solo Administrador o Coordinador de Calidad pueden cambiar este estado desde Editar.</div>
</div>
<?php endif; ?>

<div class="row g-4">
<div class="col-lg-7">
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Información General</span>
        <span class="badge bg-<?= $colorEstado[$item['estado']] ?? 'secondary' ?>"><?= e($item['estado']) ?></span>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:180px;">Tipo</td><td><?= e($item['tipo_vinculo']) ?> — <?= e($item['tipo_persona']) ?></td></tr>
            <tr><td class="text-muted">Documento</td><td><?= e($item['tipo_documento'] ?? '') ?> <?= e($item['numero_documento'] ?? '—') ?></td></tr>
            <tr><td class="text-muted">Dirección</td><td><?= e($item['direccion'] ?? '—') ?> <?= e($item['ciudad'] ?? '') ?></td></tr>
            <tr><td class="text-muted">Teléfono / Correo</td><td><?= e($item['telefono'] ?? '—') ?> · <?= e($item['correo'] ?? '—') ?></td></tr>
            <tr><td class="text-muted">Servicio Prestado</td><td><?= nl2br(e($item['servicio_prestado'] ?? '—')) ?></td></tr>
            <tr><td class="text-muted">Contacto</td><td><?= e($item['contacto_nombre'] ?? '—') ?> <?= !empty($item['contacto_cargo']) ? '('.e($item['contacto_cargo']).')' : '' ?></td></tr>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Historial de Evaluaciones</div>
    <div class="card-body">
        <?php if (empty($evaluaciones)): ?>
        <p class="text-muted mb-0">Este proveedor no tiene evaluaciones registradas todavía.</p>
        <?php else: ?>
        <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Promedio</th><th>Resultado</th><th>Evaluador</th></tr></thead>
            <tbody>
            <?php foreach ($evaluaciones as $ev): ?>
            <tr>
                <td><?= fechaEs($ev['fecha_evaluacion']) ?></td>
                <td><?= e($ev['promedio']) ?></td>
                <td><span class="badge bg-<?= $colorResultado[$ev['resultado']] ?? 'secondary' ?>"><?= str_replace('_',' ',$ev['resultado']) ?></span></td>
                <td><?= e($ev['evaluador_nombre'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</div>

<div class="col-lg-5">
<div class="card mb-4">
    <div class="card-header">Persona Públicamente Expuesta (PEP)</div>
    <div class="card-body">
        <?php
        $pepFlags = [
            'pep_administra_recursos_publicos' => 'Administra recursos públicos',
            'pep_ejerce_poder_publico'         => 'Ejerce poder público',
            'pep_reconocimiento_publico'       => 'Reconocimiento público',
            'pep_vinculo_familiar'             => 'Vínculo familiar con PEP',
        ];
        $algunPep = false;
        foreach ($pepFlags as $campo => $label) { if (!empty($item[$campo])) $algunPep = true; }
        ?>
        <?php if (!$algunPep): ?>
        <p class="text-success small mb-0"><i class="bi bi-check-circle me-1"></i>Sin alertas PEP registradas.</p>
        <?php else: ?>
        <ul class="small mb-0">
            <?php foreach ($pepFlags as $campo => $label): if (!empty($item[$campo])): ?>
            <li class="text-danger"><?= $label ?></li>
            <?php endif; endforeach; ?>
        </ul>
        <?php if (!empty($item['pep_especificar'])): ?>
        <p class="small mt-2 mb-0"><?= nl2br(e($item['pep_especificar'])) ?></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Verificación Interna</div>
    <div class="card-body">
        <?php if (!empty($item['id_usuario_verifico'])): ?>
        <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:120px;">Verificado por</td><td><?= e($item['usuario_verifico_nombre'] ?? '—') ?></td></tr>
            <tr><td class="text-muted">Fecha</td><td><?= fechaEs($item['fecha_verificacion']) ?></td></tr>
            <?php if (!empty($item['resultado_verificacion'])): ?>
            <tr><td class="text-muted">Resultado</td><td><?= nl2br(e($item['resultado_verificacion'])) ?></td></tr>
            <?php endif; ?>
        </table>
        <?php else: ?>
        <p class="text-muted small mb-0">Este proveedor todavía no ha sido verificado por ningún funcionario.</p>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
