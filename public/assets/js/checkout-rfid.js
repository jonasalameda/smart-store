(function () {
  var cfg = window.CheckoutConfig || {};
  var base = cfg.base || '';
  var i18n = (cfg.i18n || {});
  var isScanning = false;
  var scanInterval = null; // Replaces EventSource
  var btn = document.getElementById('btnReadRfid');

  if (!btn) return;

  function setButtonState(scanning) {
    if (scanning) {
      btn.classList.replace('btn-primary', 'btn-danger');
      btn.innerHTML = '<i class="bi bi-stop-fill"></i> ' + (i18n.stopScanning || 'Stop Scanning');
    } else {
      btn.classList.replace('btn-danger', 'btn-primary');
      btn.innerHTML = '<i class="bi bi-broadcast"></i> ' + (i18n.startScanning || 'Start Scanning');
    }
  }

  async function performSingleRead() {
    try {
      // Use your existing working one-time read endpoint
      const response = await fetch(base + '/api/products/read-rfid');
      const data = await response.json();

      if (data && data.epc) {
        // If your script returns multiple EPCs or a single one
        if (window.checkoutAddByEpcSingle) {
            window.checkoutAddByEpcSingle(data.epc);
        }
      }
    } catch (e) {
      console.error("RFID Read Error:", e);
    }
  }

  function startScanning() {
    if (isScanning) return;
    isScanning = true;
    setButtonState(true);

    // Call the function immediately, then every 1500ms
    performSingleRead();
    scanInterval = setInterval(performSingleRead, 1500);
  }

  function stopScanning() {
    if (!isScanning) return;
    isScanning = false;
    setButtonState(false);

    if (scanInterval) {
      clearInterval(scanInterval);
      scanInterval = null;
    }
  }

  btn.addEventListener('click', function () {
    if (isScanning) stopScanning();
    else startScanning();
  });
})();
