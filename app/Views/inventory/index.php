<?php
$pageTitle = $data['pageTitle'] ?? 'Inventory';
$current_page = 'inventory';
$products = $data['products'] ?? [];
$error = $data['error'] ?? null;
$success = $data['success'] ?? null;
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
    <p class="small text-uppercase text-muted fw-semibold mb-1">Stock</p>
    <h1 class="h2 fw-bold mb-1">Inventory</h1>
    <p class="text-muted small mb-0">Placeholder — wire persistence later.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-arrow-down-circle me-1"></i> Receive stock
        </div>
        <div class="card-body p-4">
          <form method="post" action="<?= htmlspecialchars($base) ?>/inventory/receive">
            <div class="mb-3">
              <label class="form-label fw-semibold">Product</label>
              <select class="form-select" name="product_id" required>
                <option value="">Choose…</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name']) ?> — <?= (int)($p['stock_qty'] ?? 0) ?> on hand</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Quantity</label>
              <input class="form-control" type="number" name="quantity_received" min="1" required value="1">
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Receipt date</label>
              <input class="form-control" type="date" name="received_at" required value="<?= htmlspecialchars(date('Y-m-d')) ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2">
              <i class="bi bi-check2-circle"></i> Record receipt
            </button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-boxes me-1"></i> Levels by product
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th class="text-end">Qty on hand</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($products)): ?>
                <tr>
                  <td colspan="3" class="text-center text-muted py-5">
                    <i class="bi bi-box-seam d-block fs-2 mb-2 opacity-50"></i>
                    Nothing to show.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($products as $p): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars((string)$p['name']) ?></td>
                    <td><span class="badge rounded-pill bg-light text-dark border"><?= htmlspecialchars((string)$p['category']) ?></span></td>
                    <td class="text-end"><span class="fs-6 fw-bold"><?= (int)($p['stock_qty'] ?? 0) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
