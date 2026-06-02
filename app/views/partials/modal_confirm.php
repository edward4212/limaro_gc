<?php
/**
 * Modal de confirmación genérico.
 * Uso: incluir en la vista y disparar con data-bs-target="#modalConfirm"
 *       Pasar id y acción via JS: setModalConfirm(url, mensaje)
 */
?>
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-confirm">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmar acción
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalConfirmMsg">
                ¿Está seguro de realizar esta acción?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <form id="modalConfirmForm" method="POST" style="display:inline;">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-danger btn-sm">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function setModalConfirm(url, mensaje) {
    document.getElementById('modalConfirmForm').action = url;
    document.getElementById('modalConfirmMsg').textContent = mensaje || '¿Está seguro?';
}
</script>
