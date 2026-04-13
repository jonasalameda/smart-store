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
    <a class="customer-brand" href="<?= $base ?>/">Smart Store</a>
    <nav class="customer-nav" aria-label="Customer">
      <a href="<?= $base ?>/" class="<?= ($current_page === 'store_home') ? 'active' : '' ?>">Home</a>
      <a href="#" class="<?= ($current_page === 'checkout') ? 'active' : '' ?>" title="Coming soon">Self-checkout</a>
      <?php if ($customer_logged_in): ?>
        <a href="<?= $base ?>/account" class="<?= ($current_page === 'account' || $current_page === 'account_receipt') ? 'active' : '' ?>">My account</a>
        <a class="customer-nav-cta" href="<?= $base ?>/account/logout">Log out</a>
      <?php else: ?>
        <span class="text-muted small" title="Temporarily unavailable">Customer portal unavailable</span>
      <?php endif; ?>
    </nav>
  </div>
</header>
