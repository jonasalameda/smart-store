<?php
$page = $data['data'] ?? $data;
$notifications = $page['notifications'] ?? [];
$title = $page['title'] ?? 'Notifications';
$current_page = 'notifications';

function notification_type_class(string $type): string
{
    return match (strtoupper($type)) {
        'SUCCESS' => 'success',
        'ERROR' => 'error',
        'WARNING' => 'warning',
        default => 'info',
    };
}
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
  <script>
    /** Same-origin path prefix for API routes (matches Dashboard.php / dashboard.js). */
    const APP_API_BASE = "<?= defined('APP_ROOT_DIR_NAME') ? '/' . APP_ROOT_DIR_NAME : '' ?>";
  </script>
  <style>
    .notifications-page { background: #2f2f38; border-radius: 16px; padding: 30px; margin-top: 1rem; }
    .notifications-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
    .notifications-page-header h1 { margin: 0; color: #fff; font-size: 1.75rem; }
    .notification-item-full { display: flex; gap: 20px; padding: 20px; border-radius: 12px; border-left: 4px solid; background: #3a3a45; margin-bottom: 16px; }
    .notification-item-full.notification-success { border-left-color: #22c55e; }
    .notification-item-full.notification-error { border-left-color: #ef4444; }
    .notification-item-full.notification-warning { border-left-color: #f59e0b; }
    .notification-item-full.notification-info { border-left-color: #38bdf8; }
    .notification-title { color: #fff; font-size: 1.1rem; }
    .notification-message { color: #d1d5db; margin: 8px 0 0; line-height: 1.5; }
    .notification-time { color: #9ca3af; font-size: 0.85rem; }
    .notification-type-badge { font-size: 0.7rem; text-transform: uppercase; padding: 4px 10px; border-radius: 6px; background: #4b5563; color: #e5e7eb; }
    .no-notifications-full { text-align: center; padding: 48px; color: #9ca3af; }
    .btn-mark-read { background: #6ec0ff; color: #1a1a22; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-mark-read:hover { filter: brightness(1.05); }
  </style>
</head>
<body>

  <?php include __DIR__ . '/admin/header.php'; ?>

  <main class="main-content">
    <div class="notifications-page">
      <div class="notifications-page-header">
        <h1><?= htmlspecialchars($title) ?></h1>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" class="btn-mark-read" id="mark-all-read">Mark all as read</button>
          <a href="<?= defined('APP_BASE_URL') ? htmlspecialchars(rtrim(APP_BASE_URL, '/')) : '' ?>/dashboard" style="color:#6ec0ff;">Back to dashboard</a>
        </div>
      </div>

      <div class="notifications-list-full" id="notificationsList">
        <?php if (empty($notifications)): ?>
          <div class="no-notifications-full">
            <p>No notifications yet.</p>
          </div>
        <?php else: ?>
          <?php foreach ($notifications as $notification): ?>
            <?php
              $t = (string) ($notification['Type'] ?? 'INFO');
              $css = notification_type_class($t);
            ?>
            <div class="notification-item-full notification-<?= htmlspecialchars($css) ?>">
              <div class="notification-content-full" style="flex:1;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                  <strong class="notification-title"><?= htmlspecialchars((string) ($notification['Title'] ?? '')) ?></strong>
                  <span class="notification-type-badge"><?= htmlspecialchars($t) ?></span>
                </div>
                <p class="notification-message"><?= htmlspecialchars((string) ($notification['Message'] ?? '')) ?></p>
                <small class="notification-time"><?= htmlspecialchars(date('M j, Y H:i', strtotime((string) ($notification['CreatedAt'] ?? 'now')))) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script>
    (function () {
      const btn = document.getElementById('mark-all-read');
      if (!btn) return;

      const configuredApiPath = typeof APP_API_BASE === 'string' ? APP_API_BASE.trim().replace(/\/$/, '') : '';

      function inferBaseFromPathname() {
        var p = window.location.pathname.replace(/\/$/, '');
        if (p.endsWith('/notifications')) {
          return p.slice(0, -'/notifications'.length) || '';
        }
        var m = p.match(/^(\/[^/]+)/);
        return m ? m[1] : '';
      }

      const apiPathPrefix = configuredApiPath || inferBaseFromPathname();

      function notificationsApiUrl(path) {
        var normalizedPath = path.charAt(0) === '/' ? path : '/' + path;
        return apiPathPrefix + normalizedPath;
      }

      btn.addEventListener('click', function () {
        var url = notificationsApiUrl('/api/notifications/mark-read');
        fetch(url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: '{}',
        })
          .then(function (r) {
            return r.text().then(function (text) {
              console.log('mark-read response: status', r.status, 'url', url, 'raw body:', text);
              var data;
              try {
                data = text ? JSON.parse(text) : {};
              } catch (e) {
                console.error('mark-read: response is not JSON', e);
                throw e;
              }
              console.log('mark-read parsed:', data);
              return { ok: r.ok, data: data };
            });
          })
          .then(function (result) {
            if (result.data && result.data.success) {
              window.location.reload();
            } else {
              alert((result.data && result.data.message) || 'Could not mark notifications as read.');
            }
          })
          .catch(function (err) {
            console.error('mark-read fetch error:', err);
            alert('Could not mark notifications as read.');
          });
      });
    })();
  </script>
</body>
</html>
