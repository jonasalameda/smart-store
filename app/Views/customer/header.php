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
      <a href="#" class="<?= ($current_page === 'checkout') ? 'active' : '' ?>" title="Coming soon">Self-checkout</a>
      <?php if ($customer_logged_in): ?>
        <a href="<?= $base ?>/account" class="<?= ($current_page === 'account' || $current_page === 'account_receipt') ? 'active' : '' ?>">My account</a>
        <a class="customer-nav-cta" href="<?= $base ?>/account/logout">Log out</a>
      <?php else: ?>
        <a href="<?= $base ?>/account/login" class="<?= $current_page === 'account_login' ? 'active' : '' ?>">Log in</a>
        <a href="<?= $base ?>/account/register" class="<?= $current_page === 'account_register' ? 'active' : '' ?>">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
