<?php
$pageTitle = $data['pageTitle'] ?? __('portal_disabled.title');
$message = $data['message'] ?? __('portal_disabled.message');
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column justify-content-center py-4">
<div class="text-center mb-2 d-flex justify-content-center align-items-center gap-2 flex-wrap"><?php include __DIR__ . '/../common/theme_toggle.php'; ?><?php include __DIR__ . '/../common/lang_switcher.php'; ?></div>
<main class="main-content w-100">
  <div class="container py-5" style="max-width:560px;">
    <div class="card border-0 shadow">
      <div class="card-body p-4 p-md-5 text-center">
        <h1 class="h3 fw-bold mb-3"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
        <a class="btn btn-primary" href="<?= htmlspecialchars($base) ?>/"><?= htmlspecialchars(__('portal_disabled.home')) ?></a>
      </div>
    </div>
  </div>
</main>
</body>
</html>
