<?php
$pageTitle = $data['pageTitle'] ?? 'History';
$current_page = $data['current_page'] ?? 'products';
$product = $data['product'] ?? [];
$history = $data['history'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$name = (string) ($product['name'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/sidebar.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../admin/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4">
    <div class="mb-3">
      <a href="<?= htmlspecialchars($base) ?>/products" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars(__('history.back_products')) ?></a>
    </div>
    <div class="mb-4">
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('products.title')) ?></p>
      <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars(__('history.title')) ?></h1>
      <p class="text-muted small mb-0"><?= htmlspecialchars($name) ?> — <?= htmlspecialchars(__('history.subtitle')) ?></p>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= htmlspecialchars(__('history.col_date')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('history.col_qty')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('history.col_cumulative')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($history === []): ?>
              <tr>
                <td colspan="3" class="text-center text-muted py-5"><?= htmlspecialchars(__('history.empty')) ?></td>
              </tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($h['date_received'] ?? '')) ?></td>
                  <td class="text-end font-monospace"><?= (int) ($h['quantity_received'] ?? 0) ?></td>
                  <td class="text-end font-monospace fw-semibold"><?= (int) ($h['cumulative_total'] ?? 0) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
