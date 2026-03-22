<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fridge Dashboard</title>
  <link rel="stylesheet" href="/smart-store/public/assets/css/dashboard.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

  <?php include '../common/header.php'; ?>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <a href="#" class="active"><span class="icon">🏠</span><span>Dashboard</span></a>
    <a href="#"><span class="icon">🔍</span><span>Search</span></a>
    <a href="#"><span class="icon">📊</span><span>Insights</span></a>
    <a href="#"><span class="icon">📄</span><span>Docs</span></a>
    <a href="#"><span class="icon">🛒</span><span>Products</span></a>
    <a href="#"><span class="icon">⚙️</span><span>Settings</span></a>
    <div class="toggle-btn" id="toggle-btn">▶️◀️</div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
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
                <div class="temperature" data-value="0°C"></div>
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
                <div class="temperature" data-value="0°C"></div>
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
    </div>
  </div>

    <!-- FAN SECTION -->
<div class="fridge fan-section">
  <h2>Cooling Fan</h2>

  <div class="fan-row">
    <img id="fan-img" src="fan.png" alt="Fan">

    <button id="fan-toggle" class="fan-off">
      OFF
    </button>
  </div>

  <p id="fan-status">Status: OFF</p>
</div>

<!-- Notification Button -->
<button type="button" class="icon-button">
  <span class="material-symbols-outlined">notifications</span>
  <span class="icon-button__badge" id="notification-count">0</span>
</button>
  
 <script src="/smart-store/public/assets/js/dashboard.js"></script>
</body>
</html>