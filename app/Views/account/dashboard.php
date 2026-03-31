<?php
$pageTitle = $data['pageTitle'] ?? 'My account';
$current_page = 'account';
$account = $data['account'] ?? [];
$history = $data['history'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/public/assets/css/layout/sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../common/header.php'; ?>
<main class="main-content">
  <div class="container py-4">
  <div class="mb-4">
    <p class="small text-uppercase text-muted fw-semibold mb-1">Loyalty</p>
    <h1 class="h2 fw-bold mb-1">My account</h1>
    <p class="text-muted small mb-0">Placeholder dashboard — swap labels when rules are set.</p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold">Member name</div>
        <div class="fs-5 fw-bold"><?= htmlspecialchars(trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''))) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold">Member ID</div>
        <div class="fs-5 fw-bold font-monospace"><?= htmlspecialchars((string)($account['membership_number'] ?? '—')) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 p-3 border border-primary border-2 bg-primary bg-opacity-10">
        <div class="small text-uppercase text-muted fw-semibold">Points balance</div>
        <div class="display-6 fw-bold text-primary"><?= (int)($account['points_total'] ?? 0) ?></div>
        <div class="small text-muted">Sample total</div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-receipt me-1"></i> Order history
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th class="text-end">Order total</th>
            <th class="text-end">Points earned</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($history)): ?>
            <tr>
              <td colspan="3" class="text-center text-muted py-5">
                <i class="bi bi-bag d-block fs-2 mb-2 opacity-50"></i>
                No orders yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($history as $row): ?>
              <tr>
                <td><?= htmlspecialchars((string)($row['purchased_at'] ?? '')) ?></td>
                <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float)($row['total_amount'] ?? 0), 2)) ?></td>
                <td class="text-end"><span class="badge bg-primary bg-opacity-10 text-primary"><?= (int)($row['points_earned'] ?? 0) ?> pts</span></td>
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
</body>
</html>
