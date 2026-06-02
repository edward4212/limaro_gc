<?php
use App\Core\Session;

$success = Session::getFlash('success');
$error   = Session::getFlash('error');
$info    = Session::getFlash('info');
$warning = Session::getFlash('warning');
?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible alert-autohide fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i><?= e($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($info): ?>
<div class="alert alert-info alert-dismissible alert-autohide fade show" role="alert">
    <i class="bi bi-info-circle me-2"></i><?= e($info) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($warning): ?>
<div class="alert alert-warning alert-dismissible alert-autohide fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i><?= e($warning) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
