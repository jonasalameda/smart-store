/**
 * Stock reception view JS
 * - Manages scanned items list, custom price and submit.
 * - Exposes window.checkoutAddByEpcSingle so the shared reader (checkout-rfid.js) can call it.
 *
 * Keep comments for clarity.
 */
(function () {
  'use strict';

  // read config provided by PHP view
  var cfg = window.StockReceptionConfig || {};
  var productId = cfg.productId || 0;
  var basePrice = Number(cfg.basePrice || 0);
  var base = cfg.base || '';
  var i18n = cfg.i18n || {};

  // internal state
  var currentPrice = basePrice;
  var items = []; // array of { epc: string, price: number }

  // UTIL: money format
  function money(n) {
    return '$' + Number(n).toFixed(2);
  }

  // RENDER: update table and counters
  function renderItems() {
    var tbody = document.getElementById('itemsTableBody');
    var empty = document.getElementById('emptyMessage');

    // remove existing item rows
    Array.from(tbody.querySelectorAll('tr[data-idx]')).forEach(function (r) { r.remove(); });

    if (items.length === 0) {
      empty.style.display = '';
      document.getElementById('itemCountDisplay').textContent = '0';
      return;
    }
    empty.style.display = 'none';

    items.forEach(function (it, idx) {
      var tr = document.createElement('tr');
      tr.setAttribute('data-idx', idx);
      tr.innerHTML =
        '<td>' + (idx + 1) + '</td>' +
        '<td class="font-monospace small">' + escapeHtml(it.epc) + '</td>' +
        '<td class="text-end font-monospace">' + money(it.price) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '">' + escapeHtml(i18n.removeBtn || 'Remove') + '</button></td>';
      tbody.appendChild(tr);
    });

    document.getElementById('itemCountDisplay').textContent = items.length;
  }

  // simple escape
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  // Add EPC into items (avoids duplicates)
  function addItem(epc) {
    if (!epc) return;
    var e = String(epc || '').trim();
    if (e === '') return;
    // prevent duplicates
    if (items.some(function (it) { return it.epc === e; })) return;
    items.push({ epc: e, price: currentPrice });
    renderItems();
  }

  // expose function expected by checkout-rfid.js (calls checkoutAddByEpcSingle)
  window.checkoutAddByEpcSingle = function (epc) {
    try {
      addItem(epc);
    } catch (err) {
      console.error('stock-reception: handler error', err);
    }
  };

  // also expose named helper for other integrations
  window.stockReceptionAddItem = function (epc) {
    window.checkoutAddByEpcSingle(epc);
  };

  // UI bindings

  // Set custom price button
  var btnSet = document.getElementById('btnSetPrice');
  if (btnSet) {
    btnSet.addEventListener('click', function () {
      var v = parseFloat(document.getElementById('customPrice').value);
      if (!isNaN(v) && v >= 0) {
        currentPrice = v;
        alert((i18n.priceSet || 'Unit price set to') + ' ' + money(v));
      }
    });
  }

  // Manual EPC input (Enter to add)
  var epcInput = document.getElementById('epcInput');
  if (epcInput) {
    epcInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        var val = this.value.trim();
        if (val) {
          // allow multiple EPCs separated by whitespace/comma/semicolon
          var parts = val.split(/[\s,;]+/).filter(Boolean);
          parts.forEach(function (p) { addItem(p); });
          this.value = '';
        }
      }
    });
  }

  // Remove per-row
  var tbody = document.getElementById('itemsTableBody');
  if (tbody) {
    tbody.addEventListener('click', function (e) {
      var rem = e.target.getAttribute('data-remove');
      if (rem !== null) {
        var idx = parseInt(rem, 10);
        if (!isNaN(idx)) {
          items.splice(idx, 1);
          renderItems();
        }
      }
    });
  }

  // Clear list
  var btnClear = document.getElementById('btnClear');
  if (btnClear) {
    btnClear.addEventListener('click', function () {
      if (confirm(i18n.confirmClear || 'Clear all items?')) {
        items = [];
        renderItems();
      }
    });
  }

  // Submit reception
  var btnSubmit = document.getElementById('btnSubmit');
  if (btnSubmit) {
    btnSubmit.addEventListener('click', function () {
      if (items.length === 0) {
        alert(i18n.noItems || 'Add at least one item first');
        return;
      }
      // stop reader if button exposes control (checkout-rfid.js adds start/stop on button)
      var rbtn = document.getElementById('btnReadRfid');
      if (rbtn && rbtn.rfidReader && typeof rbtn.rfidReader.stop === 'function') {
        try { rbtn.rfidReader.stop(); } catch (e) { /* ignore */ }
      }

      // create form and post
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
  }

  // initial render
  renderItems();

})();
