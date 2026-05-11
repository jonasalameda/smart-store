<?php
// ...existing code...
<script>
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
