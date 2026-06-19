<div class="page-header">
    <div>
        <h2><i class="bi bi-bar-chart me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/proveedores">Gestión de Proveedores</a></li>
            <li class="breadcrumb-item active">Comparativo</li>
        </ol></nav>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($items)): ?>
        <p class="text-muted text-center py-4 mb-0">Todavía no hay proveedores con evaluaciones registradas.</p>
        <?php else: ?>
        <table class="table table-hover table-sm datatable datatable-export" style="width:100%;">
            <thead>
                <tr>
                    <th>Código</th><th>Proveedor</th>
                    <th class="text-center">Cumpl. y Entrega</th>
                    <th class="text-center">Calidad</th>
                    <th class="text-center">Document.</th>
                    <th class="text-center">Postventa</th>
                    <th class="text-center">Precio</th>
                    <th class="text-center">Capacidad</th>
                    <th class="text-center">Soporte</th>
                    <th class="text-center">Promedio</th>
                    <th class="text-center">Resultado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $colorResultado = ['EXCELENTE'=>'success','BUENO'=>'primary','REGULAR'=>'warning','NO_CONFIABLE'=>'danger'];
                ?>
                <?php foreach ($items as $p): ?>
                <tr>
                    <td><strong><?= e($p['codigo']) ?></strong></td>
                    <td><?= e($p['razon_social']) ?></td>
                    <td class="text-center"><?= e($p['cumplimiento_entrega']) ?></td>
                    <td class="text-center"><?= e($p['calidad_especificaciones']) ?></td>
                    <td class="text-center"><?= e($p['documentacion_garantias']) ?></td>
                    <td class="text-center"><?= e($p['servicio_postventa']) ?></td>
                    <td class="text-center"><?= e($p['precio']) ?></td>
                    <td class="text-center"><?= e($p['capacidad_instalada']) ?></td>
                    <td class="text-center"><?= e($p['soporte_tecnico']) ?></td>
                    <td class="text-center"><strong><?= e($p['promedio']) ?></strong></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $colorResultado[$p['resultado']] ?? 'secondary' ?>">
                            <?= str_replace('_',' ',$p['resultado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <small class="text-muted">Se muestra la evaluación más reciente de cada proveedor, ordenadas de mayor a menor promedio.</small>
        <?php endif; ?>
    </div>
</div>
