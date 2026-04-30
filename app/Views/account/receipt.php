<?php
$pageTitle = $data['pageTitle'] ?? __('receipt_page.title');
$current_page = $data['current_page'] ?? 'account_receipt';
$error = $data['error'] ?? null;
$detail = $data['detail'] ?? null;
$account = $data['account'] ?? [];
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';

$purchase = is_array($detail) ? ($detail['purchase'] ?? null) : null;
$items = is_array($detail) ? ($detail['items'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/../common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php include __DIR__ . '/../common/theme_stylesheet.php'; ?>
</head>
<body class="bg-light customer-shell">
<?php include __DIR__ . '/../customer/header.php'; ?>
<?php include __DIR__ . '/../common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4" style="max-width:720px;">
    <div class="mb-3">
      <a href="<?= htmlspecialchars($base) ?>/account" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars(__('receipt_page.back_account')) ?></a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!is_array($purchase)): ?>
      <div class="alert alert-secondary"><?= htmlspecialchars(__('receipt_page.unavailable')) ?></div>
    <?php else: ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 border-bottom pb-3 mb-4">
            <div>
              <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('checkout.smart_store')) ?></p>
              <h1 class="h4 fw-bold mb-0"><?= htmlspecialchars(__('receipt_page.heading')) ?></h1>
              <p class="text-muted small mb-0 font-monospace">#<?= (int)($purchase['id'] ?? 0) ?></p>
            </div>
            <div class="text-md-end">
              <div class="small text-muted"><?= htmlspecialchars(__('common.date_time')) ?></div>
              <div class="fw-semibold"><?= htmlspecialchars(format_ui_datetime((string)($purchase['purchased_at'] ?? ''))) ?></div>
            </div>
          </div>

          <div class="mb-4">
            <div class="small text-muted text-uppercase fw-semibold mb-1"><?= htmlspecialchars(__('receipt_page.member')) ?></div>
            <div><?= htmlspecialchars(trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''))) ?></div>
            <div class="small text-muted font-monospace"><?= htmlspecialchars((string)($account['membership_number'] ?? '')) ?></div>
          </div>

          <div class="table-responsive mb-4">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th><?= htmlspecialchars(__('receipt_page.col_product')) ?></th>
                  <th class="text-center"><?= htmlspecialchars(__('receipt_page.col_qty')) ?></th>
                  <th class="text-end"><?= htmlspecialchars(__('receipt_page.col_price')) ?></th>
                  <th class="text-end"><?= htmlspecialchars(__('receipt_page.col_line')) ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($items)): ?>
                  <tr>
                    <td colspan="4" class="text-muted text-center py-4"><?= htmlspecialchars(__('receipt_page.no_lines')) ?></td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($items as $line): ?>
                    <tr>
                      <td><?= htmlspecialchars((string)($line['product_name'] ?? '')) ?></td>
                      <td class="text-center"><?= (int)($line['quantity'] ?? 0) ?></td>
                      <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float)($line['unit_price'] ?? 0), 2)) ?></td>
                      <td class="text-end font-monospace"><?= htmlspecialchars(number_format((float)($line['line_total'] ?? 0), 2)) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <div class="p-3 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                <div class="small text-muted text-uppercase fw-semibold"><?= htmlspecialchars(__('receipt_page.points_earned')) ?></div>
                <div class="fs-4 fw-bold text-primary"><?= (int)($purchase['points_earned'] ?? 0) ?> <?= htmlspecialchars(__('receipt_page.pts_suffix')) ?></div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 rounded bg-light border">
                <div class="small text-muted text-uppercase fw-semibold"><?= htmlspecialchars(__('receipt_page.total_paid')) ?></div>
                <div class="fs-4 fw-bold font-monospace"><?= htmlspecialchars(number_format((float)($purchase['total_amount'] ?? 0), 2)) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
