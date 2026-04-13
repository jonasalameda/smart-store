<?php
/**
 * Admin / staff layout: top navigation (dashboard, products, inventory, etc.).
 * Do not use for customer self-service account or checkout pages.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_page = $current_page ?? '';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$admin_logged_in = !empty($_SESSION['admin_account']['id']);

?>
<header class="staff-top-header">
  <nav class="staff-navbar" aria-label="Staff navigation">
    <div class="staff-navbar-bar">
      <div class="staff-navbar-inner">
        <div class="staff-navbar-burger-wrap">
          <button
            type="button"
            class="staff-navbar-burger"
            id="staff-nav-toggle"
            aria-expanded="false"
            aria-controls="staff-nav-mobile"
          >
            <span class="staff-sr-only"><?= htmlspecialchars(__('staff.nav_aria_open')) ?></span>
            <svg class="staff-navbar-icon staff-navbar-icon--menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg class="staff-navbar-icon staff-navbar-icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
        </div>

        <a href="<?= htmlspecialchars($base) ?>/" class="staff-navbar-brand"><?= htmlspecialchars(__('app.brand')) ?></a>

        <div class="staff-navbar-desktop-links">
          <a href="<?= htmlspecialchars($base) ?>/dashboard" class="staff-navbar-link<?= $current_page === 'dashboard' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.dashboard')) ?></a>
          <a href="<?= htmlspecialchars($base) ?>/notifications" class="staff-navbar-link<?= $current_page === 'notifications' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.notifications')) ?></a>
          <a href="<?= htmlspecialchars($base) ?>/customers" class="staff-navbar-link<?= $current_page === 'customers' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.customers')) ?></a>
          <a href="<?= htmlspecialchars($base) ?>/products" class="staff-navbar-link<?= $current_page === 'products' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.products')) ?></a>
          <a href="<?= htmlspecialchars($base) ?>/inventory" class="staff-navbar-link<?= $current_page === 'inventory' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.inventory')) ?></a>
          <a href="<?= htmlspecialchars($base) ?>/rfid/products" class="staff-navbar-link<?= ($current_page ?? '') === 'rfid' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.rfid_products')) ?></a>
          <span class="staff-navbar-link text-muted small"><?php include __DIR__ . '/../common/lang_switcher.php'; ?></span>
          <?php if ($admin_logged_in): ?>
            <a href="<?= htmlspecialchars($base) ?>/admin/logout" class="staff-navbar-link staff-navbar-link--portal"><?= htmlspecialchars(__('nav.admin_logout')) ?></a>
          <?php else: ?>
            <a href="<?= htmlspecialchars($base) ?>/admin/login" class="staff-navbar-link staff-navbar-link--portal"><?= htmlspecialchars(__('nav.admin_login')) ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div id="staff-nav-mobile" class="staff-navbar-mobile" hidden>
      <div class="staff-navbar-mobile-inner">
        <a href="<?= htmlspecialchars($base) ?>/dashboard" class="staff-navbar-mobile-link<?= $current_page === 'dashboard' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.dashboard')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/notifications" class="staff-navbar-mobile-link<?= $current_page === 'notifications' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.notifications')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/customers" class="staff-navbar-mobile-link<?= $current_page === 'customers' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.customers')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/products" class="staff-navbar-mobile-link<?= $current_page === 'products' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.products')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/inventory" class="staff-navbar-mobile-link<?= $current_page === 'inventory' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.inventory')) ?></a>
        <a href="<?= htmlspecialchars($base) ?>/rfid/products" class="staff-navbar-mobile-link<?= ($current_page ?? '') === 'rfid' ? ' staff-navbar-link--active' : '' ?>"><?= htmlspecialchars(__('nav.rfid_products')) ?></a>
        <div class="staff-navbar-mobile-link"><?php include __DIR__ . '/../common/lang_switcher.php'; ?></div>
        <?php if ($admin_logged_in): ?>
          <a href="<?= htmlspecialchars($base) ?>/admin/logout" class="staff-navbar-mobile-link staff-navbar-link--portal"><?= htmlspecialchars(__('nav.admin_logout')) ?></a>
        <?php else: ?>
          <a href="<?= htmlspecialchars($base) ?>/admin/login" class="staff-navbar-mobile-link staff-navbar-link--portal"><?= htmlspecialchars(__('nav.admin_login')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
<script>
(function () {
  var btn = document.getElementById('staff-nav-toggle');
  var panel = document.getElementById('staff-nav-mobile');
  if (!btn || !panel) return;
  btn.addEventListener('click', function () {
    var open = btn.getAttribute('aria-expanded') === 'true';
    var next = !open;
    btn.setAttribute('aria-expanded', next ? 'true' : 'false');
    panel.hidden = !next;
  });
  panel.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () {
      btn.setAttribute('aria-expanded', 'false');
      panel.hidden = true;
    });
  });
})();
</script>
