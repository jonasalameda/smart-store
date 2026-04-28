<?php
$pageTitle = $data['pageTitle'] ?? 'My account';
$current_page = $data['current_page'] ?? 'account';
$account = $data['account'] ?? [];
$history = $data['history'] ?? [];
$recentPurchases = $data['recent_purchases'] ?? array_slice($history, 0, 5);
$error = $data['error'] ?? null;
$success = $data['success'] ?? null;
$periodTotals = $data['period_totals'] ?? ['total_spent' => 0.0, 'total_points' => 0, 'purchase_count' => 0];
$from = (string) ($data['from'] ?? '');
$to = (string) ($data['to'] ?? '');
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$first = trim((string) ($account['first_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light customer-shell">
<?php include __DIR__ . '/../customer/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4">
  <div class="mb-4">
    <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('account.loyalty')) ?></p>
    <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars(__('account.greeting')) ?><?= $first !== '' ? ', ' . htmlspecialchars($first) : '' ?></h1>
    <p class="text-muted small mb-0"><?= htmlspecialchars(__('account.dashboard_sub')) ?></p>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" data-auto-dismiss="5000">
      <?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= htmlspecialchars(__('common.close')) ?>"></button>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <div class="small text-uppercase text-muted fw-semibold mb-2"><?= htmlspecialchars(__('account.quick_links')) ?></div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/account/search"><i class="bi bi-search me-1"></i><?= htmlspecialchars(__('account.search_purchases')) ?></a>
        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/account/summary"><i class="bi bi-graph-up me-1"></i><?= htmlspecialchars(__('account.spending_summary')) ?></a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/checkout"><i class="bi bi-cart-check me-1"></i><?= htmlspecialchars(__('nav.checkout')) ?></a>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="small text-uppercase text-muted fw-semibold">Total Spent (Selected Period)</div>
        <div class="h5 mb-0 font-monospace"><?= htmlspecialchars(number_format((float)($periodTotals['total_spent'] ?? 0), 2)) ?></div>
      </div>
      <form method="get" action="<?= htmlspecialchars($base) ?>/account" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label small mb-0">From</label>
          <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small mb-0">To</label>
          <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Apply</button>
          <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/account">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('account.member_name')) ?></div>
        <div class="fs-5 fw-bold"><?= htmlspecialchars(trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''))) ?></div>
        <?php if (!empty($account['email'])): ?>
          <div class="small text-muted mt-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string) $account['email']) ?></div>
        <?php endif; ?>
        <?php if (!empty($account['phone'])): ?>
          <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string) $account['phone']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('account.membership_no')) ?></div>
        <div class="fs-5 fw-bold font-monospace"><?= htmlspecialchars((string)($account['membership_number'] ?? '—')) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-3 border border-primary border-2 bg-primary bg-opacity-10">
        <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('account.points_balance')) ?></div>
        <div class="display-6 fw-bold text-primary"><?= (int)($account['points_total'] ?? 0) ?></div>
        <div class="small text-muted"><?= htmlspecialchars(__('account.points_hint')) ?></div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span><i class="bi bi-receipt me-1"></i> <?= htmlspecialchars(__('account.recent_purchases')) ?></span>
      <a class="small" href="<?= htmlspecialchars($base) ?>/account/search"><?= htmlspecialchars(__('account.search_purchases')) ?> →</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= htmlspecialchars(__('account.col_receipt')) ?></th>
            <th><?= htmlspecialchars(__('account.col_date')) ?></th>
            <th class="text-end"><?= htmlspecialchars(__('account.col_total')) ?></th>
            <th class="text-end"><?= htmlspecialchars(__('account.col_points')) ?></th>
            <th class="text-end"> </th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentPurchases)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-5">
                <i class="bi bi-bag d-block fs-2 mb-2 opacity-50"></i>
                <?= htmlspecialchars(__('account.no_purchases')) ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentPurchases as $row): ?>
              <?php $rid = (int)($row['id'] ?? 0); ?>
              <tr>
                <td class="font-monospace small">#<?= $rid ?></td>
                <td><?= htmlspecialchars((string)($row['purchased_at'] ?? '')) ?></td>
                <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float)($row['total_amount'] ?? 0), 2)) ?></td>
                <td class="text-end"><span class="badge bg-primary bg-opacity-10 text-primary"><?= (int)($row['points_earned'] ?? 0) ?> pts</span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/account/receipts/<?= $rid ?>"><?= htmlspecialchars(__('account.details')) ?></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(function () {
  if (typeof bootstrap === 'undefined' || !bootstrap.Alert) return;
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
    var delay = parseInt(el.getAttribute('data-auto-dismiss') || '5000', 10);
    window.setTimeout(function () {
      var alert = bootstrap.Alert.getOrCreateInstance(el);
      alert.close();
    }, Number.isNaN(delay) ? 5000 : delay);
  });
})();
</script>
</body>
</html>
