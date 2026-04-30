<?php
$pageTitle = $data['pageTitle'] ?? __('account.summary_title');
$current_page = $data['current_page'] ?? 'account_summary';
$account = $data['account'] ?? [];
$totals = $data['totals'] ?? ['total_spent' => 0.0, 'total_points' => 0, 'purchase_count' => 0];
$byMonth = $data['by_month'] ?? [];
$from = (string) ($data['from'] ?? '');
$to = (string) ($data['to'] ?? '');
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';

$labels = [];
$values = [];
foreach (array_reverse($byMonth) as $row) {
    $labels[] = (string) ($row['ym'] ?? '');
    $values[] = (float) ($row['spent'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light customer-shell">
<?php include __DIR__ . '/../customer/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4">
    <div class="mb-4">
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('account.dashboard_title')) ?></p>
      <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars(__('account.summary_title')) ?></h1>
      <p class="text-muted small mb-0"><?= htmlspecialchars(__('account.summary_sub')) ?></p>
    </div>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <form method="get" action="<?= htmlspecialchars($base) ?>/account/summary" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small mb-0">From</label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-0">To</label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit">Apply</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/account/summary">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 p-3">
          <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('account.total_spent')) ?></div>
          <div class="display-6 fw-bold font-monospace"><?= htmlspecialchars(number_format((float) ($totals['total_spent'] ?? 0), 2)) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 p-3">
          <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('account.total_points')) ?></div>
          <div class="display-6 fw-bold text-primary"><?= (int) ($totals['total_points'] ?? 0) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 p-3">
          <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('account.purchase_count')) ?></div>
          <div class="display-6 fw-bold"><?= (int) ($totals['purchase_count'] ?? 0) ?></div>
        </div>
      </div>
    </div>

    <?php if ($byMonth !== []): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold text-body bg-body-secondary"><?= htmlspecialchars(__('account.by_month')) ?></div>
      <div class="card-body">
        <canvas id="monthChart" height="120"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold text-body bg-body-secondary"><?= htmlspecialchars(__('account.by_month')) ?></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= htmlspecialchars(__('account.month')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('account.count')) ?></th>
              <th class="text-end"><?= htmlspecialchars(__('account.spent')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($byMonth === []): ?>
              <tr><td colspan="3" class="text-center text-muted py-4"><?= htmlspecialchars(__('account.no_purchases')) ?></td></tr>
            <?php else: ?>
              <?php foreach ($byMonth as $row): ?>
                <tr>
                  <td class="font-monospace"><?= htmlspecialchars((string) ($row['ym'] ?? '')) ?></td>
                  <td class="text-end"><?= (int) ($row['cnt'] ?? 0) ?></td>
                  <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float) ($row['spent'] ?? 0), 2)) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
<?php if ($byMonth !== []): ?>
<script>
(function () {
  var ctx = document.getElementById('monthChart');
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels, JSON_THROW_ON_ERROR) ?>,
      datasets: [{
        label: <?= json_encode(__('account.spent'), JSON_THROW_ON_ERROR) ?>,
        data: <?= json_encode($values, JSON_THROW_ON_ERROR) ?>,
        backgroundColor: 'rgba(13, 110, 253, 0.45)',
        borderColor: 'rgba(13, 110, 253, 1)',
        borderWidth: 1
      }]
    },
    options: {
      scales: { y: { beginAtZero: true } },
      plugins: { legend: { display: false } }
    }
  });
})();
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
