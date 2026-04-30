<?php
$pageTitle = $data['pageTitle'] ?? 'Product';
$current_page = 'products';
$product = $data['product'] ?? null;
$categories = $data['categories'] ?? [];
$p = is_array($product) ? $product : [];
$error = $data['error'] ?? null;
$isEdit = !empty($p['id']);
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$scriptPath = APP_BASE_DIR_PATH . '/public/assets/python/ContinuousReader_ChafonUHF.py';

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/sidebar.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../admin/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4" style="max-width:720px;">
  <div class="mb-4">
    <p class="small text-uppercase text-muted fw-semibold mb-0"><?= $isEdit ? htmlspecialchars(__('products.form.edit_title')) : htmlspecialchars(__('products.form.add_title')) ?></p>
    <h1 class="h2 fw-bold"><?= htmlspecialchars($pageTitle) ?></h1>
    <p class="text-muted small mb-0"><?= htmlspecialchars(__('products.form.stock_hint')) ?></p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="post" action="<?= $isEdit ? htmlspecialchars($base . '/products/' . (int)$p['id']) : htmlspecialchars($base . '/products') ?>">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('products.form.name')) ?> *</label>
            <input class="form-control form-control-lg" name="name" required placeholder="<?= htmlspecialchars(__('products.form.placeholder_name')) ?>" value="<?= htmlspecialchars((string)($p['name'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('products.form.category')) ?></label>
            <select class="form-select" name="category_id">
              <option value="">-- <?= htmlspecialchars(__('products.form.placeholder_category')) ?> --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars((string)$cat['id']) ?>" <?= ((int)($p['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)$cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Or type a new category below to create it.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">New category (optional)</label>
            <input class="form-control" name="new_category" placeholder="e.g. Frozen Foods" value="<?= htmlspecialchars((string)($p['new_category'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('products.form.price')) ?> *</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input class="form-control" type="number" step="0.01" min="0" name="price" required value="<?= htmlspecialchars((string)($p['price'] ?? '')) ?>">
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('products.form.vendor')) ?></label>
            <input class="form-control" name="producer" placeholder="<?= htmlspecialchars(__('products.form.placeholder_vendor')) ?>" value="<?= htmlspecialchars((string)($p['producer'] ?? '')) ?>">
          </div>
        </div>
        <br>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('products.col_upc')) ?></label>
            <input class="form-control font-monospace" name="upc" maxlength="13" value="<?= htmlspecialchars((string)($p['upc'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold"><?= htmlspecialchars(__('products.form.epc')) ?></label>
            <div class="input-group">
              <input class="form-control font-monospace" name="epc" id="epc" maxlength="24" placeholder="<?= htmlspecialchars(__('products.form.placeholder_epc')) ?>" value="<?= htmlspecialchars((string)($p['epc'] ?? '')) ?>">
              <button type="button" class="btn btn-outline-secondary" id="readRfidBtn"><?= htmlspecialchars(__('checkout.read_rfid')) ?></button>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
          <button type="submit" class="btn btn-primary px-4"><?= $isEdit ? htmlspecialchars(__('products.form.save')) : htmlspecialchars(__('products.form.create')) ?></button>
          <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/products"><?= htmlspecialchars(__('products.form.cancel')) ?></a>
        </div>
      </form>
    </div>
  </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
document.getElementById('readRfidBtn').addEventListener('click', async function() {
    const btn = this;
    const epcInput = document.getElementById('epc');
    btn.disabled = true;
    btn.textContent = '…';
    try {
        const response = await fetch('<?= htmlspecialchars($base) ?>/api/products/read-rfid');
        if (!response.ok) throw new Error('RFID');
        const data = await response.json();
        if (data.epc) epcInput.value = data.epc;
        else alert(<?= json_encode(__('checkout.alert_no_rfid'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
    } catch (error) {
        alert(<?= json_encode(__('checkout.alert_rfid_fail'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?> + (error && error.message ? ' ' + error.message : ''));
    } finally {
        btn.disabled = false;
        btn.textContent = <?= json_encode(__('checkout.read_rfid'), JSON_THROW_ON_ERROR) ?>;
    }
});
</script>
</body>
</html>
