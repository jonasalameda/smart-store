<?php
$d = $data['data'] ?? $data ?? [];
$pageTitle = $d['pageTitle'] ?? __('inventory.receive_title');
$product = $d['product'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$productId = (int) ($product['id'] ?? 0);
$productName = (string) ($product['name'] ?? '');
$productPrice = (float) ($product['price'] ?? 0);
$productUpc = (string) ($product['upc'] ?? '');
$currentStock = (int) ($product['stock_qty'] ?? 0);

// i18n for JS
$i18nData = [
  'confirmClear' => __('inventory.reception.confirm_clear'),
  'noItems' => __('inventory.reception.alert_no_items'),
  'priceSet' => __('inventory.reception.alert_price_set'),
  'startScanning' => __('inventory.reception.start_scanning'),
  'stopScanning' => __('inventory.reception.stop_scanning'),
  'noRfid' => __('checkout.alert_no_rfid'),
  'rfidFail' => __('checkout.alert_rfid_fail'),
  'removeBtn' => __('inventory.reception.remove_btn'),
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
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
  <div class="container py-4" style="max-width:900px;">
    <div class="mb-4">
      <a href="<?= htmlspecialchars($base) ?>/inventory" class="text-decoration-none text-muted small"><i class="bi bi-chevron-left"></i> <?= htmlspecialchars(__('common.back')) ?></a>
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('inventory.eyebrow')) ?></p>
      <h1 class="h2 fw-bold mb-3"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header fw-semibold text-body bg-body-secondary">
            <i class="bi bi-box-seam me-1"></i> <?= htmlspecialchars(__('inventory.reception.product_info')) ?>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label small text-muted"><?= htmlspecialchars(__('inventory.reception.product_name')) ?></label>
              <div class="fw-bold fs-5"><?= htmlspecialchars($productName) ?></div>
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted"><?= htmlspecialchars(__('inventory.reception.upc_label')) ?></label>
              <div class="font-monospace text-truncate"><?= htmlspecialchars($productUpc ?: '—') ?></div>
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted"><?= htmlspecialchars(__('inventory.reception.unit_price')) ?></label>
              <div class="fs-5 fw-bold">$<?= htmlspecialchars(number_format($productPrice, 2)) ?></div>
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted"><?= htmlspecialchars(__('inventory.reception.current_stock')) ?></label>
              <div class="fs-5 fw-bold text-success"><?= (int) $currentStock ?></div>
            </div>
            <hr>
            <div class="mb-0">
              <label class="form-label small text-muted"><?= htmlspecialchars(__('inventory.reception.items_to_add')) ?></label>
              <div class="fs-5 fw-bold text-primary" id="itemCountDisplay">0</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Scanner + list -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header fw-semibold text-body bg-body-secondary">
            <i class="bi bi-broadcast me-1"></i> <?= htmlspecialchars(__('inventory.reception.scan_items')) ?>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-semibold"><?= htmlspecialchars(__('inventory.reception.custom_price')) ?></label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control" id="customPrice" step="0.01" min="0" placeholder="<?= htmlspecialchars(number_format($productPrice,2)) ?>">
                <button type="button" class="btn btn-outline-secondary" id="btnSetPrice"><?= htmlspecialchars(__('inventory.reception.set_btn')) ?></button>
              </div>
              <div class="form-text"><?= htmlspecialchars(__('inventory.reception.custom_price_hint')) ?></div>
            </div>

            <div class="mb-3">
              <div class="input-group">
                <!-- Use same id as checkout button so shared RFID script can bind -->
                <button type="button" class="btn btn-primary" id="btnReadRfid" data-mode="poll" data-poll-interval="2000">
                  <i class="bi bi-broadcast"></i> <?= htmlspecialchars(__('inventory.reception.start_scanning')) ?>
                </button>
                <input type="text" class="form-control font-monospace" id="epcInput" placeholder="<?= htmlspecialchars(__('inventory.reception.epc_placeholder')) ?>" autocomplete="off">
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th><?= htmlspecialchars(__('products.col_epc')) ?></th>
                    <th class="text-end"><?= htmlspecialchars(__('products.col_price')) ?></th>
                    <th class="text-end"><?= htmlspecialchars(__('inventory.reception.action')) ?></th>
                  </tr>
                </thead>
                <tbody id="itemsTableBody">
                  <tr id="emptyMessage">
                    <td colspan="4" class="text-center text-muted py-4"><?= htmlspecialchars(__('inventory.reception.no_items')) ?></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-4 d-flex gap-2">
              <button type="button" class="btn btn-outline-secondary" id="btnClear"><?= htmlspecialchars(__('inventory.reception.clear_btn')) ?></button>
              <button type="button" class="btn btn-primary" id="btnSubmit"><?= htmlspecialchars(__('inventory.reception.register_btn')) ?></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // config available to both stock-reception.js and shared RFID reader
  window.StockReceptionConfig = {
    productId: <?= (int) $productId ?>,
    basePrice: <?= (float) $productPrice ?>,
    base: <?= json_encode($base, JSON_THROW_ON_ERROR) ?>,
    i18n: <?= json_encode($i18nData, JSON_THROW_ON_ERROR) ?>
  };
</script>

<!-- stock-reception logic exposes handler expected by shared reader (checkout-rfid.js) -->
<script src="<?= hs(public_asset_href('js/stock-reception.js')) ?>"></script>
<!-- shared RFID reader (start/stop poll or single read) - same used in checkout -->
<script src="<?= hs(public_asset_href('js/checkout-rfid.js')) ?>"></script>

</body>
</html>
