(function () {
  var cfg = window.CheckoutConfig || {};
  var catalog = cfg.catalog || [];
  var MSG = cfg.MSG || {};
  var byUpc = {};
  var byEpc = {};
  catalog.forEach(function (p) {
    if (p.upc) byUpc[String(p.upc)] = p;
    if (p.epc) byEpc[String(p.epc).toUpperCase()] = p;
  });

  var cart = {};

  function money(n) { return '$' + Number(n).toFixed(2); }

  function cartLines() {
    return Object.keys(cart).map(function (id) {
      var row = cart[id];
      return { product_id: Number(id), quantity: row.qty };
    });
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function renderCart() {
    var tbody = document.getElementById('cartBody');
    var emptyRow = document.getElementById('cartEmptyRow');
    var ids = Object.keys(cart);
    var subtotal = 0;
    tbody.querySelectorAll('tr[data-line]').forEach(function (r) { r.remove(); });

    if (ids.length.length === 0) {} // no-op to keep minified-friendly

    if (ids.length === 0) {
      emptyRow.style.display = '';
      emptyRow.querySelector('td').textContent = MSG.cartEmpty || 'Cart is empty';
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
        '<td class="text-end font-monospace">' + money(row.price) + '</td>' +
        '<td class="text-end font-monospace">' + money(line) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + id + '">' + escapeHtml(MSG.remove || 'Remove') + '</button></td>';
      tbody.appendChild(tr);
    });

    var discountCheckbox = document.getElementById('applyDiscount');
    var total = subtotal;
    if (discountCheckbox && discountCheckbox.checked) total = subtotal * 0.9;

    document.getElementById('cartTotalDisplay').textContent = money(total);
    document.getElementById('itemsPayload').value = JSON.stringify(cartLines());
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

  function addByUpc(raw) {
    var upc = String(raw || '').trim();
    if (!upc) return;
    var p = byUpc[upc];
    if (p) addProduct(p);
  }

  function addByEpcSingle(epc) {
    var key = String(epc || '').toUpperCase();
    var p = byEpc[key];
    if (!p) return;
    addProduct(p);
  }

  function addByEpc(raw) {
    var epc = String(raw || '').trim();
    if (!epc) return Promise.resolve();
    var parts = epc.split(/[\s,;]+/).filter(Boolean);
    parts.forEach(function (chunk) { addByEpcSingle(chunk); });
    return Promise.resolve();
  }

  // export handlers for external RFID script
  window.checkoutAddByEpc = addByEpc;
  window.checkoutAddByEpcSingle = addByEpcSingle;

  // UI handlers
  document.getElementById('cartBody').addEventListener('click', function (e) {
    var t = e.target;
    if (t.getAttribute('data-remove')) {
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
      if (this.checked) alert(MSG.discountApplied || 'Discount applied');
      else alert(MSG.discountRemoved || 'Discount removed');
    });
  }

  // initial render
  renderCart();
})();
