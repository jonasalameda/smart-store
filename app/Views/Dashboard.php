<?php
$current_page = 'dashboard';
$page = $data['data'] ?? $data;
$fridge_data = $page['fridge_data'] ?? [
  'Frig1' => ['temperature' => 0, 'humidity' => 0],
  'Frig2' => ['temperature' => 0, 'humidity' => 0],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fridge Dashboard</title>
  <link rel="stylesheet" href="/smart-store/public/assets/css/layout/sidebar.css">
  <link rel="stylesheet" href="/smart-store/public/assets/css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <script>
    const APP_BASE_URL = "<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>";
    /** Same-origin path prefix for API routes (avoids cross-origin fetch when host is 127.0.0.1 vs localhost). */
    const APP_API_BASE = "<?= defined('APP_ROOT_DIR_NAME') ? '/' . APP_ROOT_DIR_NAME : '' ?>";
    const phpFridgeData = {
      Frig1: {
        temperature: <?= (float) ($fridge_data['Frig1']['temperature'] ?? 0) ?>,
        humidity: <?= (float) ($fridge_data['Frig1']['humidity'] ?? 0) ?>
      },
      Frig2: {
        temperature: <?= (float) ($fridge_data['Frig2']['temperature'] ?? 0) ?>,
        humidity: <?= (float) ($fridge_data['Frig2']['humidity'] ?? 0) ?>
      }
    };
  </script>
</head>
<body>
  <?php include __DIR__ . '/admin/header.php'; ?>

  <main class="main-content dashboard-content">
    <h1>Fridge Dashboard</h1>

    <div class="fridge-container">
      <section class="fridge" aria-label="Fridge 1">
        <h2>Fridge 1</h2>
        <div class="gauges-row">
          <div class="gauge-wrapper">
            <div class="gauge-label">Temperature</div>
            <div class="thermometer-wrapper">
              <div class="termometer">
                <div class="temperature" data-value="<?= (float) ($fridge_data['Frig1']['temperature'] ?? 0) ?> C"></div>
              </div>
            </div>
          </div>
          <div class="gauge-wrapper">
            <div class="gauge-label">Humidity</div>
            <div class="arc-gauge-container">
              <div class="arc-gauge humidity-gauge">
                <div class="value">
                  <div class="small">Humidity%</div>
                  <div class="humidity pct-val"><?= (float) ($fridge_data['Frig1']['humidity'] ?? 0) ?></div>
                </div>
                <div class="mask">
                  <div class="reveal"></div>
                  <div class="cutout"></div>
                </div>
                <div class="arc"></div>
                <div class="indicator"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="fridge" aria-label="Fridge 2">
        <h2>Fridge 2</h2>
        <div class="gauges-row">
          <div class="gauge-wrapper">
            <div class="gauge-label">Temperature</div>
            <div class="thermometer-wrapper">
              <div class="termometer">
                <div class="temperature" data-value="<?= (float) ($fridge_data['Frig2']['temperature'] ?? 0) ?> C"></div>
              </div>
            </div>
          </div>
          <div class="gauge-wrapper">
            <div class="gauge-label">Humidity</div>
            <div class="arc-gauge-container">
              <div class="arc-gauge humidity-gauge">
                <div class="value">
                  <div class="small">Humidity%</div>
                  <div class="humidity pct-val"><?= (float) ($fridge_data['Frig2']['humidity'] ?? 0) ?></div>
                </div>
                <div class="mask">
                  <div class="reveal"></div>
                  <div class="cutout"></div>
                </div>
                <div class="arc"></div>
                <div class="indicator"></div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <section class="fridge fan-section" aria-label="Cooling fan controls">
      <h2>Cooling Fan</h2>
      <div class="fan-row">
        <img id="fan-img" src="/smart-store/public/assets/images/fan.png" alt="Fan">
        <button id="fan-toggle" class="fan-off" type="button">OFF</button>
      </div>
      <p id="fan-status">Status: OFF</p>
    </section>

    <a href="<?= APP_BASE_URL ?>/notifications" class="notification-link" aria-label="Open notifications">
      <button type="button" class="icon-button">
        <span class="material-symbols-outlined">notifications</span>
        <span class="icon-button__badge" id="notification-count">0</span>
      </button>
    </a>
  </main>

  <script src="/smart-store/public/assets/js/dashboard.js"></script>
  <script src="/smart-store/public/assets/js/threshold_alerts.js"></script>
</body>
</html>