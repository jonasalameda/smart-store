<?php
$pageTitle = $data['pageTitle'] ?? __('account.search_title');
$current_page = $data['current_page'] ?? 'account_search';
$account = $data['account'] ?? [];
$from = (string) ($data['from'] ?? '');
$to = (string) ($data['to'] ?? '');
$product = (string) ($data['product'] ?? '');
$results = $data['results'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light customer-shell">
<?php include __DIR__ . '/../customer/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4">
    <div class="mb-4">
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('account.dashboard_title')) ?></p>
      <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars(__('account.search_title')) ?></h1>
      <p class="text-muted small mb-0"><?= htmlspecialchars(__('account.search_sub')) ?></p>
    </div>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($base) ?>/account/search" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small mb-0"><?= htmlspecialchars(__('account.date_from')) ?></label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-0"><?= htmlspecialchars(__('account.date_to')) ?></label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-0"><?= htmlspecialchars(__('account.product_name')) ?></label>
            <input type="search" name="product" class="form-control" value="<?= htmlspecialchars($product) ?>" placeholder="…">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><?= htmlspecialchars(__('account.search')) ?></button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/account/search"><?= htmlspecialchars(__('account.reset')) ?></a>
          </div>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold"><?= htmlspecialchars(__('account.search_results')) ?></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= htmlspecialchars(__('account.col_receipt')) ?></th>
              <th><?= htmlspecialchars(__('account.col_date')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('account.col_items')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('account.col_total')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('account.col_points')) ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($results === []): ?>
              <tr><td colspan="6" class="text-center text-muted py-4"><?= htmlspecialchars(__('account.no_purchases')) ?></td></tr>
            <?php else: ?>
              <?php foreach ($results as $row): ?>
                <?php $rid = (int) ($row['id'] ?? 0); ?>
                <tr>
                  <td class="font-monospace small">#<?= $rid ?></td>
                  <td><?= htmlspecialchars((string) ($row['purchased_at'] ?? '')) ?></td>
                  <td class="text-end"><?= (int) ($row['items_count'] ?? 0) ?></td>
                  <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                  <td class="text-end"><?= (int) ($row['points_earned'] ?? 0) ?></td>
                  <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/account/receipts/<?= $rid ?>"><?= htmlspecialchars(__('account.details')) ?></a></td>
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
