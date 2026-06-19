<?php
$isEdit = !is_null($item);
$action = $isEdit
    ? e(APP_URL).'/proveedores/editar/'.$item['id']
    : e(APP_URL).'/proveedores/crear';
$v = fn($campo, $default = '') => $isEdit ? e($item[$campo] ?? $default) : $default;
$chk = fn($campo) => $isEdit && !empty($item[$campo]) ? 'checked' : '';
?>
<div class="page-header">
    <div>
        <h2><i class="bi bi-truck me-2"></i><?= e($pageTitle) ?></h2>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(APP_URL) ?>/proveedores">Gestión de Proveedores</a></li>
            <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
        </ol></nav>
    </div>
</div>
<form action="<?= $action ?>" method="POST">
    <?= csrfField() ?>

<div class="card mb-4">
    <div class="card-header">Información Básica</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" class="form-control bg-light" value="<?= e($codigo) ?>" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de Vínculo</label>
                <select class="form-select" name="tipo_vinculo">
                    <option value="PROVEEDOR" <?= $v('tipo_vinculo','PROVEEDOR')==='PROVEEDOR'?'selected':'' ?>>Proveedor</option>
                    <option value="CONTRATISTA" <?= $v('tipo_vinculo')==='CONTRATISTA'?'selected':'' ?>>Contratista</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Tipo de Persona</label>
                <select class="form-select" name="tipo_persona" id="selTipoPersona" onchange="toggleAnexosPersona()">
                    <option value="JURIDICA" <?= $v('tipo_persona','JURIDICA')==='JURIDICA'?'selected':'' ?>>Persona Jurídica</option>
                    <option value="NATURAL" <?= $v('tipo_persona')==='NATURAL'?'selected':'' ?>>Persona Natural</option>
                </select>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Razón Social / Nombre Completo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="razon_social" value="<?= $v('razon_social') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo Documento</label>
                <input type="text" class="form-control" name="tipo_documento" value="<?= $v('tipo_documento') ?>" placeholder="NIT, CC, CE...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Número Documento</label>
                <input type="text" class="form-control" name="numero_documento" value="<?= $v('numero_documento') ?>">
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion" value="<?= $v('direccion') ?>"></div>
            <div class="col-md-3"><label class="form-label">Barrio</label>
                <input type="text" class="form-control" name="barrio" value="<?= $v('barrio') ?>"></div>
            <div class="col-md-3"><label class="form-label">Ciudad</label>
                <input type="text" class="form-control" name="ciudad" value="<?= $v('ciudad') ?>"></div>
            <div class="col-md-2"><label class="form-label">Departamento</label>
                <input type="text" class="form-control" name="departamento" value="<?= $v('departamento') ?>"></div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><label class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" value="<?= $v('telefono') ?>"></div>
            <div class="col-md-3"><label class="form-label">Celular</label>
                <input type="text" class="form-control" name="celular" value="<?= $v('celular') ?>"></div>
            <div class="col-md-6"><label class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" name="correo" value="<?= $v('correo') ?>"></div>
        </div>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Fecha de Constitución</label>
                <input type="date" class="form-control" name="fecha_constitucion" value="<?= $v('fecha_constitucion') ?>"></div>
            <div class="col-md-3"><label class="form-label">Cámara de Comercio</label>
                <input type="text" class="form-control" name="camara_comercio" value="<?= $v('camara_comercio') ?>"></div>
            <div class="col-md-4"><label class="form-label">Actividad Económica</label>
                <input type="text" class="form-control" name="actividad_economica" value="<?= $v('actividad_economica') ?>"></div>
            <div class="col-md-2"><label class="form-label">CIIU</label>
                <input type="text" class="form-control" name="ciiu" value="<?= $v('ciiu') ?>"></div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-12"><label class="form-label">Servicio / Bien Prestado</label>
                <textarea class="form-control" name="servicio_prestado" rows="2"><?= $v('servicio_prestado') ?></textarea></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Representante Legal</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" name="rl_nombre" value="<?= $v('rl_nombre') ?>"></div>
            <div class="col-md-3"><label class="form-label">Tipo Documento</label>
                <input type="text" class="form-control" name="rl_tipo_documento" value="<?= $v('rl_tipo_documento') ?>"></div>
            <div class="col-md-3"><label class="form-label">Número Documento</label>
                <input type="text" class="form-control" name="rl_numero_documento" value="<?= $v('rl_numero_documento') ?>"></div>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="rl_telefono" value="<?= $v('rl_telefono') ?>"></div>
            <div class="col-md-5"><label class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" name="rl_correo" value="<?= $v('rl_correo') ?>"></div>
            <div class="col-md-3 form-check ms-2 mb-2">
                <input class="form-check-input" type="checkbox" name="rl_es_asociado_coopeaipe" id="rlAsoc" <?= $chk('rl_es_asociado_coopeaipe') ?>>
                <label class="form-check-label small" for="rlAsoc">Es asociado de COOPEAIPE</label>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Información Tributaria</div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ([
                'regimen_simplificado'=>'Régimen Simplificado','regimen_comun'=>'Régimen Común',
                'gran_contribuyente'=>'Gran Contribuyente','exento_iva'=>'Exento IVA','autorretenedor'=>'Autorretenedor',
            ] as $campo => $label): ?>
            <div class="col-md-2 col-6 form-check">
                <input class="form-check-input" type="checkbox" name="<?= $campo ?>" id="<?= $campo ?>" <?= $chk($campo) ?>>
                <label class="form-check-label small" for="<?= $campo ?>"><?= $label ?></label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Información Financiera <small class="text-muted">(opcional)</small></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-2"><label class="form-label small">Fecha de Corte</label>
                <input type="date" class="form-control form-control-sm" name="fecha_corte_financiera" value="<?= $v('fecha_corte_financiera') ?>"></div>
            <div class="col-md-2"><label class="form-label small">Ingresos Mensuales</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="total_ingresos_mensuales" value="<?= $v('total_ingresos_mensuales') ?>"></div>
            <div class="col-md-2"><label class="form-label small">Egresos Mensuales</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="total_egresos_mensuales" value="<?= $v('total_egresos_mensuales') ?>"></div>
        </div>
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label small">Total Activos</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="total_activos" value="<?= $v('total_activos') ?>"></div>
            <div class="col-md-2"><label class="form-label small">Total Pasivos</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="total_pasivos" value="<?= $v('total_pasivos') ?>"></div>
            <div class="col-md-2"><label class="form-label small">Total Patrimonio</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="total_patrimonio" value="<?= $v('total_patrimonio') ?>"></div>
        </div>
        <div class="row g-2 mt-3">
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="realiza_transacciones_moneda_extranjera" id="moneda_ext" <?= $chk('realiza_transacciones_moneda_extranjera') ?>>
                <label class="form-check-label small" for="moneda_ext">Realiza transacciones en moneda extranjera</label>
            </div>
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="posee_cuenta_exterior" id="cta_ext" <?= $chk('posee_cuenta_exterior') ?>>
                <label class="form-check-label small" for="cta_ext">Posee cuenta en el exterior</label>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 border-warning">
    <div class="card-header bg-warning-subtle">
        <i class="bi bi-shield-exclamation me-1"></i>Persona Públicamente Expuesta (PEP) y Prevención LA/FT
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="pep_administra_recursos_publicos" id="pep1" <?= $chk('pep_administra_recursos_publicos') ?>>
                <label class="form-check-label small" for="pep1">¿Administra recursos públicos?</label>
            </div>
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="pep_ejerce_poder_publico" id="pep2" <?= $chk('pep_ejerce_poder_publico') ?>>
                <label class="form-check-label small" for="pep2">¿Ejerce algún grado de poder público?</label>
            </div>
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="pep_reconocimiento_publico" id="pep3" <?= $chk('pep_reconocimiento_publico') ?>>
                <label class="form-check-label small" for="pep3">¿Goza de reconocimiento público?</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small">Si alguna respuesta anterior es afirmativa, especifique:</label>
            <textarea class="form-control form-control-sm" name="pep_especificar" rows="2"><?= $v('pep_especificar') ?></textarea>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="pep_vinculo_familiar" id="pepfam" <?= $chk('pep_vinculo_familiar') ?>>
                <label class="form-check-label small" for="pepfam">¿Tiene vínculo familiar con una persona PEP?</label>
            </div>
            <div class="col-md-8">
                <label class="form-label small">Si la respuesta es sí, nombre completo</label>
                <input type="text" class="form-control form-control-sm" name="pep_vinculo_familiar_nombre" value="<?= $v('pep_vinculo_familiar_nombre') ?>">
            </div>
        </div>
        <hr>
        <div class="row g-2">
            <div class="col-md-6 form-check">
                <input class="form-check-input" type="checkbox" name="cuenta_sgsst" id="sgsst" <?= $chk('cuenta_sgsst') ?>>
                <label class="form-check-label small" for="sgsst">Cuenta con Sistema de Gestión SST</label>
            </div>
            <div class="col-md-6 form-check">
                <input class="form-check-input" type="checkbox" name="cuenta_sarlaft" id="sarlaft" <?= $chk('cuenta_sarlaft') ?>>
                <label class="form-check-label small" for="sarlaft">Cuenta con sistema propio de prevención LA/FT</label>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Contacto Operativo</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Nombre y Apellidos</label>
                <input type="text" class="form-control" name="contacto_nombre" value="<?= $v('contacto_nombre') ?>"></div>
            <div class="col-md-3"><label class="form-label">Cargo</label>
                <input type="text" class="form-control" name="contacto_cargo" value="<?= $v('contacto_cargo') ?>"></div>
            <div class="col-md-2"><label class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="contacto_telefono" value="<?= $v('contacto_telefono') ?>"></div>
            <div class="col-md-3"><label class="form-label">Correo</label>
                <input type="email" class="form-control" name="contacto_correo" value="<?= $v('contacto_correo') ?>"></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Checklist de Anexos Requeridos</div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4 form-check anexo-juridica">
                <input class="form-check-input" type="checkbox" name="anexo_certificado_existencia" id="ax1" <?= $chk('anexo_certificado_existencia') ?>>
                <label class="form-check-label small" for="ax1">Certificado de existencia y representación legal</label>
            </div>
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="anexo_rut" id="ax2" <?= $chk('anexo_rut') ?>>
                <label class="form-check-label small" for="ax2">RUT actualizado</label>
            </div>
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="anexo_cedula" id="ax3" <?= $chk('anexo_cedula') ?>>
                <label class="form-check-label small" for="ax3">Cédula ampliada (representante legal)</label>
            </div>
            <div class="col-md-4 form-check anexo-juridica">
                <input class="form-check-input" type="checkbox" name="anexo_declaracion_renta" id="ax4" <?= $chk('anexo_declaracion_renta') ?>>
                <label class="form-check-label small" for="ax4">Declaración de renta último año</label>
            </div>
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="anexo_certificado_cuenta_bancaria" id="ax5" <?= $chk('anexo_certificado_cuenta_bancaria') ?>>
                <label class="form-check-label small" for="ax5">Certificado cuenta bancaria</label>
            </div>
            <div class="col-md-4 form-check">
                <input class="form-check-input" type="checkbox" name="anexo_portafolio_servicios" id="ax6" <?= $chk('anexo_portafolio_servicios') ?>>
                <label class="form-check-label small" for="ax6">Portafolio de servicios/productos</label>
            </div>
        </div>
        <small class="text-muted">Los anexos marcados como "solo jurídica" siguen visibles si necesita registrarlos para una persona natural igualmente.</small>
    </div>
</div>

<?php if ($isEdit): ?>
<div class="card mb-4">
    <div class="card-header">Estado y Verificación</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select class="form-select" name="estado">
                    <?php foreach (['ACTIVO','INACTIVO','RESTRINGIDO'] as $est): ?>
                    <option value="<?= $est ?>" <?= $item['estado'] === $est ? 'selected' : '' ?>><?= $est ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if (!empty($item['id_usuario_verifico'])): ?>
        <div class="alert alert-light border small mb-3">
            Verificado por <strong><?= e($item['usuario_verifico_nombre'] ?? '—') ?></strong>
            el <?= fechaEs($item['fecha_verificacion']) ?>.
        </div>
        <?php endif; ?>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="marcar_verificado" id="marcarVer" value="1">
            <label class="form-check-label" for="marcarVer">Marcar como verificado por mí en este momento</label>
        </div>
        <textarea class="form-control" name="resultado_verificacion" rows="2"
            placeholder="Resultado de la verificación (opcional)"><?= $v('resultado_verificacion') ?></textarea>
    </div>
</div>
<?php endif; ?>

<div class="d-flex gap-2 justify-content-end mb-4">
    <a href="<?= e(APP_URL) ?>/proveedores" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-lim-primary"><i class="bi bi-save me-1"></i>Guardar</button>
</div>
</form>

<script>
function toggleAnexosPersona() {
    var esNatural = document.getElementById('selTipoPersona').value === 'NATURAL';
    document.querySelectorAll('.anexo-juridica').forEach(function(el) {
        el.style.opacity = esNatural ? '0.5' : '1';
    });
}
document.addEventListener('DOMContentLoaded', toggleAnexosPersona);
</script>
