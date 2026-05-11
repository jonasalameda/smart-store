(function () {
  var config = window.StockReceptionConfig;
  var productId = config.productId;
  var basePrice = config.basePrice;
  var base = config.base;
  var i18n = config.i18n;

  var currentPrice = basePrice;
  var items = [];
  var isScanning = false;
  var eventSource = null;

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

  document.getElementById('btnReadRfid').addEventListener('click', function () {
    if (isScanning) {
      stopScanning();
    } else {
      startScanning();
    }
  });

  function startScanning() {
    var btn = document.getElementById('btnReadRfid');
    btn.classList.add('btn-danger');
    btn.classList.remove('btn-primary');
    btn.innerHTML = '<i class="bi bi-stop-fill"></i> ' + i18n.stopScanning;
    isScanning = true;

    eventSource = new EventSource(base + '/api/products/stream-rfid');

    eventSource.onmessage = function (event) {
      var data = JSON.parse(event.data);
      if (data.epc) {
        addItem(data.epc);
      }
    };

    eventSource.onerror = function (err) {
      stopScanning();
      alert(i18n.rfidFail);
    };
  }

  function stopScanning() {
    if (eventSource) {
      eventSource.close();
      eventSource = null;
    }

    var btn = document.getElementById('btnReadRfid');
    btn.classList.remove('btn-danger');
    btn.classList.add('btn-primary');
    btn.innerHTML = '<i class="bi bi-broadcast"></i> ' + i18n.startScanning;
    isScanning = false;
  }

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
    if (isScanning) stopScanning();

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
