<?php
/**
 * SweetAlert2 — Reemplaza confirm() nativo y el modal Bootstrap.
 *
 * Funciones disponibles:
 *   setModalConfirm(url, msg)          → confirma y hace POST a url
 *   swalConfirm(event, msg)            → confirma y ejecuta el form del botón
 *   swalConfirmForm(event, msg)        → confirma y ejecuta el submit del form
 */
?>

<!-- Form oculto para acciones POST desde setModalConfirm -->
<form id="swal-confirm-form" method="POST" style="display:none;">
    <?= csrfField() ?>
</form>

<script>
/* ── setModalConfirm: POST a una URL ────────────────────────────────── */
function setModalConfirm(url, mensaje, titulo) {
    Swal.fire({
        title: titulo || 'Confirmar acción',
        text: mensaje || '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1B3A6B',
        cancelButtonColor: '#94A3B8',
        iconColor: '#F59E0B',
        reverseButtons: true,
        width: '480px',
        customClass: { popup: 'swal-lim-popup' },
        buttonsStyling: true,
    }).then(function(r) {
        if (r.isConfirmed) {
            var f = document.getElementById('swal-confirm-form');
            f.action = url;
            f.submit();
        }
    });
}

/* ── swalConfirm: confirma y submittea el form del botón ────────────── */
function swalConfirm(event, mensaje, titulo) {
    event.preventDefault();
    var btn = event.target.closest('button') || event.currentTarget;
    var form = btn ? btn.closest('form') : null;

    Swal.fire({
        title: titulo || 'Confirmar acción',
        text: mensaje || '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1B3A6B',
        cancelButtonColor: '#94A3B8',
        iconColor: '#F59E0B',
        reverseButtons: true,
        width: '480px',
        customClass: { popup: 'swal-lim-popup' },
        buttonsStyling: true,
    }).then(function(r) {
        if (r.isConfirmed && form) {
            // Deshabilitar validación SweetAlert y submit directo
            btn.removeAttribute('onclick');
            form.submit();
        }
    });
    return false;
}

/* ── swalConfirmForm: confirma y submittea el form ──────────────────── */
function swalConfirmForm(event, mensaje, titulo) {
    event.preventDefault();
    var form = event.target.closest('form') || event.currentTarget;

    Swal.fire({
        title: titulo || 'Confirmar acción',
        text: mensaje || '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1B3A6B',
        cancelButtonColor: '#94A3B8',
        iconColor: '#F59E0B',
        reverseButtons: true,
        width: '480px',
        customClass: { popup: 'swal-lim-popup' },
        buttonsStyling: true,
    }).then(function(r) {
        if (r.isConfirmed) {
            form.removeAttribute('onsubmit');
            form.submit();
        }
    });
    return false;
}

/* ── swalConfirmLink: confirma y sigue el href ──────────────────────── */
function swalConfirmLink(event, mensaje, titulo) {
    event.preventDefault();
    var href = event.currentTarget.href;
    Swal.fire({
        title: titulo || 'Confirmar acción',
        text: mensaje || '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1B3A6B',
        cancelButtonColor: '#94A3B8',
        iconColor: '#F59E0B',
        reverseButtons: true,
        customClass: { popup: 'swal-lim-popup' },
    }).then(function(r) {
        if (r.isConfirmed) window.location.href = href;
    });
    return false;
}

</script>
