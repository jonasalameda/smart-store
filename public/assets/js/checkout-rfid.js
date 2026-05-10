(function () {
  var cfg = window.CheckoutConfig || {};
  var base = cfg.base || '';
  var i18n = (cfg.i18n || {});
  var isScanning = false;
  var eventSource = null;
  var btn = document.getElementById('btnReadRfid');
  if (!btn) return;

  function setButtonState(scanning) {
    if (scanning) {
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-danger');
      btn.innerHTML = '<i class="bi bi-stop-fill"></i> ' + (i18n.stopScanning || 'Stop scanning');
    } else {
      btn.classList.remove('btn-danger');
      btn.classList.add('btn-primary');
      btn.innerHTML = '<i class="bi bi-broadcast"></i> ' + (i18n.startScanning || 'Start scanning');
    }
  }

  function startScanning() {
    if (isScanning) return;
    setButtonState(true);
    isScanning = true;
    eventSource = new EventSource(base + '/api/products/stream-rfid');
    eventSource.onmessage = function (ev) {
      try {
        var data = JSON.parse(ev.data);
        if (data && data.epc) {
          if (window.checkoutAddByEpcSingle) window.checkoutAddByEpcSingle(data.epc);
          else if (window.checkoutAddByEpc) window.checkoutAddByEpc(data.epc);
        }
      } catch (e) { /* ignore */ }
    };
    eventSource.onerror = function () {
      stopScanning();
      alert(i18n.rfidFail || 'RFID read failed');
    };
  }

  function stopScanning() {
    if (!isScanning) return;
    if (eventSource) {
      try { eventSource.close(); } catch (e) {}
      eventSource = null;
    }
    isScanning = false;
    setButtonState(false);
  }

  btn.addEventListener('click', function () {
    if (isScanning) stopScanning(); else startScanning();
  });

  // init
  setButtonState(false);
})();
