(function () {
  var config = window.StockReceptionConfig;
  var productId = config.productId;
  var basePrice = config.basePrice;
  var base = config.base;
  var i18n = config.i18n;

  var currentPrice = basePrice;
  var items = [];

  function money(n) {
    return '$' + Number(n).toFixed(2);
  }

  function renderItems() {
    var tbody = document.getElementById('itemsTableBody');
    var empty = document.getElementById('emptyMessage');

    tbody.querySelectorAll('tr[data-idx]').forEach(function (r) {
      r.remove();
    });

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
        '<td class="text-end font-monospace">' + money(item.price) + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '">' + escapeHtml(i18n.removeBtn) + '</button></td>';
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
      alert(i18n.priceSet + ' ' + money(val));
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

  document.getElementById('btnSubmit').addEventListener('click', function () {
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
