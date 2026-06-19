<div class="page-header">
    <div>
        <h2><i class="bi bi-person-check me-2"></i>Perfil de Competencias</h2>
        <small class="text-muted">ISO 9001:2015 — Cláusula 7.2 / 7.3</small>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted small mb-3">
            Perfil de competencias requeridas por cargo, según el Manual de Funciones (TH-FO-21) de cada uno.
        </p>
        <table class="table table-hover datatable" style="width:100%;">
            <thead>
                <tr><th>Cargo</th><th class="text-center">Empleados Activos</th><th class="text-center">Perfil Definido</th><th class="text-center">Última Actualización</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($cargos as $c): ?>
                <tr>
                    <td><?= e($c['cargo']) ?></td>
                    <td class="text-center"><?= (int)$c['total_empleados'] ?></td>
                    <td class="text-center">
                        <?php if ($c['id_competencia']): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Definido</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $c['fecha_actualizacion'] ? fechaEs($c['fecha_actualizacion']) : '—' ?></td>
                    <td>
                        <a href="<?= e(APP_URL) ?>/competencia/cargo/<?= (int)$c['id_cargo'] ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px;">
                            <i class="bi bi-pencil me-1"></i><?= $c['id_competencia'] ? 'Editar' : 'Definir' ?>
                        </a>
                        <a href="<?= e(APP_URL) ?>/archivo/<?= (int)$c['id_archivo'] ?>?inline=1" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:11px;" title="Ver manual de funciones completo">
                            <i class="bi bi-file-earmark-text"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($cargos)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Ningún cargo tiene manual de funciones cargado todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
