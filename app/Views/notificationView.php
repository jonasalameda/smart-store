<?php
$notifications = $data['notifications'] ?? [];
$title = $data['title'] ?? 'Notifications';
$current_page = 'notifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="/smart-store/public/assets/css/layout/sidebar.css">
  <link rel="stylesheet" href="/smart-store/public/assets/css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Jaldi:wght@400;700&display=swap" rel="stylesheet">
  <style>
    .notification-card { display: flex; align-items: flex-start; gap: 15px; padding: 15px 20px; border-radius: 10px; background: #2f2f38; border: 2px solid #6ec0ff; margin-bottom: 15px; transition: border-color 0.2s, box-shadow 0.2s; }
    .notification-card:hover { box-shadow: 0 0 15px rgba(110, 192, 255, 0.3); }
    .notification-success { border-left: 4px solid #22c55e; }
    .notification-error { border-left: 4px solid #ef4444; }
    .notification-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; flex-shrink: 0; }
    .notification-badge.success { background: #22c55e; color: #fff; }
    .notification-badge.error { background: #ef4444; color: #fff; }
    .notification-message { flex: 1; }
    .notification-message p { margin: 0 0 5px 0; color: #fff; }
    .notification-time { font-size: 12px; color: #b0b0c3; }
    .notifications-empty { text-align: center; padding: 60px 20px; color: #b0b0c3; }
  </style>
</head>
<body>

  <?php include __DIR__ . '/common/header.php'; ?>

  <main class="main-content">
    <h1>Notifications</h1>
    <div class="notifications-container">
      <?php if (empty($notifications)): ?>
        <div class="notifications-empty">
          <p>No notifications yet.</p>
          <p style="font-size: 14px;">When you add customers or receive alerts, they will show up here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $n): ?>
          <div class="notification-card notification-<?= $n['type'] ?>">
            <span class="notification-badge <?= $n['type'] ?>">
              <?= $n['type'] === 'success' ? 'Success' : 'Error' ?>
            </span>
            <div class="notification-message">
              <p><?= htmlspecialchars($n['message']) ?></p>
              <span class="notification-time"><?= htmlspecialchars($n['time']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

</body>
</html>
