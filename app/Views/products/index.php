<?php
$pageTitle = $data['pageTitle'] ?? 'Products';
$current_page = 'products';
$products = $data['products'] ?? [];
$error = $data['error'] ?? null;
$success = $data['success'] ?? null;
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$count = count($products);
$placeholderLowStock = 0;
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
  <style>.p3-avatar{width:2.25rem;height:2.25rem;font-size:.75rem;border-radius:.35rem}</style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../common/header.php'; ?>
<main class="main-content">
  <div class="container py-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3 mb-4">
    <div>
      <p class="small text-uppercase text-muted fw-semibold mb-1">Catalog</p>
      <h1 class="h2 fw-bold mb-1">Products</h1>
      <p class="text-muted mb-0 small">Placeholder copy — swap when final.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <div class="input-group d-none d-md-flex" style="max-width:280px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="search" class="form-control border-start-0" placeholder="Search… (later)" disabled aria-disabled="true">
      </div>
      <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="<?= htmlspecialchars($base) ?>/products/create">
        <i class="bi bi-plus-lg"></i> Add product
      </a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold">Line items</div>
        <div class="h4 fw-bold mb-0"><?= (int) $count ?></div>
        <div class="small text-muted">Placeholder metric</div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold">Low stock (demo)</div>
        <div class="h4 fw-bold mb-0"><?= (int) $placeholderLowStock ?></div>
        <div class="small text-muted">Wire threshold later</div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold">Last import</div>
        <div class="fs-5 fw-bold mb-0">—</div>
        <div class="small text-muted">Optional</div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th class="text-end">Price</th>
            <th>UPC</th>
            <th>EPC</th>
            <th>Vendor</th>
            <th class="text-end">On hand</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="8" class="text-muted py-5 text-center">
                <i class="bi bi-inbox d-block fs-2 mb-2 opacity-50"></i>
                No rows yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
              <?php
                $name = (string)($p['name'] ?? '');
                $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
                if (count($parts) >= 2) {
                    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                } else {
                    $initials = strtoupper(substr($parts[0] ?? '?', 0, 2));
                }
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="p3-avatar d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
                    <span class="fw-semibold"><?= htmlspecialchars($name) ?></span>
                  </div>
                </td>
                <td><span class="badge rounded-pill bg-light text-dark border"><?= htmlspecialchars((string)($p['category'] ?? '—')) ?></span></td>
                <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float)($p['price'] ?? 0), 2)) ?></td>
                <td><code class="small bg-light px-1 rounded"><?= htmlspecialchars((string)($p['upc'] ?? '')) ?></code></td>
                <td><code class="small bg-light px-1 rounded text-truncate d-inline-block" style="max-width:8rem;"><?= htmlspecialchars((string)($p['epc'] ?? '')) ?></code></td>
                <td><?= htmlspecialchars((string)($p['producer'] ?? '')) ?></td>
                <td class="text-end"><span class="fw-semibold"><?= (int)($p['stock_qty'] ?? 0) ?></span></td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$p['id'] ?>/edit" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form class="d-inline" method="post" action="<?= htmlspecialchars($base) ?>/products/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('Remove this row?');">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
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
</body>
</html>
