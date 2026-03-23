<?php $current_page = 'dashboard'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fridge Dashboard</title>
  <link rel="stylesheet" href="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/public/assets/css/layout/sidebar.css">
  <link rel="stylesheet" href="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/public/assets/css/dashboard.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

  <?php include __DIR__ . '/common/header.php'; ?>

  <main class="main-content">
    <?php $fridge_data = $data['fridge_data'] ?? ['temperature' => 'N/A', 'humidity' => 'N/A']; ?>
    <h1>Fridge Dashboard</h1>
    <div class="fridge-container">
      <!-- Fridge 1 -->
      <div class="fridge">
        <h2>Fridge 1</h2>
        <div class="gauges-row">
          <div class="gauge-wrapper">
            <div class="gauge-label">Temperature</div>
            <div class="thermometer-wrapper">
              <div class="termometer">
                <div class="temperature" data-value="0 C"></div>
              </div>
            </div>
          </div>
          <div class="gauge-wrapper">
            <div class="gauge-label">Humidity</div>
            <div class="arc-gauge-container">
              <div class="arc-gauge humidity-gauge">
                <div class="value">
                  <div class="small">Humidity%</div>
                  <div class="humidity pct-val">0</div>
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
      </div>
      <!-- Fridge 2 -->
      <div class="fridge">
        <h2>Fridge 2</h2>
        <div class="gauges-row">
          <div class="gauge-wrapper">
            <div class="gauge-label">Temperature</div>
            <div class="thermometer-wrapper">
              <div class="termometer">
                <div class="temperature" data-value="0 C"></div>
              </div>
            </div>
          </div>
          <div class="gauge-wrapper">
            <div class="gauge-label">Humidity</div>
            <div class="arc-gauge-container">
              <div class="arc-gauge humidity-gauge">
                <div class="value">
                  <div class="small">Humidity%</div>
                  <div class="humidity pct-val">0</div>
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
      </div>
      <!-- Fan Section -->
      <div class="fridge fan-section">
        <h2>Cooling Fan</h2>
        <div class="fan-row">
          <img id="fan-img" src="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/public/assets/images/fan.png" alt="Fan">
          <button id="fan-toggle" class="fan-off">OFF</button>
        </div>
        <p id="fan-status">Status: OFF</p>
      </div>
    </div>
  </div>
  
    <!-- FAN SECTION -->
<div class="fridge fan-section">
  <h2>Cooling Fan</h2>

  <div class="fan-row">
    <img id="fan-img" src="/assets/images/fan.png" alt="Fan">

    <button id="fan-toggle" class="fan-off">
      OFF
    </button>
  </div>

  <p id="fan-status">Status: OFF</p>
</div>
  
 <script src="/smart-store/public/assets/js/dashboard.js"></script>
</body>
</html>
