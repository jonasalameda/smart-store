<?php
// Unused while /rfid/products routes and ProductController::rfidProducts are disabled.
$data = $data ?? [];
$current_page = $data['current_page'] ?? 'rfid';
$title = $data['title'] ?? __('nav.rfid_products');
$rfid = $data['rfid'] ?? '';
$products = $data['products'] ?? [];
$used_placeholder = !empty($data['used_placeholder']);
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= hs($title) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/rfid-products.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php include __DIR__ . '/common/theme_stylesheet.php'; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
</head>
<body class="customer-shell rfid-page">

<?php include __DIR__ . '/customer/header.php'; ?>
<?php include __DIR__ . '/common/flash.php'; ?>

<main class="main-content rfid-main">
  <section class="rfid-hero" aria-labelledby="rfid-heading">
    <h1 id="rfid-heading"><?= htmlspecialchars(__('rfid.hero_title')) ?></h1>
    <p><?= htmlspecialchars(__('rfid.hero_intro')) ?></p>
    <div class="rfid-chip" title="<?= htmlspecialchars(__('checkout.epc')) ?>">
      <span class="label"><?= htmlspecialchars(__('rfid.tag_label')) ?></span>
      <span><?= hs($rfid) ?></span>
    </div>
    <?php if ($used_placeholder): ?>
      <p class="rfid-placeholder-note" role="status">
        <strong><?= htmlspecialchars(__('rfid.demo_title')) ?></strong> <?= htmlspecialchars(__('rfid.demo_body')) ?>
      </p>
    <?php endif; ?>
  </section>

  <?php if (count($products) === 0): ?>
    <div class="rfid-empty">
      <strong><?= htmlspecialchars(__('rfid.empty_title')) ?></strong>
      <?= htmlspecialchars(__('rfid.empty_body')) ?>
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
              <span><?= htmlspecialchars(__('rfid.no_image')) ?></span>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
