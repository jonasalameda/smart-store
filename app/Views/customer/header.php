<?php
/**
 * Customer-facing layout: store / account / checkout (no admin sidebar).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_page = $current_page ?? '';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$session_customer = $_SESSION['customer_account'] ?? null;
$customer_logged_in = is_array($session_customer) && !empty($session_customer['id']);
?>
<header class="customer-topnav">
  <div class="customer-topnav-inner">
    <a class="customer-brand" href="<?= htmlspecialchars($base) ?>/"><?= htmlspecialchars(__('app.brand')) ?></a>
    <nav class="customer-nav" aria-label="Customer">
      <a href="<?= htmlspecialchars($base) ?>/checkout" class="<?= ($current_page === 'checkout') ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.checkout')) ?></a>
      <?php if ($customer_logged_in): ?>
        <a href="<?= htmlspecialchars($base) ?>/account" class="<?= ($current_page === 'account' || $current_page === 'account_receipt') ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.my_account')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/account/search" class="<?= ($current_page === 'account_search') ? 'active' : '' ?>"><?= htmlspecialchars(__('account.search_purchases')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/account/summary" class="<?= ($current_page === 'account_summary') ? 'active' : '' ?>"><?= htmlspecialchars(__('account.spending_summary')) ?></a>
        <a class="customer-nav-cta" href="<?= htmlspecialchars($base) ?>/account/logout"><?= htmlspecialchars(__('nav.log_out')) ?></a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($base) ?>/account/login" class="<?= $current_page === 'account_login' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.log_in')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/account/register" class="<?= $current_page === 'account_register' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.register')) ?></a>
      <?php endif; ?>
      <span class="ms-2"><?php include __DIR__ . '/../common/lang_switcher.php'; ?></span>
    </nav>
  </div>
</header>
