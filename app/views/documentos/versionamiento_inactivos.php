<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-archive me-2"></i>Documentos Inactivos / Obsoletos</h2>
        <small class="text-muted">HU-020 — Documentos con estado INACTIVO u OBSOLETO</small>
    </div>
    <a href="<?= e(APP_URL) ?>/versionamiento" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<?php
$nDocs = count($documentos);
$kpis = [
    ['label'=>'Inactivos', 'valor'=>count(array_filter($documentos, fn($d)=>($d['estado_documento']??'') === 'INACTIVO')), 'icono'=>'bi-slash-circle', 'tipo'=>'kpi-rose'],
    ['label'=>'Obsoletos', 'valor'=>count(array_filter($documentos, fn($d)=>($d['estado_version']??'') === 'OBSOLETO')), 'icono'=>'bi-archive', 'tipo'=>'kpi-amber'],
];
$kpiTotal = ['label'=>'Total', 'valor'=>$nDocs];
include APP_ROOT . '/app/views/partials/kpi_cards.php';
?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm datatable datatable-export mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Proceso</th>
                    <th>Tipo</th>
                    <th class="text-center">Ver.</th>
                    <th class="text-center">Estado Doc.</th>
                    <th class="text-center">Estado Ver.</th>
                    <th>Aprobó</th>
                    <?php if (Auth::puede('versionamiento','editar')): ?>
                    <th class="text-center">Reactivar</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($documentos as $d): ?>
            <tr>
                <td><code style="font-size:11px;"><?= e($d['codigo']) ?></code></td>
                <td style="font-size:12px;"><?= e(truncar($d['nombre_documento'],55)) ?></td>
                <td style="font-size:11px;"><?= e($d['proceso'] ?? '—') ?></td>
                <td style="font-size:11px;"><?= e($d['tipo_documento'] ?? '—') ?></td>
                <td class="text-center">
                    <span class="badge bg-secondary">v<?= $d['numero_version'] ?? '—' ?></span>
                </td>
                <td class="text-center">
                    <span class="badge <?= ($d['estado_documento']??'') === 'INACTIVO' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                        <?= $d['estado_documento'] ?? 'ACTIVO' ?>
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary" style="font-size:10px;">
                        <?= $d['estado_version'] ?? '—' ?>
                    </span>
                </td>
                <td style="font-size:11px;"><?= e($d['aprobador'] ?? '—') ?></td>
                <?php if (Auth::puede('versionamiento','editar')): ?>
                <td class="text-center">
                    <!-- CA-2: reactivar con confirmación -->
                    <button class="btn btn-sm btn-outline-success py-0 px-2"
                            onclick="setModalConfirm(
                                '<?= e(APP_URL) ?>/versionamiento/reactivar/<?= (int)$d['id_documento'] ?>',
                                '¿Reactivar el documento «<?= e(addslashes($d['nombre_documento'])) ?>»?'
                            )" title="Reactivar documento">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($documentos)): ?>
            <tr><td colspan="9" class="text-center py-4 text-muted">
                <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                No hay documentos inactivos ni obsoletos.
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
