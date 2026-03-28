<?php
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
  <a href="#"><span class="icon">S</span><span>Search</span></a>
  <a href="#"><span class="icon">I</span><span>Insights</span></a>
  <a href="#"><span class="icon">Doc</span><span>Docs</span></a>
  <a href="#"><span class="icon">P</span><span>Products</span></a>
  <a href="#"><span class="icon">G</span><span>Settings</span></a>
  <div class="toggle-btn" id="toggle-btn">&lt;&gt;</div>
</aside>
<script>
  document.getElementById('toggle-btn')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('expanded');
  });
</script>
