<div class="page-header d-print-none">
    <div><h2><i class="bi bi-people me-2"></i><?= e($pageTitle) ?></h2></div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong><?= e($pageTitle) ?></strong>
        <span class="badge bg-primary"><?= count($datos) ?> usuario(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm datatable datatable-export mb-0" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Roles</th>
                    <th class="text-center">Estado</th>
                    <th>Fecha Activación</th>
                    <th>Fecha Vencimiento</th>
                    <th>Último Acceso</th>
                    <th>Último Cambio Clave</th>
                    <th class="text-center">Intentos Fall.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $u):
                    $vence = $u['fecha_vencimiento'] ?? null;
                    $vencido = $vence && $vence < date('Y-m-d');
                ?>
                <tr>
                    <td><?= e($u['nombre_completo']) ?></td>
                    <td><span style="font-size:12px;"><?= e($u['usuario']) ?></span></td>
                    <td style="font-size:12px;"><?= e($u['roles'] ?? '—') ?></td>
                    <td class="text-center"><?= badgeEstado($u['estado']) ?></td>
                    <td style="font-size:12px;"><?= $u['fecha_activacion'] ? fechaEs($u['fecha_activacion']) : '—' ?></td>
                    <td style="font-size:12px;">
                        <?php if ($vence): ?>
                        <span class="<?= $vencido ? 'text-danger fw-bold' : '' ?>">
                            <?= fechaEs($vence) ?>
                            <?= $vencido ? '<i class="bi bi-exclamation-triangle ms-1"></i>' : '' ?>
                        </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="font-size:12px;"><?= $u['ultimo_login'] ? fechaEs($u['ultimo_login'], 'hora') : '<em class="text-muted">Nunca</em>' ?></td>
                    <td style="font-size:12px;"><?= $u['fecha_cambio_clave'] ? fechaEs($u['fecha_cambio_clave']) : '—' ?></td>
                    <td class="text-center">
                        <?php $i = (int)($u['intentos_fallidos'] ?? 0); ?>
                        <span class="badge bg-<?= $i >= 3 ? 'danger' : ($i > 0 ? 'warning' : 'success') ?>"><?= $i ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted d-print-none" style="font-size:12px;">
        <i class="bi bi-exclamation-triangle text-danger me-1"></i>Fecha vencimiento en rojo = cuenta vencida.
        <span class="ms-3"><span class="badge bg-danger">3+</span> intentos fallidos = cuenta bloqueada.</span>
    </div>
</div>
