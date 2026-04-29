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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
      <!-- <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white fw-semibold"><i class="bi bi-upc-scan me-1"></i> <?= htmlspecialchars(__('checkout.add_items')) ?></div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-semibold"><?= htmlspecialchars(__('checkout.upc')) ?></label>
              <div class="input-group">
                <input type="text" class="form-control font-monospace" id="upcInput" maxlength="13" placeholder="<?= htmlspecialchars(__('checkout.upc_placeholder')) ?>" autocomplete="off">
                <button type="button" class="btn btn-outline-primary" id="btnAddUpc"><?= htmlspecialchars(__('checkout.add_btn')) ?></button>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold"><?= htmlspecialchars(__('checkout.epc')) ?></label>
              <div class="input-group">
                <input type="text" class="form-control font-monospace" id="epcInput" maxlength="24" placeholder="<?= htmlspecialchars(__('checkout.epc_placeholder')) ?>" autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" id="btnReadRfid"><?= htmlspecialchars(__('checkout.read_rfid')) ?></button>
              </div>
              <div class="form-text"><?= htmlspecialchars(__('checkout.epc_help')) ?></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearCart"><?= htmlspecialchars(__('checkout.clear_cart')) ?></button>
          </div>
        </div>
      </div> -->
      <!-- <div class="input-group">
        <button type="button" class="btn btn-outline-secondary" id="btnReadRfid"><?= htmlspecialchars(__('checkout.read_rfid')) ?></button>
      </div> -->
      <div class="input-group mb-3">
          <button type="button" class="btn btn-outline-secondary" id="btnReadRfid">
              <i class="bi bi-broadcast"></i> <?= htmlspecialchars(__('checkout.read_rfid')) ?>
          </button>
      </div>
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cart3 me-1"></i> <?= htmlspecialchars(__('checkout.cart')) ?></span>
            <span class="font-monospace fw-bold" id="cartTotalDisplay">$0.00</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= htmlspecialchars(__('checkout.col_product')) ?></th>
                  <th>EPC</th>
                  <th class="text-center"><?= htmlspecialchars(__('checkout.col_qty')) ?></th>
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
          <div class="card-header bg-white fw-semibold"><i class="bi bi-credit-card me-1"></i> <?= htmlspecialchars(__('checkout.payment')) ?></div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(function () {
  var MSG = {
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
  };
  var catalog = <?= $productsJson ?>;
  var byUpc = {};
  var byEpc = {};
  catalog.forEach(function (p) {
    if (p.upc) byUpc[String(p.upc)] = p;
    if (p.epc) byEpc[String(p.epc).toUpperCase()] = p;
  });

  var cart = {};

  function money(n) {
    return '$' + Number(n).toFixed(2);
  }

  function cartLines() {
    return Object.keys(cart).map(function (id) {
      var row = cart[id];
      return { product_id: Number(id), quantity: row.qty };
    });
  }

  function renderCart() {
    var tbody = document.getElementById('cartBody');
    var emptyRow = document.getElementById('cartEmptyRow');
    var ids = Object.keys(cart);
    var subtotal = 0;
    tbody.querySelectorAll('tr[data-line]').forEach(function (r) { r.remove(); });

    if (ids.length === 0) {
      emptyRow.style.display = '';
      emptyRow.querySelector('td').textContent = MSG.cartEmpty;
      document.getElementById('cartTotalDisplay').textContent = '$0.00';
      document.getElementById('itemsPayload').value = '[]';
      return;
    }
    emptyRow.style.display = 'none';

    ids.forEach(function (id) {
      var row = cart[id];
      var line = row.price * row.qty;
      subtotal += line;
      var tr = document.createElement('tr');
      tr.setAttribute('data-line', id);
      tr.innerHTML =
        '<td class="fw-semibold">' + escapeHtml(row.name) + '</td>' +
        '<td class="text-center font-monospace">' + (row.epc ? escapeHtml(row.epc) : '-') + '</td>' +
        '<td class="text-center"><div class="btn-group btn-group-sm">' +
        '<button type="button" class="btn btn-outline-secondary" data-dec="' + id + '">−</button>' +
        '<span class="btn btn-light disabled">' + row.qty + '</span>' +
        '<button type="button" class="btn btn-outline-secondary" data-inc="' + id + '">+</button>' +
        '</div></td>' +
        '<td class="text-end font-monospace">' + money(row.price) + '</td>' +
        '<td class="text-end font-monospace">' + money(line) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + id + '">' + escapeHtml(MSG.remove) + '</button></td>';
      tbody.appendChild(tr);
    });

    var discountCheckbox = document.getElementById('applyDiscount');
    var total = subtotal;
    if (discountCheckbox && discountCheckbox.checked) {
      total = subtotal * 0.9;
    }

    document.getElementById('cartTotalDisplay').textContent = money(total);
    document.getElementById('itemsPayload').value = JSON.stringify(cartLines());
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function addProduct(p) {
    if (!p || !p.id) return;
    var id = String(p.id);
    if (cart[id]) return;
    var stock = Number(p.stock_qty || 0);
    if (stock <= 0) return;
    cart[id] = { name: p.name, price: Number(p.price), qty: 1, epc: p.epc || null, stock: stock };
    renderCart();
  }

  function fetchJson(url) {
    return fetch(url).then(function (r) {
      if (!r.ok) throw new Error('Request failed');
      return r.json();
    });
  }

  function addByUpc(raw) {
    var upc = String(raw || '').trim();
    if (!upc) return;
    var p = byUpc[upc];
    if (p) {
      addProduct(p);
    }
  }

  function addByEpc(raw) {
    var epc = String(raw || '').trim();
    if (!epc) return Promise.resolve();

    var parts = epc.split(/[\s,;]+/).filter(Boolean);
    var tasks = parts.map(function (chunk) { return addByEpcSingle(chunk); });

    return Promise.all(tasks).then(function (results) {
      var errors = results.filter(Boolean);
      if (errors.length > 0) {
        alert(errors.join('\n'));
      }
    });
  }

  function addByEpcSingle(epc) {
    var key = epc.toUpperCase();
    var p = byEpc[key];
    if (!p) {
      return Promise.resolve(null);
    }
    addProduct(p);
    return Promise.resolve(null);
  }

  // TODO: uncomment when the UPC input panel is re-enabled in the HTML above
  // document.getElementById('btnAddUpc').addEventListener('click', function () {
  //   var el = document.getElementById('upcInput');
  //   addByUpc(el.value);
  //   el.value = '';
  //   el.focus();
  // });
  // document.getElementById('upcInput').addEventListener('keydown', function (e) {
  //   if (e.key === 'Enter') {
  //     e.preventDefault();
  //     document.getElementById('btnAddUpc').click();
  //   }
  // });

  // ── RFID scan via one-time read ───────────────────────────────
  document.getElementById('btnReadRfid').addEventListener('click', async function () {
    var btnRead = document.getElementById('btnReadRfid');
    var originalHtml = btnRead.innerHTML;
    btnRead.disabled = true;
    btnRead.innerHTML = '…';

    try {
      var data = await fetchJson('<?= htmlspecialchars($base) ?>/api/products/read-rfid');
      if (data && data.epc) {
        await addByEpc(data.epc);
      } else {
        alert(MSG.noRfid);
      }
    } catch (error) {
      alert(MSG.rfidFail);
    } finally {
      btnRead.disabled = false;
      btnRead.innerHTML = originalHtml;
    }
  });

  // TODO: uncomment when the EPC text input is re-enabled in the HTML above
  // document.getElementById('epcInput').addEventListener('keydown', function (e) {
  //   if (e.key === 'Enter') {
  //     e.preventDefault();
  //     addByEpc(this.value);
  //     this.value = '';
  //   }
  // });

  // TODO: uncomment when the clear cart button is re-enabled in the HTML above
  // document.getElementById('btnClearCart').addEventListener('click', function () {
  //   cart = {};
  //   renderCart();
  // });

  document.getElementById('cartBody').addEventListener('click', function (e) {
    var t = e.target;
    if (t.getAttribute('data-inc')) {
      var id = t.getAttribute('data-inc');
      if (cart[id]) {
        if (cart[id].qty < cart[id].stock) {
          cart[id].qty += 1;
        } else {
          alert(MSG.overStock);
        }
      }
      renderCart();
    } else if (t.getAttribute('data-dec')) {
      var id2 = t.getAttribute('data-dec');
      if (cart[id2]) {
        cart[id2].qty -= 1;
        if (cart[id2].qty <= 0) delete cart[id2];
      }
      renderCart();
    } else if (t.getAttribute('data-remove')) {
      delete cart[t.getAttribute('data-remove')];
      renderCart();
    }
  });

  document.getElementById('checkoutForm').addEventListener('submit', function (e) {
    var errors = [];
    Object.keys(cart).forEach(function (id) {
      var item = cart[id];
      if (item.qty > item.stock) {
        errors.push(item.name + ' - only ' + item.stock + ' left in stock');
      }
    });
    if (errors.length > 0) {
      e.preventDefault();
      alert(errors.join('\n'));
      return;
    }
    document.getElementById('itemsPayload').value = JSON.stringify(cartLines());
  });

  var discountCheckbox = document.getElementById('applyDiscount');
  if (discountCheckbox) {
    discountCheckbox.addEventListener('change', function () {
      renderCart();
      if (this.checked) {
        alert(MSG.discountApplied);
      } else {
        alert(MSG.discountRemoved);
      }
    });
  }
})();
</script>
</body>
</html>
