<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_page = $current_page ?? '';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<header class="site-header">
  <div class="logo">
    <h1>Smart Store</h1>
  </div>
</header>
<aside class="sidebar expanded" id="sidebar">
  <a href="<?= $base ?>/dashboard" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>"><span class="icon">D</span><span>Dashboard</span></a>
  <a href="<?= $base ?>/notifications" class="<?= $current_page === 'notifications' ? 'active' : '' ?>"><span class="icon">N</span><span>Notifications</span></a>
  <a href="<?= $base ?>/" class="<?= $current_page === 'customers' ? 'active' : '' ?>"><span class="icon">C</span><span>Customers</span></a>
  <a href="<?= $base ?>/products" class="<?= $current_page === 'products' ? 'active' : '' ?>"><span class="icon">P</span><span>Products</span></a>
  <a href="<?= $base ?>/inventory" class="<?= $current_page === 'inventory' ? 'active' : '' ?>"><span class="icon">V</span><span>Inventory</span></a>
  <a href="#"><span class="icon">S</span><span>Search</span></a>
  <a href="#"><span class="icon">I</span><span>Insights</span></a>
  <a href="#"><span class="icon">Doc</span><span>Docs</span></a>
  <a href="#"><span class="icon">G</span><span>Settings</span></a>
  <?php if (!empty($_SESSION['phase3_account']) && is_array($_SESSION['phase3_account'])): ?>
    <a href="<?= $base ?>/account" class="<?= $current_page === 'account' ? 'active' : '' ?>"><span class="icon">A</span><span>Account</span></a>
    <a href="<?= $base ?>/account/logout"><span class="icon">O</span><span>Log out</span></a>
  <?php else: ?>
    <a href="<?= $base ?>/account/login" class="<?= $current_page === 'account_login' ? 'active' : '' ?>"><span class="icon">L</span><span>Log in</span></a>
    <a href="<?= $base ?>/account/register" class="<?= $current_page === 'account_register' ? 'active' : '' ?>"><span class="icon">R</span><span>Register</span></a>
  <?php endif; ?>
  <div class="toggle-btn" id="toggle-btn">&lt;&gt;</div>
</aside>
<script>
  document.getElementById('toggle-btn')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('expanded');
  });
</script>
