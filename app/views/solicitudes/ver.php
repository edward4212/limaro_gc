<div class="page-header">
    <div>
        <h2><i class="bi bi-clipboard-check me-2"></i>Solicitud #<?= e($sol['id_solicitud']) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/solicitudes/mis-radicadas">Solicitudes</a></li>
            <li class="breadcrumb-item active">#<?= e($sol['id_solicitud']) ?></li>
        </ol></nav>
    </div>
    <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-3">
    <!-- Detalle de la solicitud -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Información de la Solicitud</div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Tipo Solicitud:</div>
                    <div class="col-sm-8"><?= e($sol['tipo_solicitud']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Tipo Documento:</div>
                    <div class="col-sm-8"><?= e($sol['tipo_documento']) ?></div>
                </div>
                <?php if ($sol['nombre_documento'] ?? null): ?>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Documento:</div>
                    <div class="col-sm-8"><code><?= e($sol['codigo_documento']) ?></code> — <?= e($sol['nombre_documento']) ?></div>
                </div>
                <?php endif; ?>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Prioridad:</div>
                    <div class="col-sm-8"><?= prioridadLabel($sol['prioridad'] ?? '') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Estado:</div>
                    <div class="col-sm-8"><?= badgeEstado($sol['estado_solicitud']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Solicitante:</div>
                    <div class="col-sm-8"><?= e($sol['solicitante']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Asignado a:</div>
                    <div class="col-sm-8"><?= e($sol['asignado_a'] ?? '—') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Fecha Solicitud:</div>
                    <div class="col-sm-8"><?= fechaEs($sol['fecha_solicitud'], 'largo') ?></div>
                </div>
                <?php if ($sol['fecha_solucion'] ?? null): ?>
                <div class="row mb-2">
                    <div class="col-sm-4 fw-bold text-muted">Fecha Solución:</div>
                    <div class="col-sm-8"><?= fechaEs($sol['fecha_solucion'], 'largo') ?></div>
                </div>
                <?php endif; ?>
                <?php if ($sol['descripcion'] ?? null): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="fw-bold text-muted d-block mb-1">Descripción:</label>
                        <p class="border rounded p-2 bg-light"><?= nl2br(e($sol['descripcion'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Archivos adjuntos -->
        <?php if (!empty($sol['archivos'])): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-paperclip me-2"></i>Archivos Adjuntos</div>
            <div class="card-body">
                <?php foreach ($sol['archivos'] as $ar): ?>
                <a href="<?= e(APP_URL) ?>/archivo/<?= $ar['id_archivo'] ?>"
                   class="btn btn-outline-primary btn-sm me-2 mb-2">
                    <i class="bi bi-download me-1"></i><?= e($ar['nombre_original']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial de asignaciones -->
        <?php if (!empty($sol['asignaciones'])): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-people me-2"></i>Asignaciones</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Empleado</th><th>Rol</th><th>Asignado Por</th><th>Fecha</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach ($sol['asignaciones'] as $a): ?>
                        <tr>
                            <td><?= e($a['nombre_completo']) ?></td>
                            <td><?= e($a['rol_asignacion']) ?></td>
                            <td><?= e($a['asignado_por']) ?></td>
                            <td><?= fechaEs($a['fecha_asignacion']) ?></td>
                            <td><?= badgeEstado($a['estado']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Comentarios -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-text me-2"></i>Comentarios</div>
            <div class="card-body" style="max-height:350px;overflow-y:auto;">
                <?php if (empty($sol['comentarios'])): ?>
                <p class="text-muted text-center small">Sin comentarios.</p>
                <?php else: ?>
                <?php foreach ($sol['comentarios'] as $c): ?>
                <div class="d-flex mb-3">
                    <div class="flex-shrink-0 me-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;font-size:12px;">
                            <?= strtoupper(substr($c['nombre_completo'] ?? 'U', 0, 1)) ?>
                        </div>
                    </div>
                    <div>
                        <div class="bg-light rounded p-2">
                            <strong style="font-size:12px;"><?= e($c['nombre_completo'] ?? '—') ?></strong>
                            <small class="text-muted ms-2"><?= fechaEs($c['fecha_comentario'], 'hora') ?></small>
                            <p class="mb-0 mt-1" style="font-size:13px;"><?= nl2br(e($c['comentario'])) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <form action="<?= e(APP_URL) ?>/solicitudes/comentar/<?= $sol['id_solicitud'] ?>" method="POST">
                    <?= csrfField() ?>
                    <div class="mb-2">
                        <textarea class="form-control form-control-sm" name="comentario"
                                  rows="2" placeholder="Agregar comentario..."></textarea>
                    </div>
                    <button class="btn btn-lim-primary btn-sm w-100">
                        <i class="bi bi-send me-1"></i>Comentar
                    </button>
                </form>
            </div>
        </div>

        <!-- Asignar si es CREADA y tiene permisos -->
        <?php if ($sol['estado_solicitud'] === 'CREADA' && Auth::puede('sol_radicadas', 'editar')): ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-person-plus me-2"></i>Asignar Funcionario</div>
            <div class="card-body">
                <form action="<?= e(APP_URL) ?>/solicitudes/asignar/<?= $sol['id_solicitud'] ?>" method="POST">
                    <?= csrfField() ?>
                    <div class="mb-2">
                        <select class="form-select form-select-sm" name="id_empleado" required>
                            <option value="">-- Seleccione empleado --</option>
                            <?php foreach ($empleados as $emp): ?>
                            <option value="<?= e($emp['id_empleado']) ?>">
                                <?= e($emp['nombre_completo']) ?> — <?= e($emp['cargo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-lim-primary btn-sm w-100">
                        <i class="bi bi-check me-1"></i>Asignar como Elaborador
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
