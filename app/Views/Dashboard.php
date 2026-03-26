<?php $current_page = 'dashboard'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fridge Dashboard</title>

  <link rel="stylesheet" href="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/public/assets/css/layout/sidebar.css"> 
  <link rel="stylesheet" href="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/public/assets/css/dashboard.css">

  <!-- commented this out the code above ^^ doesn't let me access any of the css on my laptop relative path works tho -->
      <!--  <link rel="stylesheet" href="assets/css/layout/sidebar.css">
  <link rel="stylesheet" href="assets/css/dashboard.css"> -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

  <?php include __DIR__ . '/common/header.php'; ?>

  <main class="main-content">
    <?php $fridge_data = $data['fridge_data'] ?? ['Frig1' => ['temperature' => 0, 'humidity' => 0], 'Frig2' => ['temperature' => 0, 'humidity' => 0]]; ?>
    <h1>Fridge Dashboard</h1>

    <!-- GRID CONTAINER -->
    <div class="fridge-container">

      <!-- Fridge 1 -->
      <div class="fridge">
        <h2>Fridge 1</h2>
        <div class="gauges-row">
          <div class="gauge-wrapper">
            <div class="gauge-label">Temperature</div>
            <div class="thermometer-wrapper">
              <div class="termometer">
                <div class="temperature" data-value="<?php echo $fridge_data['Frig1']['temperature'] ?? 0; ?> C"></div>
              </div>
            </div>
          </div>
          <div class="gauge-wrapper">
            <div class="gauge-label">Humidity</div>
            <div class="arc-gauge-container">
              <div class="arc-gauge humidity-gauge">
                <div class="value">
                  <div class="small">Humidity%</div>
                  <div class="humidity pct-val"><?php echo $fridge_data['Frig1']['humidity'] ?? 0; ?></div>
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
                <div class="temperature" data-value="<?php echo $fridge_data['Frig2']['temperature'] ?? 0; ?> C"></div>
              </div>
            </div>
          </div>
          <div class="gauge-wrapper">
            <div class="gauge-label">Humidity</div>
            <div class="arc-gauge-container">
              <div class="arc-gauge humidity-gauge">
                <div class="value">
                  <div class="small">Humidity%</div>
                  <div class="humidity pct-val"><?php echo $fridge_data['Frig2']['humidity'] ?? 0; ?></div>
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
</div>

      <!-- Fan Section  -->
      <div class="fridge fan-section">
        <h2>Cooling Fan</h2>
        <div class="fan-row">

          <img id="fan-img" src="<?= defined('APP_BASE_URL') ? APP_BASE_URL : '' ?>/public/assets/images/fan.png" alt="Fan">
          <!-- commmented this out as well ^^ code above doesnt work on my laptop -->
          <!-- <img id="fan-img" src="assets/images/fan.png" alt="Fan"> -->
          <button id="fan-toggle" class="fan-off">OFF</button>
        </div>
        <p id="fan-status">Status: OFF</p>
      </div>

    </div> 

    <!-- Notif. button -->
 <a href="<?= APP_BASE_URL ?>/notifications">
  <button type="button" class="icon-button">
    <span class="material-symbols-outlined">notifications</span>
    <span class="icon-button__badge" id="notification-count">0</span>
  </button>
</a>
  </main>

  <script src="assets/js/fan.js"></script>
</body>
</html>