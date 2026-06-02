<div class="page-header">
    <div><h2><i class="bi bi-layers me-2"></i>Versionamiento de Documentos</h2></div>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre del Documento</th>
                    <th>Proceso</th>
                    <th>Tipo</th>
                    <th>Versión Actual</th>
                    <th>Estado</th>
                    <th>Última Aprobación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($versiones)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No hay documentos con versiones.</td></tr>
                <?php else: ?>
                <?php foreach ($versiones as $v): ?>
                <tr>
                    <td><code><?= e($v['codigo'] ?? '') ?></code></td>
                    <td><?= e(truncar($v['nombre_documento'] ?? '', 55)) ?></td>
                    <td style="font-size:12px;"><?= e($v['proceso'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= e($v['sigla_tipo'] ?? $v['tipo_documento'] ?? '') ?></span></td>
                    <td class="text-center">
                        <span class="badge bg-primary">V<?= e($v['max_version'] ?? 0) ?></span>
                    </td>
                    <td><?= badgeEstado($v['estado_version'] ?? 'CREADO') ?></td>
                    <td style="font-size:12px;"><?= fechaEs($v['fecha_aprobacion'] ?? null) ?></td>
                    <td>
                        <a href="<?= e(APP_URL) ?>/versionamiento/documento/<?= (int)$v['id_documento'] ?>"
                           class="btn btn-sm btn-outline-info py-0" title="Ver historial">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        <?php if (Auth::puede('versionamiento', 'crear')): ?>
                        <a href="<?= e(APP_URL) ?>/versionamiento/nueva/<?= (int)$v['id_documento'] ?>"
                           class="btn btn-sm btn-lim-primary py-0" title="Nueva versión">
                            <i class="bi bi-plus-circle"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
