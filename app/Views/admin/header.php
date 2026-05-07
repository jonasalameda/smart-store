<?php
/**
 * Admin / staff layout — same structure and classes as customer/header.php (customer-topnav).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_page = $current_page ?? '';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$admin_logged_in = !empty($_SESSION['admin_account']['id']);

?>
<header class="customer-topnav">
  <div class="customer-topnav-inner">
    <div class="customer-topnav-start">
      <a class="customer-brand" href="<?= htmlspecialchars($base) ?>/"><?= htmlspecialchars(__('app.brand')) ?></a>
      <nav class="customer-nav customer-nav-links" aria-label="<?= htmlspecialchars(__('staff.badge')) ?>">
        <a href="<?= htmlspecialchars($base) ?>/dashboard" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.dashboard')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/notifications" class="<?= $current_page === 'notifications' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.notifications')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/customers" class="<?= $current_page === 'customers' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.customers')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/products" class="<?= $current_page === 'products' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.products')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/inventory" class="<?= $current_page === 'inventory' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.inventory')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/admin/reports" class="<?= $current_page === 'reports' ? 'active' : '' ?>">Reports</a>
        <?php /* RFID shelf page disabled: /rfid/products
        <a href="<?= htmlspecialchars($base) ?>/rfid/products" class="<?= ($current_page ?? '') === 'rfid' ? 'active' : '' ?>"><?= htmlspecialchars(__('nav.rfid_products')) ?></a>
        */ ?>
      </nav>
    </div>
    <div class="customer-topnav-end">
      <?php include __DIR__ . '/../common/theme_toggle.php'; ?>
      <?php include __DIR__ . '/../common/lang_switcher.php'; ?>
      <?php if ($admin_logged_in): ?>
        <a class="customer-nav-cta" href="<?= htmlspecialchars($base) ?>/admin/logout"><?= htmlspecialchars(__('nav.admin_logout')) ?></a>
      <?php else: ?>
        <a class="customer-nav-cta" href="<?= htmlspecialchars($base) ?>/admin/login"><?= htmlspecialchars(__('nav.admin_login')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
