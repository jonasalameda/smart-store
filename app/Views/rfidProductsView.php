<?php
$current_page = 'rfid';
$data = $data ?? [];
$title = $data['title'] ?? 'RFID products';
$rfid = $data['rfid'] ?? '';
$products = $data['products'] ?? [];
$used_placeholder = !empty($data['used_placeholder']);
$assets_base = defined('APP_ASSETS_DIR_URL') ? APP_ASSETS_DIR_URL : (defined('APP_BASE_URL') ? APP_BASE_URL . '/public/assets' : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= hs($title) ?></title>
  <link rel="stylesheet" href="<?= hs($assets_base) ?>/css/layout/sidebar.css">
  <link rel="stylesheet" href="<?= hs($assets_base) ?>/css/rfid-products.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'DM Sans', system-ui, sans-serif; background: #0f1018; color: #e8eaf0; }
  </style>
</head>
<body class="rfid-page">

<?php include __DIR__ . '/admin/header.php'; ?>

<main class="main-content rfid-main">
  <section class="rfid-hero" aria-labelledby="rfid-heading">
    <h1 id="rfid-heading">Shelf scan</h1>
    <p>Products linked to the RFID tag read at the fixture. When the external reader is connected, this page will receive the live tag ID; for now a placeholder tag is used.</p>
    <div class="rfid-chip" title="Electronic Product Code (EPC) used as RFID payload">
      <span class="label">Tag</span>
      <span><?= hs($rfid) ?></span>
    </div>
    <?php if ($used_placeholder): ?>
      <p class="rfid-placeholder-note" role="status">
        <strong>Demo mode:</strong> no RFID query was passed, so the app uses the built-in placeholder EPC. Add <code>?rfid=YOUR_EPC</code> or open <code>/rfid/products/{epc}</code> to test other tags.
      </p>
    <?php endif; ?>
  </section>

  <?php if (count($products) === 0): ?>
    <div class="rfid-empty">
      <strong>No product for this tag</strong>
      There is no row in <code>PRODUCT</code> with a matching <code>epc</code> value.
    </div>
  <?php else: ?>
    <div class="rfid-grid">
      <?php foreach ($products as $p): ?>
        <article class="rfid-card">
          <div class="rfid-card-visual">
            <?php
            $img = isset($p['image_url']) ? trim((string) $p['image_url']) : '';
            if ($img !== '' && str_starts_with($img, 'http')): ?>
              <img src="<?= hs($img) ?>" alt="" loading="lazy" width="320" height="200">
            <?php else: ?>
              <span>No image</span>
            <?php endif; ?>
          </div>
          <div class="rfid-card-body">
            <h2><?= hs((string) ($p['name'] ?? '')) ?></h2>
            <div class="rfid-meta">
              <?= hs((string) ($p['category'] ?? '')) ?>
              <?php if (!empty($p['manufacturer'])): ?>
                · <?= hs((string) $p['manufacturer']) ?>
              <?php endif; ?>
            </div>
            <div class="rfid-price-row">
              <span class="rfid-price"><?= hs(number_format((float) ($p['price'] ?? 0), 2)) ?> $</span>
              <?php if (isset($p['current_stock'])): ?>
                <span class="rfid-stock">Stock <?= hs((string) (int) $p['current_stock']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
