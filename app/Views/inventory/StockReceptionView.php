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
                <button type="button" class="btn btn-outline-secondary" id="btnReadRfid">
                  <i class="bi bi-broadcast"></i> <?= htmlspecialchars(__('checkout.read_rfid')) ?>
                </button>
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
// (function () {
  var I18N = <?= json_encode($i18nData, JSON_THROW_ON_ERROR) ?>;
  var BASE_PRICE = <?= (float) $productPrice ?>;
  var PRODUCT_ID = <?= (int) $productId ?>;
  var BASE = <?= json_encode($base, JSON_THROW_ON_ERROR) ?>;

  var activePrice = BASE_PRICE;
  var items = []; // { epc, price }

  // ── Price override ────────────────────────────────────────────
  document.getElementById('btnSetPrice').addEventListener('click', function () {
    var val = parseFloat(document.getElementById('customPrice').value);
    if (!isNaN(val) && val >= 0) {
      activePrice = val;
      alert(I18N.priceSet);
    }
  });

  // ── Helpers ───────────────────────────────────────────────────
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  function money(n) {
    return '$' + Number(n).toFixed(2);
  }

  function renderItems() {
    var tbody = document.getElementById('itemsTableBody');
    var emptyRow = document.getElementById('emptyMessage');
    tbody.querySelectorAll('tr[data-idx]').forEach(function (r) { r.remove(); });

    if (items.length === 0) {
      emptyRow.style.display = '';
      document.getElementById('itemCountDisplay').textContent = '0';
      return;
    }
    emptyRow.style.display = 'none';
    document.getElementById('itemCountDisplay').textContent = String(items.length);

    items.forEach(function (item, idx) {
      var tr = document.createElement('tr');
      tr.setAttribute('data-idx', idx);
      tr.innerHTML =
        '<td>' + (idx + 1) + '</td>' +
        '<td class="font-monospace">' + escapeHtml(item.epc) + '</td>' +
        '<td class="text-end font-monospace">' + money(item.price) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '">' + escapeHtml(I18N.removeBtn) + '</button></td>';
      tbody.appendChild(tr);
    });
  }

  function addEpc(epcRaw) {
    var parts = String(epcRaw || '').split(/[\s,;]+/).filter(Boolean);
    parts.forEach(function (epc) {
      var key = epc.toUpperCase();
      var already = items.some(function (i) { return i.epc.toUpperCase() === key; });
      if (!already) {
        items.push({ epc: key, price: activePrice });
      }
    });
    renderItems();
  }

  function fetchJson(url) {
    return fetch(url).then(function (r) {
      if (!r.ok) throw new Error('Request failed');
      return r.json();
    });
  }

  // ── RFID read — exact same logic as checkout ──────────────────
//   document.getElementById('btnReadRfid').addEventListener('click', async function () {
//     var btnRead = document.getElementById('btnReadRfid');
//     var originalHtml = btnRead.innerHTML;
//     btnRead.disabled = true;
//     btnRead.innerHTML = '…';

//     try {
//       var data = await fetchJson(BASE + '/api/products/read-rfid');
//       if (data && data.epc) {
//         addEpc(data.epc);
//       } else {
//         alert(I18N.noRfid);
//       }
//     } catch (error) {
//       alert(I18N.rfidFail);
//     } finally {
//       btnRead.disabled = false;
//       btnRead.innerHTML = originalHtml;
//     }
//   });
// ── RFID read — exact same logic as checkout ──────────────────
  document.getElementById('btnReadRfid').addEventListener('click', async function () {
    var btnRead = document.getElementById('btnReadRfid');
    var originalHtml = btnRead.innerHTML;
    btnRead.disabled = true;
    btnRead.innerHTML = '…';

    try {
      var response = await fetch(BASE + '/api/products/read-rfid');
      if (!response.ok) {
        alert(I18N.rfidFail);
        return;
      }
      var data = await response.json();
      if (data && data.epc && typeof data.epc === 'string' && data.epc.trim() !== '') {
        addEpc(data.epc);
      } else {
        alert(I18N.noRfid);
      }
    } catch (error) {
      alert(I18N.rfidFail);
    } finally {
      btnRead.disabled = false;
      btnRead.innerHTML = originalHtml;
    }
  });

  // ── Remove row ────────────────────────────────────────────────
  document.getElementById('itemsTableBody').addEventListener('click', function (e) {
    var t = e.target;
    if (t.getAttribute('data-remove') !== null) {
      items.splice(parseInt(t.getAttribute('data-remove'), 10), 1);
      renderItems();
    }
  });

  // ── Clear all ─────────────────────────────────────────────────
  document.getElementById('btnClear').addEventListener('click', function () {
    if (items.length === 0 || confirm(I18N.confirmClear)) {
      items = [];
      renderItems();
    }
  });

  // ── Submit ────────────────────────────────────────────────────
//   document.getElementById('btnSubmit').addEventListener('click', function () {
//     if (items.length === 0) {
//       alert(I18N.noItems);
//       return;
//     }
//     fetch(BASE + '/inventory/receive/' + PRODUCT_ID, {
//       method: 'POST',
//       headers: { 'Content-Type': 'application/json' },
//       body: JSON.stringify({ items: items })
//     }).then(function (r) {
//       if (!r.ok) throw new Error('Submit failed');
//       return r.json();
//     }).then(function () {
//       items = [];
//       renderItems();
//       window.location.href = BASE + '/inventory';
//     }).catch(function () {
//       alert(I18N.rfidFail);
//     });
//   });
// ── Submit ────────────────────────────────────────────────────
  document.getElementById('btnSubmit').addEventListener('click', function () {
    fetch(BASE + '/inventory/receive/' + PRODUCT_ID, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: items })
    }).then(function () {
      items = [];
      renderItems();
      window.location.href = BASE + '/inventory';
    });
  });
  renderItems();
})();
(function () {
  var I18N = <?= json_encode($i18nData, JSON_THROW_ON_ERROR) ?>;
  var BASE_PRICE = <?= (float) $productPrice ?>;
  var PRODUCT_ID = <?= (int) $productId ?>;
  var BASE = <?= json_encode($base, JSON_THROW_ON_ERROR) ?>;

  var activePrice = BASE_PRICE;
  var items = []; // { epc, price }

  // ── Price override ────────────────────────────────────────────
  document.getElementById('btnSetPrice').addEventListener('click', function () {
    var val = parseFloat(document.getElementById('customPrice').value);
    if (!isNaN(val) && val >= 0) {
      activePrice = val;
      alert(I18N.priceSet);
    }
  });

  // ── Helpers ───────────────────────────────────────────────────
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  function money(n) {
    return '$' + Number(n).toFixed(2);
  }

  function renderItems() {
    var tbody = document.getElementById('itemsTableBody');
    var emptyRow = document.getElementById('emptyMessage');
    tbody.querySelectorAll('tr[data-idx]').forEach(function (r) { r.remove(); });

    if (items.length === 0) {
      emptyRow.style.display = '';
      document.getElementById('itemCountDisplay').textContent = '0';
      return;
    }
    emptyRow.style.display = 'none';
    document.getElementById('itemCountDisplay').textContent = String(items.length);

    items.forEach(function (item, idx) {
      var tr = document.createElement('tr');
      tr.setAttribute('data-idx', idx);
      tr.innerHTML =
        '<td>' + (idx + 1) + '</td>' +
        '<td class="font-monospace">' + escapeHtml(item.epc) + '</td>' +
        '<td class="text-end font-monospace">' + money(item.price) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '">' + escapeHtml(I18N.removeBtn) + '</button></td>';
      tbody.appendChild(tr);
    });
  }

  function addEpc(epcRaw) {
    var parts = String(epcRaw || '').split(/[\s,;]+/).filter(Boolean);
    parts.forEach(function (epc) {
      var key = epc.toUpperCase();
      var already = items.some(function (i) { return i.epc.toUpperCase() === key; });
      if (!already) {
        items.push({ epc: key, price: activePrice });
      }
    });
    renderItems();
  }

  function fetchJson(url) {
    return fetch(url).then(function (r) {
      if (!r.ok) throw new Error('Request failed');
      return r.json();
    });
  }

  // ── RFID read — exact same logic as checkout (single read) ─────
  document.getElementById('btnReadRfid').addEventListener('click', async function () {
    var btnRead = document.getElementById('btnReadRfid');
    var originalHtml = btnRead.innerHTML;
    btnRead.disabled = true;
    btnRead.innerHTML = '…';

    try {
      var response = await fetch(BASE + '/api/products/read-rfid');
      if (!response.ok) {
        // show alert only on error response
        alert(I18N.rfidFail || 'RFID read failed');
        return;
      }
      var data = await response.json();
      if (data && data.epc && typeof data.epc === 'string' && data.epc.trim() !== '') {
        addEpc(data.epc);
      } else {
        // no tag found
        alert(I18N.noRfid || 'No RFID tag detected');
      }
    } catch (error) {
      // on fetch/network error, show alert
      alert(I18N.rfidFail || 'RFID read failed');
    } finally {
      btnRead.disabled = false;
      btnRead.innerHTML = originalHtml;
    }
  });

  // ── Remove row ────────────────────────────────────────────────
  document.getElementById('itemsTableBody').addEventListener('click', function (e) {
    var t = e.target;
    if (t.getAttribute('data-remove') !== null) {
      items.splice(parseInt(t.getAttribute('data-remove'), 10), 1);
      renderItems();
    }
  });

  // ── Clear all ─────────────────────────────────────────────────
  document.getElementById('btnClear').addEventListener('click', function () {
    if (items.length === 0 || confirm(I18N.confirmClear)) {
      items = [];
      renderItems();
    }
  });

  // ── Submit ────────────────────────────────────────────────────
  // Use the controller route that updates DB: POST /inventory/reception/submit/{id}
  // Build a standard form (so server-side redirect and flash messages work)
  document.getElementById('btnSubmit').addEventListener('click', function () {
    if (items.length === 0) {
      alert(I18N.noItems || 'Add at least one item first');
      return;
    }

    // stop shared RFID reader if present on button
    var rbtn = document.getElementById('btnReadRfid');
    if (rbtn && rbtn.rfidReader && typeof rbtn.rfidReader.stop === 'function') {
      try { rbtn.rfidReader.stop(); } catch (e) { /* ignore */ }
    }

    // create a form and submit to server so controller can process and redirect
    var form = document.createElement('form');
    form.method = 'post';
    form.action = BASE + '/inventory/reception/submit/' + PRODUCT_ID;

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'items';
    input.value = JSON.stringify(items);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  });

  renderItems();
})();
</script>
</body>
</html>
