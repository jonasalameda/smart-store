<?php
declare(strict_types=1);

use App\Helpers\FlashHelper;

$flash = FlashHelper::consume();
if ($flash === null) {
    return;
}
$type = $flash['type'];
$message = $flash['message'];
$bsClass = match ($type) {
    'success' => 'text-bg-success',
    'error', 'danger' => 'text-bg-danger',
    'warning' => 'text-bg-warning',
    default => 'text-bg-info',
};
?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
  <div id="appFlashToast" class="toast align-items-center <?= htmlspecialchars($bsClass) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="6000">
    <div class="d-flex">
      <div class="toast-body"><?= htmlspecialchars($message) ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="<?= htmlspecialchars(__('common.close')) ?>"></button>
    </div>
  </div>
</div>
<script>
(function () {
  var el = document.getElementById('appFlashToast');
  if (!el || typeof bootstrap === 'undefined' || !bootstrap.Toast) return;
  var t = bootstrap.Toast.getOrCreateInstance(el);
  el.addEventListener('hidden.bs.toast', function () { el.closest('.toast-container')?.remove(); });
})();
</script>
