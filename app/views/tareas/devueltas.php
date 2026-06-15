<div class="page-header">
    <div><h2><i class="bi bi-arrow-return-left me-2"></i><?= e($pageTitle) ?></h2></div>
</div>

<!-- KPIs resumen de mis tareas -->
<?php if (!empty($resumen)):
$tiposKpi = [
    'CREADO'     => ['kpi-blue',  'bi-pencil-square',     'Creadas'],
    'REVISION'   => ['kpi-amber', 'bi-eye',               'En Revisión'],
    'APROBACION' => ['kpi-teal',  'bi-check2-square',     'En Aprobación'],
    'DEVUELTO'   => ['kpi-rose',  'bi-arrow-return-left', 'Devueltas'],
    'FINALIZADO' => ['kpi-green', 'bi-check2-circle',     'Finalizadas'],
];
$kpis = [];
foreach ($tiposKpi as $est => [$tipo, $icono, $lbl]) {
    if ($resumen[$est] ?? 0)
        $kpis[] = ['label'=>$lbl,'valor'=>$resumen[$est],'icono'=>$icono,'tipo'=>$tipo,'filtro'=>$est];
}
if (!empty($kpis)) {
    $kpiTotal = ['label'=>'Mis Tareas','valor'=>array_sum($resumen)];
    include APP_ROOT . '/app/views/partials/kpi_cards.php';
}
endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($tareas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
            No tienes tareas devueltas pendientes.
        </div>
        <?php else: ?>
        <table class="table table-hover datatable" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo Documento</th>
                    <th>Prioridad</th>
                    <th>Solicitante</th>
                    <th title="Rol que realizó la devolución de la tarea">
                        Devuelta por
                        <i class="bi bi-question-circle text-muted ms-1"
                           style="font-size:11px;cursor:help;"
                           title="Indica qué rol devolvió esta tarea para corrección: Revisor o Aprobador"></i>
                    </th>
                    <th>Motivo / Comentario</th>
                    <th>Fecha</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tareas as $t): ?>
            <tr class="table-warning">
                <td>
                    <strong class="text-primary"><?= (int)$t['id_tarea'] ?></strong>
                    <br><small class="text-muted" style="font-size:10px;">Sol.<?= (int)$t['id_solicitud'] ?></small>
                </td>
                <td style="font-size:12px;">
                    
                    <span class="badge bg-secondary" style="font-size:10px;">
                        <?= e($t['tipo_documento'] ?? '') ?>
                    </span>
                </td>
                <td><?= prioridadLabel($t['prioridad'] ?? '') ?></td>
                <td style="font-size:12px;"><?= e($t['solicitante'] ?? '—') ?></td>
                <td>
                    <?php
                    // CA-1: el rol que DEVOLVIÓ es el anterior al actual en el flujo
                    // elaborador → revisor devuelve | revisor → aprobador devuelve
                    $estadoActual = strtoupper($t['estado_actual'] ?? 'CREADO');
                    // Si está DEVUELTO, quien devolvió es el rol siguiente al actual
                    $quienDevolvio = match($estadoActual) {
                        'DEVUELTO','CREADO' => $t['ultimo_devolutor'] ?? 'REVISOR',
                        default             => $t['rol_asignacion'] ?? '—',
                    };
                    $quienDevolvio = strtoupper($quienDevolvio ?: 'REVISOR');
                    $badgeDevol = match($quienDevolvio) {
                        'REVISOR','REVISION'    => ['bg-warning text-dark', 'bi-eye',        'Revisor'],
                        'APROBADOR','APROBACION'=> ['bg-danger',            'bi-check2-all', 'Aprobador'],
                        default                 => ['bg-secondary',         'bi-person',     $quienDevolvio],
                    };
                    ?>
                    <?php if ($quienDevolvio && $quienDevolvio !== '—'): ?>
                    <span class="badge <?= $badgeDevol[0] ?>" style="font-size:10px;"
                          title="Esta tarea fue devuelta por el <?= $badgeDevol[2] ?>">
                        <i class="bi <?= $badgeDevol[1] ?> me-1"></i><?= $badgeDevol[2] ?>
                    </span>
                    <?php else: ?>
                    <!-- CA-2: valor por defecto explicativo -->
                    <span class="text-muted fst-italic" style="font-size:11px;"
                          title="No hay registro del rol que devolvió">Sin datos</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px; max-width:200px;">
                    <?php
                    // Buscar el comentario del último estado DEVUELTO
                    $comentario = $t['descripcion'] ?? '';
                    if (!empty($comentario)):
                    ?>
                    <span title="<?= e($comentario) ?>"
                          style="display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;cursor:default;">
                        <i class="bi bi-chat-left-text me-1 text-warning"></i><?= e($comentario) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">Sin comentario</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px; color:#6b7280;">
                    <?= !empty($t['fecha_estado'])
                        ? date('d/m/Y H:i', strtotime($t['fecha_estado']))
                        : '—' ?>
                </td>
                <td class="text-center">
                    <a href="<?= e(APP_URL . ($t['url_accion'] ?? '/tareas/elaborar/' . $t['id_tarea'])) ?>"
                       class="btn btn-sm btn-warning py-0">
                        <i class="bi bi-arrow-right me-1"></i>Corregir
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
