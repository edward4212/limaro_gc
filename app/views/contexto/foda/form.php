<?php $isEdit = $item !== null; ?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-grid-3x3-gap me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/contexto/foda">DOFA</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nuevo' ?></li>
        </ol></nav>
    </div>
</div>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card">
    <div class="card-body">
    <form action="<?= e(APP_URL) ?>/contexto/foda/<?= $isEdit ? 'editar/'.$item['id'] : 'crear' ?>"
          method="POST">
        <?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                <select class="form-select" name="tipo" required>
                    <?php
                    $tipos = [
                        'FORTALEZA'   => '💪 Fortaleza — factor interno positivo',
                        'OPORTUNIDAD' => '🚀 Oportunidad — factor externo positivo',
                        'DEBILIDAD'   => '⚠️ Debilidad — factor interno negativo',
                        'AMENAZA'     => '⚡ Amenaza — factor externo negativo',
                    ];
                    $sel = $item['tipo'] ?? '';
                    foreach ($tipos as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= $sel === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nivel de Impacto</label>
                <select class="form-select" name="impacto">
                    <?php foreach (['ALTO'=>'🔴 Alto','MEDIO'=>'🟡 Medio','BAJO'=>'🟢 Bajo'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($item['impacto'] ?? 'MEDIO') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                <textarea class="form-control" name="descripcion" rows="3" required
                          placeholder="Describa el factor de forma clara y específica..."><?= e($item['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Estrategia / Acción Asociada</label>
                <textarea class="form-control" name="estrategia" rows="2"
                          placeholder="¿Qué acción se toma para aprovechar o mitigar este factor?"><?= e($item['estrategia'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-lim-primary">
                <i class="bi bi-save me-1"></i><?= $isEdit ? 'Guardar Cambios' : 'Registrar' ?>
            </button>
            <a href="<?= e(APP_URL) ?>/contexto/foda" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
</div></div>
