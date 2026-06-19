<?php
$soloConsulta = \App\Core\Request::get('consulta') === '1';
?>
<?php include APP_ROOT . '/app/views/partials/modal_confirm.php'; ?>

<div class="page-header">
    <div>
        <h2><i class="bi bi-layers me-2"></i><?= e($pageTitle) ?></h2>
        <!--<?php if ($soloConsulta): ?>-->
        <!--<span class="badge bg-secondary ms-2"><i class="bi bi-eye me-1"></i>Solo consulta</span>-->
        <!--<?php endif; ?>-->
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= e(APP_URL) ?>/versionamiento">Versionamiento</a>
            </li>
            <li class="breadcrumb-item active">
                <?= e($documento['codigo'] ?? $documento['codigo_documento'] ?? '') ?>
            </li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$soloConsulta && Auth::puede('versionamiento', 'crear')): ?>
        <a href="<?= e(APP_URL) ?>/versionamiento/nueva/<?= (int)$documento['id_documento'] ?>"
           class="btn btn-lim-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nueva Versión
        </a>
        <?php endif; ?>
        <?php
        // CA-1: ocultar Descargar Carpeta cuando viene de Documentos Registrados
        $fromParam = \App\Core\Request::get('from', 'versionamiento');
        $esDesdeDocumentos = in_array($fromParam, ['documentos', 'empresa/documentos']);
        ?>
        <?php if (!$esDesdeDocumentos): ?>
        <!--<a href="<?= e(APP_URL) ?>/versionamiento/descargar/<?= (int)$documento['id_documento'] ?>"-->
        <!--   class="btn btn-success btn-sm">-->
        <!--    <i class="bi bi-file-zip me-1"></i>Descargar carpeta-->
        <!--</a>-->
        <?php endif; ?>
        <a href="<?= e(APP_URL) ?>/<?= e($fromParam) ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Info documento -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2">
            <div class="col-md-2">
                <div class="text-muted" style="font-size:11px;">CÓDIGO</div>
                <code class="fs-6"><?= e($documento['codigo'] ?? $documento['codigo_documento'] ?? '') ?></span>
                <!--<?php if (!empty($documento['codigo_anterior'])): ?>-->
                <!--<div class="form-text">Antes: <del><?= e($documento['codigo_anterior']) ?></del></div>-->
                <!--<?php endif; ?>-->
            </div>
            <div class="col-md-5">
                <div class="text-muted" style="font-size:11px;">DOCUMENTO</div>
                <strong><?= e($documento['nombre_documento']) ?></strong>
            </div>
            <div class="col-md-3">
                <div class="text-muted" style="font-size:11px;">PROCESO</div>
                <?= e($documento['proceso'] ?? '—') ?>
            </div>
            <div class="col-md-2">
                <div class="text-muted" style="font-size:11px;">ESTADO</div>
                <?= badgeEstado($documento['estado'] ?? 'ACTIVO') ?>
            </div>
        </div>
    </div>
</div>

<!-- Timeline de versiones -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Historial de Versiones</span>
        <span class="badge bg-secondary"><?= count($versiones) ?> versión(es)</span>
    </div>
    <div class="card-body">
        <?php if (empty($versiones)): ?>
        <p class="text-muted text-center py-3">Sin versiones registradas.</p>
        <?php else: ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle" style="width:100%;">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        <th>Elaboró</th>
                        <th>Revisó</th>
                        <th>Aprobó</th>
                        <th>F. Aprobación</th>
                        <th>F. Obsoleto</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($versiones as $v): ?>
                <tr class="<?= ($v['estado_version'] ?? '') === 'VIGENTE' ? 'table-success' : (($v['estado_version'] ?? '') === 'OBSOLETO' ? 'table-secondary' : '') ?>">
                    <td class="text-center">
                        <span class="badge bg-primary fs-6">V<?= e($v['numero_version']) ?></span>
                    </td>
                    <td><?= badgeEstado($v['estado_version']) ?></td>
                    <td style="max-width:200px; white-space:normal; font-size:12px;">
                        <?= e($v['descripcion_version'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px;"><?= e($v['elaborador'] ?? $v['usuario_creacion'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($v['revisor']    ?? $v['usuario_revision'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= e($v['aprobador']  ?? $v['usuario_aprobacion'] ?? '—') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($v['fecha_aprobacion'] ?? null) ?></td>
                    <td style="font-size:12px;"><?= fechaEs($v['fecha_obsoleto']   ?? null) ?></td>
                    <td>
                        <?php
                        $rutaArch = $v['documento'] ?? $v['archivo'] ?? null;
                        $idArch   = $v['id_archivo'] ?? null;
                        if ($idArch): ?>
                            <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$idArch ?>"
                               class="btn btn-outline-primary btn-sm py-0" title="Descargar">
                                <i class="bi bi-download"></i></a>
                        <?php elseif ($rutaArch): ?>
                            <a href="<?= e(APP_URL) ?>/archivo/v/<?= (int)$v['id_versionamiento'] ?>"
                               class="btn btn-outline-primary btn-sm py-0" title="Descargar">
                                <i class="bi bi-download"></i></a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Cambiar estado -->
                        <?php if (!$soloConsulta && Auth::puede('versionamiento', 'editar')): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary py-0 dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><h6 class="dropdown-header">Cambiar estado</h6></li>
                                <?php foreach (['VIGENTE','OBSOLETO','CREADO'] as $est):
                                    if ($est === ($v['estado_version'] ?? '')) continue; ?>
                                <li>
                                    <form action="<?= e(APP_URL) ?>/versionamiento/cambiar-estado/<?= (int)$v['id_versionamiento'] ?>"
                                          method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="estado_version" value="<?= $est ?>">
                                        <input type="hidden" name="id_documento"   value="<?= (int)$documento['id_documento'] ?>">
                                        <button type="submit" class="dropdown-item
                                            <?= $est === 'VIGENTE' ? 'text-success' : ($est === 'OBSOLETO' ? 'text-danger' : '') ?>"
                                            onclick="swalConfirm(event, '¿Cambiar estado a <?= $est ?>?' + (<?= json_encode($est === 'VIGENTE') ?> ? ' Las demás versiones quedarán OBSOLETO.' : ''))">
                                            <?= $est ?>
                                        </button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>
    </div>
</div>
