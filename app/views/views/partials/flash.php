<?php
use App\Core\Session;

$success = Session::getFlash('success');
$error   = Session::getFlash('error');
$info    = Session::getFlash('info');
$warning = Session::getFlash('warning');

$msgs = array_filter([
    'success' => $success,
    'error'   => $error,
    'info'    => $info,
    'warning' => $warning,
]);

if (empty($msgs)) return;
?>
<script>
function _showFlash() {
    if (typeof Swal === 'undefined') { setTimeout(_showFlash, 100); return; }
    <?php foreach ($msgs as $tipo => $msg): ?>
    <?php
    $icon  = match($tipo) {
        'success' => 'success',
        'error'   => 'error',
        'warning' => 'warning',
        'info'    => 'info',
        default   => 'info',
    };
    $color = match($tipo) {
        'success' => '#059669',
        'error'   => '#F43F5E',
        'warning' => '#F59E0B',
        'info'    => '#00B5D8',
        default   => '#6D28D9',
    };
    ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: '<?= $icon ?>',
        title: <?= json_encode(strip_tags($msg)) ?>,
        showConfirmButton: false,
        timer: <?= $tipo === 'error' ? 6000 : 4000 ?>,
        timerProgressBar: true,
        iconColor: '<?= $color ?>',
        customClass: { popup: 'swal-lim-toast' },
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
    <?php endforeach; ?>
}
document.addEventListener('DOMContentLoaded', _showFlash);
</script>
