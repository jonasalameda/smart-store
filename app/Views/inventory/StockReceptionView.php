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
  <div class="container py-4" style="max-width:900px;">
    <div class="mb-4">
      <a href="<?= htmlspecialchars($base) ?>/inventory" class="text-decoration-none text-muted small"><i class="bi bi-chevron-left"></i> <?= htmlspecialchars(__('common.back')) ?></a>
      <p class="small text-uppercase text-muted fw-semibold mb-1"><?= htmlspecialchars(__('inventory.eyebrow')) ?></p>
      <h1 class="h2 fw-bold mb-3"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <div class="row g-4">
      <!-- Product Info Card -->
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

      <!-- RFID Scan & List -->
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
                <input type="number" class="form-control" id="customPrice" step="0.01" min="0" placeholder="<?= htmlspecialchars(number_format($productPrice, 2)) ?>" value="">
                <button type="button" class="btn btn-outline-secondary" id="btnSetPrice"><?= htmlspecialchars(__('inventory.reception.set_btn')) ?></button>
              </div>
              <div class="form-text"><?= htmlspecialchars(__('inventory.reception.custom_price_hint')) ?></div>
            </div>

            <div class="mb-3">
              <div class="input-group">
                <button type="button" class="btn btn-primary" id="btnReadRfid">
                  <i class="bi bi-broadcast"></i> <?= htmlspecialchars(__('checkout.read_rfid')) ?>
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
              <button type="button" class="btn btn-outline-secondary" id="btnClear">
                <i class="bi bi-trash"></i> <?= htmlspecialchars(__('inventory.reception.clear_btn')) ?>
              </button>
              <button type="button" class="btn btn-primary" id="btnSubmit">
                <i class="bi bi-check2-circle"></i> <?= htmlspecialchars(__('inventory.reception.register_btn')) ?>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var productId = <?= (int) $productId ?>;
  var basePrice = <?= (float) $productPrice ?>;
  var currentPrice = basePrice;
  var items = [];
  var base = <?= json_encode($base, JSON_THROW_ON_ERROR) ?>;
  var i18n = {
    money: function(n) { return '$' + Number(n).toFixed(2); },
    confirmClear: <?= json_encode(__('inventory.reception.confirm_clear'), JSON_THROW_ON_ERROR) ?>,
    noItems: <?= json_encode(__('inventory.reception.alert_no_items'), JSON_THROW_ON_ERROR) ?>,
    priceSet: <?= json_encode(__('inventory.reception.alert_price_set'), JSON_THROW_ON_ERROR) ?>,
    noRfid: <?= json_encode(__('checkout.alert_no_rfid'), JSON_THROW_ON_ERROR) ?>,
    rfidFail: <?= json_encode(__('checkout.alert_rfid_fail'), JSON_THROW_ON_ERROR) ?>
  };

  function renderItems() {
    var tbody = document.getElementById('itemsTableBody');
    var empty = document.getElementById('emptyMessage');

    tbody.querySelectorAll('tr[data-idx]').forEach(function (r) { r.remove(); });

    if (items.length === 0) {
      empty.style.display = '';
      document.getElementById('itemCountDisplay').textContent = '0';
      return;
    }
    empty.style.display = 'none';

    items.forEach(function (item, idx) {
      var tr = document.createElement('tr');
      tr.setAttribute('data-idx', idx);
      tr.innerHTML =
        '<td>' + (idx + 1) + '</td>' +
        '<td class="font-monospace small">' + escapeHtml(item.epc) + '</td>' +
        '<td class="text-end font-monospace">' + i18n.money(item.price) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '"><?= htmlspecialchars(__('inventory.reception.remove_btn')) ?></button></td>';
      tbody.appendChild(tr);
    });

    document.getElementById('itemCountDisplay').textContent = items.length;
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function addItem(epc) {
    if (!epc || epc.trim() === '') return;
    items.push({
      epc: epc.trim(),
      price: currentPrice
    });
    renderItems();
  }

  document.getElementById('btnSetPrice').addEventListener('click', function () {
    var val = parseFloat(document.getElementById('customPrice').value);
    if (!isNaN(val) && val >= 0) {
      currentPrice = val;
      alert(i18n.priceSet + ' ' + i18n.money(val));
    }
  });

  document.getElementById('btnReadRfid').addEventListener('click', async function () {
    var btn = this;
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '…';

    try {
      var resp = await fetch(base + '/api/products/read-rfid');
      if (!resp.ok) throw new Error('Request failed');
      var data = await resp.json();
      if (data.epc) {
        addItem(data.epc);
      } else {
        alert(i18n.noRfid);
      }
    } catch (err) {
      alert(i18n.rfidFail + ': ' + (err.message || err));
    } finally {
      btn.disabled = false;
      btn.innerHTML = orig;
    }
  });

  document.getElementById('epcInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var val = this.value.trim();
      if (val) {
        var parts = val.split(/[\s,;]+/).filter(Boolean);
        parts.forEach(addItem);
        this.value = '';
      }
    }
  });

  document.getElementById('itemsTableBody').addEventListener('click', function (e) {
    if (e.target.getAttribute('data-remove') !== null) {
      var idx = parseInt(e.target.getAttribute('data-remove'), 10);
      items.splice(idx, 1);
      renderItems();
    }
  });

  document.getElementById('btnClear').addEventListener('click', function () {
    if (confirm(i18n.confirmClear)) {
      items = [];
      renderItems();
    }
  });

  document.getElementById('btnSubmit').addEventListener('click', async function () {
    if (items.length === 0) {
      alert(i18n.noItems);
      return;
    }

    var form = document.createElement('form');
    form.method = 'post';
    form.action = base + '/inventory/reception/submit/' + productId;

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'items';
    input.value = JSON.stringify(items);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  });
})();
</script>

</body>
</html>
