<?php
$d = $data['data'] ?? $data ?? [];
$ok = (bool) ($d['ok'] ?? false);
$message = (string) ($d['message'] ?? '');
$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fan response</title>
  <link rel="stylesheet" href="/smart-store/public/assets/css/layout/sidebar.css">
  <link rel="stylesheet" href="/smart-store/public/assets/css/dashboard.css">
</head>
<body>
  <?php include __DIR__ . '/admin/header.php'; ?>
  <main class="main-content">
    <h1><?= $ok ? 'Success' : 'Notice' ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <?php if ($ok && isset($d['temperature'], $d['threshold'])): ?>
      <p>Reading: <?= htmlspecialchars((string) $d['temperature']) ?> (threshold <?= htmlspecialchars((string) $d['threshold']) ?>)</p>
    <?php endif; ?>
    <p><a href="<?= defined('APP_BASE_URL') ? htmlspecialchars(APP_BASE_URL) : '' ?>/dashboard">Back to dashboard</a></p>
  </main>
</body>
</html>
