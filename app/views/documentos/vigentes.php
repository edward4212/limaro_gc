<div class="page-header">
    <div><h2><i class="bi bi-list-check me-2"></i>Listado Maestro — Documentos Vigentes</h2></div>
    <div class="d-flex gap-2">
        <a href="<?= e(APP_URL) ?>/documentos/vigentes/descargar-zip"
           class="btn btn-success btn-sm" title="Descargar todos como ZIP">
            <i class="bi bi-file-zip me-1"></i>Descargar Todo
        </a>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover datatable datatable-export table-sm" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre del Documento</th>
                    <th>Tipo</th>
                    <th>Proceso</th>
                    <th>Versión</th>
                    <th>Fecha Aprobación</th>
                    <th>Elaboró</th>
                    <th>Revisó</th>
                    <th>Aprobó</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">No hay documentos vigentes.</td></tr>
                <?php else: ?>
                <?php foreach ($documentos as $d): ?>
                <tr>
                    <td><code><?= e($d['codigo_documento']) ?></code></td>
                    <td><?= e($d['nombre_documento']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($d['tipo_documento']) ?></span></td>
                    <td><?= e($d['proceso']) ?></td>
                    <td><span class="badge bg-success">v<?= e($d['numero_version']) ?></span></td>
                    <td><?= fechaEs($d['fecha_aprobacion']) ?></td>
                    <td><?= e($d['elaborador'] ?? '—') ?></td>
                    <td><?= e($d['revisor'] ?? '—') ?></td>
                    <td><?= e($d['aprobador'] ?? '—') ?></td>
                    <td>
                        <?php
                        $dlId   = $d['id_archivo'] ?? null;
                        $dlRuta = $d['archivo_ruta'] ?? $d['documento'] ?? null;
                        if ($dlId): ?>
                            <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$dlId ?>"
                               class="btn btn-sm btn-outline-primary py-0" title="Descargar">
                                <i class="bi bi-download"></i></a>
                        <?php elseif ($dlRuta): ?>
                            <a href="<?= e(APP_URL) ?>/public<?= e($dlRuta) ?>"
                               target="_blank"
                               class="btn btn-sm btn-outline-primary py-0" title="Descargar">
                                <i class="bi bi-download"></i></a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
