<?php
$pageTitle = $data['pageTitle'] ?? 'Product';
$current_page = 'products';
$product = $data['product'] ?? null;
$p = is_array($product) ? $product : [];
$error = $data['error'] ?? null;
$isEdit = !empty($p['id']);
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
<?php include __DIR__ . '/../admin/header.php'; ?>
<main class="main-content">
  <div class="container py-4" style="max-width:720px;">
  <div class="mb-4">
    <p class="small text-uppercase text-muted fw-semibold mb-0"><?= $isEdit ? 'Edit' : 'Create' ?></p>
    <h1 class="h2 fw-bold"><?= htmlspecialchars($pageTitle) ?></h1>
    <p class="text-muted small mb-0">*For stock update, go to inventory page</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="post" action="<?= $isEdit ? htmlspecialchars($base . '/products/' . (int)$p['id']) : htmlspecialchars($base . '/products') ?>">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Display name *</label>
            <input class="form-control form-control-lg" name="name" required placeholder="e.g. Organic oat milk 1L" value="<?= htmlspecialchars((string)($p['name'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Category</label>
            <input class="form-control" name="category" placeholder="e.g. Dairy alternatives" value="<?= htmlspecialchars((string)($p['category'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Unit price *</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input class="form-control" type="number" step="0.01" min="0" name="price" required value="<?= htmlspecialchars((string)($p['price'] ?? '')) ?>">
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold">Producer / vendor</label>
            <input class="form-control" name="producer" placeholder="Supplier name" value="<?= htmlspecialchars((string)($p['producer'] ?? '')) ?>">
          </div>
        </div>
        <br>

        <!-- <h6 class="text-uppercase text-muted border-bottom pb-2 mb-3 mt-4">Identifiers</h6> -->
        <div class="row g-3">
          <div class="col-md-6">
            <!-- <label class="form-label fw-semibold">UPC <span class="text-muted fw-normal">(max 13)</span></label>
            <input class="form-control font-monospace" name="upc" maxlength="13" placeholder="0001234567890" value="<?= htmlspecialchars((string)($p['upc'] ?? '')) ?>">
          </div> -->
          <div class="col-md-6">
            <label class="form-label fw-semibold">EPC <span class="text-muted fw-normal"></span></label>
            <div class="input-group">
              <input class="form-control font-monospace" name="epc" id="epc" maxlength="24" placeholder="RFID hex" value="<?= htmlspecialchars((string)($p['epc'] ?? '')) ?>">
              <button type="button" class="btn btn-outline-secondary" id="readRfidBtn">Read RFID</button>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
          <button type="submit" class="btn btn-primary px-4"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
          <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($base) ?>/products">Cancel</a>
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
    btn.textContent = 'Reading...';
    try {
        const response = await fetch('<?= htmlspecialchars($base) ?>/api/products/read-rfid');
        if (!response.ok) {
            throw new Error('Failed to read RFID');
        }
        const data = await response.json();
        if (data.epc) {
            epcInput.value = data.epc;
        } else {
            alert('No RFID tag detected.');
        }
    } catch (error) {
        alert('Error reading RFID: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Read RFID';
    }
});
</script>
</body>
</html>
