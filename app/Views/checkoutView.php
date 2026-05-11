<?php
/**
 * Phase 3 self-checkout: RFID / UPC scan simulation, cart, optional membership, payment simulation.
 */
// $scriptPath = APP_BASE_DIR_PATH . '/public/assets/python/ContinuousReader_ChafonUHF.py';

// shell_exec("python3 " . escapeshellarg($scriptPath));

$d = $data['data'] ?? $data ?? [];
$pageTitle = $d['title'] ?? __('checkout.title');
$current_page = 'checkout';
$base = defined('APP_BASE_URL') ? APP_BASE_URL : '';
$productsJson = $d['products_json'] ?? '[]';
$customerId = isset($d['customer_id']) ? (int) $d['customer_id'] : null;
$customerPoints = isset($d['customer_points']) ? (int) $d['customer_points'] : 0;
$error = $d['error'] ?? null;
$success = $d['success'] ?? null;
$purchaseId = isset($d['purchase_id']) ? (int) $d['purchase_id'] : null;
$total = isset($d['total']) ? (float) $d['total'] : null;
$points = isset($d['points']) ? (int) $d['points'] : null;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
  <?php include __DIR__ . '/common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <?php include __DIR__ . '/common/theme_stylesheet.php'; ?>
</head>
<body class="bg-light customer-shell">
<?php include __DIR__ . '/customer/header.php'; ?>
<?php include __DIR__ . '/common/flash.php'; ?>
<main class="main-content">
  <div class="container py-4" style="max-width:960px;">
    <div class="mb-4">
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('checkout.smart_store')) ?></p>
      <h1 class="h2 fw-bold mb-1"><?= htmlspecialchars(__('checkout.title')) ?></h1>
      <p class="text-muted small mb-0"><?= htmlspecialchars(__('checkout.subtitle')) ?></p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <?php if ($purchaseId): ?>
          <div class="small mt-2"><?= htmlspecialchars(__('checkout.receipt_line')) ?> #<?= (int) $purchaseId ?><?php if ($total !== null): ?> · <?= htmlspecialchars(number_format($total, 2)) ?><?php endif; ?><?php if ($points !== null && $points > 0): ?> · +<?= (int) $points ?> pts<?php endif; ?></div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= htmlspecialchars(__('common.close')) ?>"></button>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="input-group mb-3">
        <button type="button" class="btn btn-primary" id="btnReadRfid">
          <i class="bi bi-broadcast"></i> <?= htmlspecialchars(__('inventory.reception.start_scanning')) ?>
        </button>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header fw-semibold text-body bg-body-secondary d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cart3 me-1"></i> <?= htmlspecialchars(__('checkout.cart')) ?></span>
            <span class="font-monospace fw-bold" id="cartTotalDisplay">$0.00</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= htmlspecialchars(__('checkout.col_product')) ?></th>
                  <th>EPC</th>
                  <th class="text-end"><?= htmlspecialchars(__('checkout.col_each')) ?></th>
                  <th class="text-end"><?= htmlspecialchars(__('checkout.col_line')) ?></th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="cartBody">
                <tr id="cartEmptyRow">
                  <td colspan="6" class="text-center text-muted py-5"><?= htmlspecialchars(__('checkout.cart_empty')) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header fw-semibold text-body bg-body-secondary"><i class="bi bi-credit-card me-1"></i> <?= htmlspecialchars(__('checkout.payment')) ?></div>
          <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($base) ?>/checkout" id="checkoutForm">
              <input type="hidden" name="items" id="itemsPayload" value="[]">
              <?php if ($customerId): ?>
                <input type="hidden" name="customer_id" value="<?= (int) $customerId ?>">
              <?php else: ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold"><?= htmlspecialchars(__('checkout.membership_optional')) ?></label>
                  <input type="text" class="form-control font-monospace" name="membership_number" placeholder="<?= htmlspecialchars(__('checkout.membership_placeholder')) ?>" autocomplete="off">
                  <div class="form-text"><?= htmlspecialchars(__('checkout.guest_hint')) ?></div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold"><?= htmlspecialchars(__('checkout.guest_receipt_email')) ?></label>
                  <input type="email" class="form-control" name="guest_receipt_email" placeholder="<?= htmlspecialchars(__('checkout.guest_receipt_placeholder')) ?>" autocomplete="email">
                  <div class="form-text"><?= htmlspecialchars(__('checkout.guest_receipt_help')) ?></div>
                </div>
              <?php endif; ?>
              <div class="mb-3">
                <label class="form-label fw-semibold"><?= htmlspecialchars(__('checkout.payment_method')) ?></label>
                <select class="form-select" name="payment_method">
                  <option value="credit_card"><?= htmlspecialchars(__('checkout.pay_credit')) ?></option>
                  <option value="debit_card"><?= htmlspecialchars(__('checkout.pay_debit')) ?></option>
                  <option value="mobile_pay"><?= htmlspecialchars(__('checkout.pay_mobile')) ?></option>
                  <option value="cash"><?= htmlspecialchars(__('checkout.pay_cash')) ?></option>
                </select>
              </div>
              <?php if ($customerId && $customerPoints >= 10): ?>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="applyDiscount" name="apply_discount" value="1">
                  <label class="form-check-label fw-semibold" for="applyDiscount">
                    <?= htmlspecialchars(__('checkout.apply_discount')) ?> (10% off - 10 pts)
                  </label>
                  <div class="form-text"><?= htmlspecialchars(__('checkout.discount_help')) ?></div>
                </div>
              </div>
              <?php endif; ?>
              <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2" id="btnPay">
                <i class="bi bi-check2-circle"></i> <?= htmlspecialchars(__('checkout.confirm')) ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<script>
window.CheckoutConfig = {
  base: <?= json_encode($base, JSON_THROW_ON_ERROR) ?>,
  catalog: <?= $productsJson ?>,
  MSG: {
    cartEmpty: <?= json_encode(__('checkout.cart_empty'), JSON_THROW_ON_ERROR) ?>,
    remove: <?= json_encode(__('checkout.remove'), JSON_THROW_ON_ERROR) ?>,
    noUpc: <?= json_encode(__('checkout.alert_no_upc'), JSON_THROW_ON_ERROR) ?>,
    noEpc: <?= json_encode(__('checkout.alert_no_epc'), JSON_THROW_ON_ERROR) ?>,
    lookupFail: <?= json_encode(__('checkout.alert_lookup_fail'), JSON_THROW_ON_ERROR) ?>,
    noRfid: <?= json_encode(__('checkout.alert_no_rfid'), JSON_THROW_ON_ERROR) ?>,
    rfidFail: <?= json_encode(__('checkout.alert_rfid_fail'), JSON_THROW_ON_ERROR) ?>,
    outOfStock: <?= json_encode(__('checkout.alert_out_of_stock'), JSON_THROW_ON_ERROR) ?>,
    alreadyInCart: <?= json_encode(__('checkout.alert_already_in_cart'), JSON_THROW_ON_ERROR) ?>,
    overStock: <?= json_encode(__('checkout.alert_over_stock'), JSON_THROW_ON_ERROR) ?>,
    discountApplied: <?= json_encode(__('checkout.discount_applied'), JSON_THROW_ON_ERROR) ?>,
    discountRemoved: <?= json_encode(__('checkout.discount_removed'), JSON_THROW_ON_ERROR) ?>
  },
  i18n: {
    startScanning: <?= json_encode(__('inventory.reception.start_scanning'), JSON_THROW_ON_ERROR) ?>,
    stopScanning: <?= json_encode(__('inventory.reception.stop_scanning'), JSON_THROW_ON_ERROR) ?>,
    noRfid: <?= json_encode(__('checkout.alert_no_rfid'), JSON_THROW_ON_ERROR) ?>,
    rfidFail: <?= json_encode(__('checkout.alert_rfid_fail'), JSON_THROW_ON_ERROR) ?>
  }
};
</script>

<script src="<?= hs(public_asset_href('js/checkout.js')) ?>"></script>
<script src="<?= hs(public_asset_href('js/checkout-rfid.js')) ?>"></script>

</body>
</html>
