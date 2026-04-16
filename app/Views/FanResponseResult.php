<?php
$d = $data['data'] ?? $data ?? [];
$ok = (bool) ($d['ok'] ?? false);
$message = (string) ($d['message'] ?? '');
$current_page = 'dashboard';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(__('fan.title')) ?></title>
  <link rel="stylesheet" href="/assets/css/layout/sidebar.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
  <?php include __DIR__ . '/admin/header.php'; ?>
  <main class="main-content">
    <h1><?= $ok ? htmlspecialchars(__('fan.success')) : htmlspecialchars(__('fan.notice')) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <?php if ($ok && isset($d['temperature'], $d['threshold'])): ?>
      <p><?= htmlspecialchars(str_replace(
          ['{0}', '{1}'],
          [(string) $d['temperature'], (string) $d['threshold']],
          __('fan.reading')
      )) ?></p>
    <?php endif; ?>
    <p><a href="<?= htmlspecialchars($base) ?>/dashboard"><?= htmlspecialchars(__('common.back_dashboard')) ?></a></p>
  </main>
</body>
</html>
