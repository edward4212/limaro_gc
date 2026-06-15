<div class="page-header d-print-none">
    <div><h2><i class="bi bi-shield-lock me-2"></i><?= e($pageTitle) ?></h2></div>
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
                    <th class="text-center">Estado</th>
                    <th>Fecha Registro</th>
                    <th>Último Cambio Contraseña</th>
                    <th class="text-center">Días sin cambiar</th>
                    <th class="text-center">Requiere Reset</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $u):
                    $ultimoCambio = $u['ultimo_cambio'] ?? null;
                    $diasSin = $ultimoCambio
                        ? (int)((time() - strtotime($ultimoCambio)) / 86400)
                        : null;
                    $colorDias = $diasSin === null ? 'secondary'
                               : ($diasSin > 90 ? 'danger' : ($diasSin > 30 ? 'warning' : 'success'));
                ?>
                <tr>
                    <td><?= e($u['nombre_completo']) ?></td>
                    <td><span style="font-size:12px;"><?= e($u['usuario']) ?></span></td>
                    <td class="text-center"><?= badgeEstado($u['estado']) ?></td>
                    <td style="font-size:12px;"><?= $u['fecha_registro'] ? fechaEs($u['fecha_registro']) : '—' ?></td>
                    <td style="font-size:12px;"><?= $ultimoCambio ? fechaEs($ultimoCambio) : '<em class="text-muted">Sin cambios</em>' ?></td>
                    <td class="text-center">
                        <?php if ($diasSin !== null): ?>
                        <span class="badge bg-<?= $colorDias ?>"><?= $diasSin ?> d</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?= ($u['requiere_reset'] ?? 0) ? '<span class="badge bg-warning text-dark">Sí</span>' : '<span class="badge bg-success">No</span>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted d-print-none" style="font-size:12px;">
        Semáforo días sin cambiar:
        <span class="badge bg-success ms-1">≤30 d</span> Reciente ·
        <span class="badge bg-warning ms-1">31–90 d</span> Moderado ·
        <span class="badge bg-danger ms-1">+90 d</span> Requiere atención
    </div>
</div>
