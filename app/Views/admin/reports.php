<?php
$pageTitle = $data['pageTitle'] ?? 'Reports';
$current_page = $data['current_page'] ?? 'reports';
$from = (string) ($data['from'] ?? '');
$to = (string) ($data['to'] ?? '');
$categoryId = (int) ($data['category_id'] ?? 0);
$categories = $data['categories'] ?? [];
$inventoryRows = $data['inventory_rows'] ?? [];
$lowStockThreshold = (int) ($data['low_stock_threshold'] ?? 5);
$salesByProduct = $data['sales_by_product'] ?? [];
$mostSold = $data['most_sold'] ?? [];
$leastSold = $data['least_sold'] ?? [];
$totalSales = (float) ($data['total_sales'] ?? 0);
$salesTrend = $data['sales_trend'] ?? [];
$customerActivity = $data['customer_activity'] ?? ['total_customers' => 0, 'new_customers' => 0, 'returning_customers' => 0];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';

$trendLabels = [];
$trendValues = [];
foreach ($salesTrend as $row) {
    $trendLabels[] = (string) ($row['day_key'] ?? '');
    $trendValues[] = (float) ($row['sales_total'] ?? 0);
}
$salesPieLabels = [];
$salesPieValues = [];
foreach ($salesByProduct as $row) {
    $qty = (int) ($row['sold_qty'] ?? 0);
    if ($qty <= 0) {
        continue;
    }
    $salesPieLabels[] = (string) ($row['product_name'] ?? '');
    $salesPieValues[] = $qty;
}
$mostLabels = [];
$mostValues = [];
foreach ($mostSold as $row) {
    $mostLabels[] = (string) ($row['product_name'] ?? '');
    $mostValues[] = (int) ($row['sold_qty'] ?? 0);
}
$leastLabels = [];
$leastValues = [];
foreach ($leastSold as $row) {
    $leastLabels[] = (string) ($row['product_name'] ?? '');
    $leastValues[] = (int) ($row['sold_qty'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/sidebar.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light">
<?php include __DIR__ . '/header.php'; ?>
<main class="main-content">
  <div class="container py-4">
    <h1 class="h3 fw-bold mb-3">Report</h1>
    <form method="get" action="<?= htmlspecialchars($base) ?>/admin/reports" class="card card-body border-0 shadow-sm mb-4">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="form-label small mb-0">From</label>
          <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-0">To</label>
          <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small mb-0">Category</label>
          <select class="form-select" name="category_id">
            <option value="0">All categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) ($cat['id'] ?? 0) ?>"<?= ((int) ($cat['id'] ?? 0) === $categoryId) ? ' selected' : '' ?>>
                <?= htmlspecialchars((string) ($cat['name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label small mb-0">Low stock thresholds are per category (set below)</label>
          <input type="text" class="form-control" value="Per-category thresholds active" readonly>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit">Apply</button>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($base) ?>/admin/reports/export?section=all&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&category_id=<?= (int) $categoryId ?>&low_stock_threshold=<?= (int) $lowStockThreshold ?>">Export CSV (All)</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($base) ?>/admin/reports/export?section=inventory&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&category_id=<?= (int) $categoryId ?>&low_stock_threshold=<?= (int) $lowStockThreshold ?>">Inventory CSV</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($base) ?>/admin/reports/export?section=sales&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&category_id=<?= (int) $categoryId ?>&low_stock_threshold=<?= (int) $lowStockThreshold ?>">Sales CSV</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($base) ?>/admin/reports/export?section=activity&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&category_id=<?= (int) $categoryId ?>&low_stock_threshold=<?= (int) $lowStockThreshold ?>">Activity CSV</a>
      </div>
    </form>

    <form method="post" action="<?= htmlspecialchars($base) ?>/admin/reports/thresholds" class="card card-body border-0 shadow-sm mb-4">
      <div class="fw-semibold mb-2">Per-Category Low Stock Thresholds</div>
      <div class="row g-2 align-items-end">
        <?php foreach ($categories as $cat): ?>
          <div class="col-md-3 col-sm-6">
            <label class="form-label small mb-0"><?= htmlspecialchars((string) ($cat['name'] ?? 'Category')) ?></label>
            <input type="number" min="1" class="form-control" name="category_thresholds[<?= (int) ($cat['id'] ?? 0) ?>]" value="<?= (int) ($cat['low_stock_threshold'] ?? 5) ?>">
          </div>
        <?php endforeach; ?>
        <div class="col-12 mt-2">
          <button type="submit" class="btn btn-outline-primary btn-sm">Save thresholds</button>
        </div>
      </div>
    </form>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">Inventory Report</div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr><th>Product</th><th>ID</th><th class="text-end">Available Qty</th><th>Last Restocked</th><th>Threshold</th><th>Status</th></tr>
          </thead>
          <tbody id="inventoryRowsBody">
          <?php foreach ($inventoryRows as $row): ?>
            <?php $qty = (int) ($row['stock_qty'] ?? 0); ?>
            <?php $status = (string) ($row['status'] ?? 'OK'); ?>
            <?php $badge = $status === 'Low' ? 'danger' : ($status === 'Moderate' ? 'warning text-dark' : 'success'); ?>
            <tr>
              <td><?= htmlspecialchars((string) ($row['name'] ?? '')) ?></td>
              <td>#<?= (int) ($row['id'] ?? 0) ?></td>
              <td class="text-end"><?= $qty ?></td>
              <td><?= htmlspecialchars((string) ($row['last_received_at'] ?? 'N/A')) ?></td>
              <td><?= (int) ($row['threshold'] ?? 5) ?></td>
              <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Total Sales Value</div><div class="h4 mb-0"><?= htmlspecialchars(number_format($totalSales, 2)) ?></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">Customers Purchased</div><div class="h4 mb-0"><?= (int) ($customerActivity['total_customers'] ?? 0) ?></div></div></div>
      <div class="col-md-4"><div class="card border-0 shadow-sm p-3"><div class="small text-muted">New vs Returning</div><div class="h6 mb-0"><?= (int) ($customerActivity['new_customers'] ?? 0) ?> new / <?= (int) ($customerActivity['returning_customers'] ?? 0) ?> returning</div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">Sales Report</div>
      <div class="card-body border-bottom">
        <h6 class="mb-3">Sold Quantity by Product (Circle Diagram)</h6>
        <div style="max-width: 520px; margin: 0 auto;">
          <canvas id="salesQtyPie" height="140"></canvas>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead><tr><th>Product</th><th class="text-end">Sold Qty</th><th class="text-end">Sales Value</th></tr></thead>
          <tbody>
          <?php foreach ($salesByProduct as $row): ?>
            <tr>
              <td><?= htmlspecialchars((string) ($row['product_name'] ?? '')) ?></td>
              <td class="text-end"><?= (int) ($row['sold_qty'] ?? 0) ?></td>
              <td class="text-end"><?= htmlspecialchars(number_format((float) ($row['sales_value'] ?? 0), 2)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-body border-top">
        <h6 class="mb-3">Most vs Least Sold (Long Stick Diagram)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted mb-2">Most Sold</div>
            <canvas id="mostSoldBars" height="170"></canvas>
          </div>
          <div class="col-md-6">
            <div class="small text-muted mb-2">Least Sold</div>
            <canvas id="leastSoldBars" height="170"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">Sales Trend (Line)</div>
      <div class="card-body"><canvas id="salesTrend" height="90"></canvas></div>
    </div>
  </div>
</main>
<script>
(function () {
  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function statusBadgeClass(status) {
    if (status === 'Low') return 'danger';
    if (status === 'Moderate') return 'warning text-dark';
    return 'success';
  }

  function renderInventoryRows(rows) {
    var body = document.getElementById('inventoryRowsBody');
    if (!body) return;
    var html = '';
    rows.forEach(function (row) {
      var qty = Number(row.stock_qty || 0);
      var status = String(row.status || 'OK');
      html += '<tr>'
        + '<td>' + escapeHtml(row.name || '') + '</td>'
        + '<td>#' + Number(row.id || 0) + '</td>'
        + '<td class="text-end">' + qty + '</td>'
        + '<td>' + escapeHtml(row.last_received_at || 'N/A') + '</td>'
        + '<td>' + Number(row.threshold || 5) + '</td>'
        + '<td><span class="badge bg-' + statusBadgeClass(status) + '">' + escapeHtml(status) + '</span></td>'
        + '</tr>';
    });
    body.innerHTML = html;
  }

  function pollInventory() {
    var url = <?= json_encode($base . '/admin/reports/inventory-live', JSON_THROW_ON_ERROR) ?>;
    fetch(url, { cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !Array.isArray(data.rows)) return;
        renderInventoryRows(data.rows);
      })
      .catch(function () { /* ignore poll errors */ });
  }

  var pieEl = document.getElementById('salesQtyPie');
  if (pieEl && typeof Chart !== 'undefined') {
    new Chart(pieEl, {
      type: 'pie',
      data: {
        labels: <?= json_encode($salesPieLabels, JSON_THROW_ON_ERROR) ?>,
        datasets: [{
          data: <?= json_encode($salesPieValues, JSON_THROW_ON_ERROR) ?>,
          backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#198754', '#0dcaf0'],
          borderWidth: 1
        }]
      },
      options: {
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }

  var mostEl = document.getElementById('mostSoldBars');
  if (mostEl && typeof Chart !== 'undefined') {
    new Chart(mostEl, {
      type: 'bar',
      data: {
        labels: <?= json_encode($mostLabels, JSON_THROW_ON_ERROR) ?>,
        datasets: [{
          data: <?= json_encode($mostValues, JSON_THROW_ON_ERROR) ?>,
          backgroundColor: 'rgba(25, 135, 84, 0.70)',
          borderColor: 'rgba(25, 135, 84, 1)',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  var leastEl = document.getElementById('leastSoldBars');
  if (leastEl && typeof Chart !== 'undefined') {
    new Chart(leastEl, {
      type: 'bar',
      data: {
        labels: <?= json_encode($leastLabels, JSON_THROW_ON_ERROR) ?>,
        datasets: [{
          data: <?= json_encode($leastValues, JSON_THROW_ON_ERROR) ?>,
          backgroundColor: 'rgba(220, 53, 69, 0.70)',
          borderColor: 'rgba(220, 53, 69, 1)',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  var el = document.getElementById('salesTrend');
  if (!el || typeof Chart === 'undefined') return;
  new Chart(el, {
    type: 'line',
    data: {
      labels: <?= json_encode($trendLabels, JSON_THROW_ON_ERROR) ?>,
      datasets: [{ data: <?= json_encode($trendValues, JSON_THROW_ON_ERROR) ?>, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.2)', fill: true, tension: 0.2 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });

  pollInventory();
  window.setInterval(pollInventory, 10000);
})();
</script>
</body>
</html>
