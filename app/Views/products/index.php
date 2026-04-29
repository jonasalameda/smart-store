<?php
$pageTitle = $data['pageTitle'] ?? 'Products';
$current_page = 'products';
$products = $data['products'] ?? [];
$error = $data['error'] ?? null;
$lowStockCount = (int) ($data['low_stock_count'] ?? 0);
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$searchQuery = (string) ($data['search_query'] ?? '');
$searchActive = !empty($data['search_active']);
$searchNotFound = !empty($data['search_not_found']);
$lastImport = (string) ($data['last_import'] ?? '');
$count = count($products);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/sidebar.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>.p3-avatar{width:2.25rem;height:2.25rem;font-size:.75rem;border-radius:.35rem}</style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/../admin/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3 mb-4">
    <div>
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('products.catalog')) ?></p>
      <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars(__('products.title')) ?></h1>
      <p class="text-muted mb-0 small"><?= htmlspecialchars(__('products.subtitle')) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form class="d-flex flex-grow-1" method="get" action="<?= htmlspecialchars($base) ?>/products" role="search" style="max-width: min(100%, 22rem);">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" aria-hidden="true"></i></span>
          <input type="search" name="q" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="form-control border-start-0" placeholder="<?= htmlspecialchars(__('products.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" aria-label="<?= htmlspecialchars(__('products.search_aria'), ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="btn btn-outline-secondary"><?= htmlspecialchars(__('products.search_submit')) ?></button>
        </div>
      </form>
      <?php if ($searchActive): ?>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/products"><?= htmlspecialchars(__('products.search_clear')) ?></a>
      <?php endif; ?>
      <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="<?= htmlspecialchars($base) ?>/products/create">
        <i class="bi bi-plus-lg"></i> <?= htmlspecialchars(__('products.add')) ?>
      </a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($searchNotFound): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="status">
      <i class="bi bi-exclamation-circle flex-shrink-0" aria-hidden="true"></i>
      <span><?= htmlspecialchars(__('products.search_not_found')) ?></span>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('products.line_items')) ?></div>
        <div class="h4 fw-bold mb-0"><?= (int) $count ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('products.low_stock')) ?></div>
        <div class="h4 fw-bold mb-0"><?= (int) $lowStockCount ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card border-0 shadow-sm h-100 p-3">
        <div class="small text-uppercase text-muted fw-semibold"><?= htmlspecialchars(__('products.last_import')) ?></div>
        <div class="fs-5 fw-bold mb-0"><?= htmlspecialchars($lastImport !== '' ? $lastImport : '—') ?></div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?= htmlspecialchars(__('products.col_product')) ?></th>
            <th><?= htmlspecialchars(__('products.col_category')) ?></th>
            <th class="text-end"><?= htmlspecialchars(__('products.col_price')) ?></th>
            <th><?= htmlspecialchars(__('products.col_upc')) ?></th>
            <th><?= htmlspecialchars(__('products.col_epc')) ?></th>
            <th><?= htmlspecialchars(__('products.col_vendor')) ?></th>
            <th class="text-end"><?= htmlspecialchars(__('products.col_on_hand')) ?></th>
            <th class="text-end"><?= htmlspecialchars(__('products.col_actions')) ?></th>
          </tr>
        </thead>
        <!-- <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="8" class="text-muted py-5 text-center">
                <i class="bi bi-inbox d-block fs-2 mb-2 opacity-50"></i>
                <?php if ($searchNotFound): ?>
                  <?= htmlspecialchars(__('products.search_not_found')) ?>
                <?php else: ?>
                  <?= htmlspecialchars(__('products.empty')) ?>
                <?php endif; ?>
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
                $vendor = (string)($p['manufacturer'] ?? $p['producer'] ?? '');
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
                <td><?= htmlspecialchars($vendor) ?></td>
                <td class="text-end"><span class="fw-semibold"><?= (int)($p['stock_qty'] ?? 0) ?></span></td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$p['id'] ?>/history" title="<?= htmlspecialchars(__('products.view_history')) ?>"><i class="bi bi-clock-history"></i></a>
                  <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$p['id'] ?>/edit" title="<?= htmlspecialchars(__('products.form.save')) ?>"><i class="bi bi-pencil"></i></a>
                  <form class="d-inline" method="post" action="<?= htmlspecialchars($base) ?>/products/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm(<?= json_encode(__('products.delete_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody> -->
            <tbody>
          <?php
            // Group products by UPC (empty UPC grouped under '__no_upc__')
            $groups = [];
            foreach ($products as $prod) {
                $upcKey = trim((string)($prod['upc'] ?? ''));
                $key = $upcKey !== '' ? $upcKey : '__no_upc__';
                $groups[$key][] = $prod;
            }

            if (empty($products)): ?>
              <tr>
                <td colspan="8" class="text-muted py-5 text-center">
                  <i class="bi bi-inbox d-block fs-2 mb-2 opacity-50"></i>
                  <?php if ($searchNotFound): ?>
                    <?= htmlspecialchars(__('products.search_not_found')) ?>
                  <?php else: ?>
                    <?= htmlspecialchars(__('products.empty')) ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php else:
              $gi = 0;
              foreach ($groups as $upc => $items):
                $gi++;
                $first = $items[0];
                $collapseId = 'group_' . $gi;
                $displayUpc = $upc === '__no_upc__' ? '—' : htmlspecialchars($upc, ENT_QUOTES, 'UTF-8');
                $name = (string)($first['name'] ?? '');
                $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
                if (count($parts) >= 2) {
                    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                } else {
                    $initials = strtoupper(substr($parts[0] ?? '?', 0, 2));
                }
                $vendor = (string)($first['manufacturer'] ?? $first['producer'] ?? '');
              ?>
              <tr class="align-middle">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-link p-0 me-2" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId) ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($collapseId) ?>" title="Toggle variants">
                      <i class="bi bi-chevron-down"></i>
                    </button>
                    <span class="p3-avatar d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold" aria-hidden="true"><?= htmlspecialchars($initials) ?></span>
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($name) ?></div>
                      <div class="small text-muted"><?= count($items) ?> item<?= count($items) > 1 ? 's' : '' ?> in group</div>
                    </div>
                  </div>
                </td>
                <td><span class="badge rounded-pill bg-light text-dark border"><?= htmlspecialchars((string)($first['category'] ?? '—')) ?></span></td>
                <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float)($first['price'] ?? 0), 2)) ?></td>
                <td><code class="small bg-light px-1 rounded"><?= $displayUpc ?></code></td>
                <td><code class="small bg-light px-1 rounded text-truncate d-inline-block" style="max-width:8rem;"><?= htmlspecialchars((string)($first['epc'] ?? '')) ?></code></td>
                <td><?= htmlspecialchars($vendor) ?></td>
                <td class="text-end"><span class="fw-semibold"><?= (int)($first['stock_qty'] ?? 0) ?></span></td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$first['id'] ?>/history" title="<?= htmlspecialchars(__('products.view_history')) ?>"><i class="bi bi-clock-history"></i></a>
                  <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$first['id'] ?>/edit" title="<?= htmlspecialchars(__('products.form.save')) ?>"><i class="bi bi-pencil"></i></a>
                  <form class="d-inline" method="post" action="<?= htmlspecialchars($base) ?>/products/<?= (int)$first['id'] ?>/delete" onsubmit="return confirm(<?= json_encode(__('products.delete_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>

              <tr class="collapse" id="<?= htmlspecialchars($collapseId) ?>">
                <td colspan="8" class="p-2">
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <!-- <th>ID</th> -->
                          <th>EPC</th>
                          <th>Manufacturer</th>
                          <th class="text-end">Price</th>
                          <th class="text-end">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($items as $it): ?>
                          <tr>
                            <!-- <td><?= (int)($it['id'] ?? 0) ?></td> -->
                            <td><code class="font-monospace"><?= htmlspecialchars((string)($it['epc'] ?? '')) ?></code></td>
                            <td><?= htmlspecialchars((string)($it['manufacturer'] ?? $it['producer'] ?? '')) ?></td>
                            <td class="text-end"><?= (int)($it['stock_qty'] ?? 0) ?></td>
                            <td class="text-end text-nowrap">
                              <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$it['id'] ?>/history" title="<?= htmlspecialchars(__('products.view_history')) ?>"><i class="bi bi-clock-history"></i></a>
                              <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($base) ?>/products/<?= (int)$it['id'] ?>/edit" title="<?= htmlspecialchars(__('products.form.save')) ?>"><i class="bi bi-pencil"></i></a>
                              <form class="d-inline" method="post" action="<?= htmlspecialchars($base) ?>/products/<?= (int)$it['id'] ?>/delete" onsubmit="return confirm(<?= json_encode(__('products.delete_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            <?php endforeach;
            endif;
          ?>
        </tbody>
    </table>
    </div>
  </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
