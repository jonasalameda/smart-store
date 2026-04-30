<?php
$current_page = 'dashboard';
$page = $data['data'] ?? $data;
$fridge_data = $page['fridge_data'] ?? [
  'Frig1' => ['temperature' => 0, 'humidity' => 0],
  'Frig2' => ['temperature' => 0, 'humidity' => 0],
];
$refrigerators = $page['refrigerators'] ?? [];
$flash = $page['flash'] ?? null;

// Map by MQTT_Topic so the form can pre-fill per fridge even if ordering changes.
$refrigeratorsByTopic = [];
foreach ($refrigerators as $refrigerator) {
  if (!is_array($refrigerator)) {
    continue;
  }
  $topic = (string) ($refrigerator['MQTT_Topic'] ?? '');
  if ($topic !== '') {
    $refrigeratorsByTopic[$topic] = $refrigerator;
  }
}
$thresholdForm = [];
foreach (['Frig1', 'Frig2'] as $topic) {
  $row = $refrigeratorsByTopic[$topic] ?? null;
  $thresholdForm[$topic] = [
    'id' => $row ? (int) ($row['RefrigeratorID'] ?? 0) : null,
    'name' => $row['Name'] ?? $topic,
    'temp' => $row ? (float) ($row['Temperature_Threshold'] ?? 15) : 15.0,
    'hum' => $row ? (float) ($row['Humidity_Threshold'] ?? 40) : 40.0,
  ];
}
$base = defined('APP_BASE_URL') ? rtrim((string) APP_BASE_URL, '/') : '';
$thresholdsJsonHref = public_asset_href('other_data/thresholds.json');
$dashI18n = [
  'fan_on' => __('dash.fan_on'),
  'fan_off' => __('dash.fan_off'),
  'fan_status_on' => __('js.fan_status_on'),
  'fan_status_off' => __('js.fan_status_off'),
  'fan_status_error' => __('js.fan_status_error'),
  'fan_status_error_hint' => __('js.fan_status_error_hint'),
  'yes' => __('common.yes'),
  'no' => __('common.no'),
  'alert_temp' => __('js.alert_temp'),
  'alert_hum' => __('js.alert_hum'),
  'alert_fan_on' => __('js.alert_fan_on'),
  'alert_fan_stay_off' => __('js.alert_fan_stay_off'),
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">

<head>
  <?php include __DIR__ . '/common/theme_init.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(__('dash.title')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <?php include __DIR__ . '/common/theme_stylesheet.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/sidebar.css')) ?>">
  <link rel="stylesheet" href="<?= hs(public_asset_href('css/dashboard.css')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <script>
    const APP_BASE_URL = "<?= htmlspecialchars($base) ?>";
    /** Same-origin path prefix for API routes (avoids cross-origin fetch when host is 127.0.0.1 vs localhost). */
    const APP_API_BASE = "<?= defined('APP_ROOT_DIR_NAME') ? '/' . htmlspecialchars((string) APP_ROOT_DIR_NAME, ENT_QUOTES) : '' ?>";
    window.__THRESHOLDS_JSON_PATH = <?= json_encode($thresholdsJsonHref, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) ?>;
    window.__APP_I18N = <?= json_encode($dashI18n, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
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

<body class="fridge-dashboard">
  <?php include __DIR__ . '/admin/header.php'; ?>

  <main class="main-content dashboard-content">
    <h1><?= htmlspecialchars(__('dash.title')) ?></h1>

    <div class="dashboard-main-grid">
      <div class="dashboard-top-row fridge-container">
        <section class="fridge" aria-label="<?= htmlspecialchars(str_replace('{n}', '1', __('dash.fridge_n'))) ?>">
          <h2><?= htmlspecialchars(str_replace('{n}', '1', __('dash.fridge_n'))) ?></h2>
          <div class="gauges-row">
            <div class="gauge-wrapper">
              <div class="gauge-label"><?= htmlspecialchars(__('dash.temperature')) ?></div>
              <div class="thermometer-wrapper">
                <div class="termometer">
                  <div class="temperature" data-value="<?= (float) ($fridge_data['Frig1']['temperature'] ?? 0) ?> °C"></div>
                </div>
              </div>
            </div>
            <div class="gauge-wrapper">
              <div class="gauge-label"><?= htmlspecialchars(__('dash.humidity')) ?></div>
              <div class="arc-gauge-container">
                <div class="arc-gauge humidity-gauge">
                  <div class="value">
                    <div class="small"><?= htmlspecialchars(__('dash.humidity_pct_label')) ?></div>
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

        <section class="fridge" aria-label="<?= htmlspecialchars(str_replace('{n}', '2', __('dash.fridge_n'))) ?>">
          <h2><?= htmlspecialchars(str_replace('{n}', '2', __('dash.fridge_n'))) ?></h2>
          <div class="gauges-row">
            <div class="gauge-wrapper">
              <div class="gauge-label"><?= htmlspecialchars(__('dash.temperature')) ?></div>
              <div class="thermometer-wrapper">
                <div class="termometer">
                  <div class="temperature" data-value="<?= (float) ($fridge_data['Frig2']['temperature'] ?? 0) ?> °C"></div>
                </div>
              </div>
            </div>
            <div class="gauge-wrapper">
              <div class="gauge-label"><?= htmlspecialchars(__('dash.humidity')) ?></div>
              <div class="arc-gauge-container">
                <div class="arc-gauge humidity-gauge">
                  <div class="value">
                    <div class="small"><?= htmlspecialchars(__('dash.humidity_pct_label')) ?></div>
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

      <div class="dashboard-bottom-row">
        <section class="fridge fan-section" aria-label="<?= htmlspecialchars(__('dash.cooling_fan')) ?>">
          <h2><?= htmlspecialchars(__('dash.cooling_fan')) ?></h2>
          <div class="fan-row">
            <img id="fan-img" src="<?= hs(public_asset_href('images/fan.png')) ?>" alt="<?= htmlspecialchars(__('dash.fan_alt')) ?>">
            <button id="fan-toggle" class="fan-off" type="button"><?= htmlspecialchars(__('dash.fan_off')) ?></button>
          </div>
          <p id="fan-status"><?= htmlspecialchars(__('js.fan_status_off')) ?></p>
        </section>
      </div>
    </div>

    <section class="fridge fridge-panel threshold-settings-panel threshold-section" aria-label="<?= htmlspecialchars(__('dash.threshold_settings')) ?>">
      <h2><?= htmlspecialchars(__('dash.threshold_settings')) ?></h2>
      <?php if ($flash && !empty($flash['message'])): ?>
        <p class="threshold-flash threshold-flash--<?= htmlspecialchars((string) ($flash['type'] ?? 'info')) ?>">
          <?= htmlspecialchars((string) $flash['message']) ?>
        </p>
      <?php endif; ?>
      <form method="post" action="<?= htmlspecialchars($base) ?>/dashboard/thresholds" class="threshold-form">
        <div class="threshold-form-rows">
          <?php foreach (['Frig1', 'Frig2'] as $topic): $row = $thresholdForm[$topic];
            $id = $row['id']; ?>
            <div class="threshold-row">
              <h3><?= htmlspecialchars((string) $row['name']) ?> <small>(<?= htmlspecialchars($topic) ?>)</small></h3>
              <label>
                <?= htmlspecialchars(__('dash.threshold_temp_c')) ?>
                <input
                  type="number"
                  step="0.1"
                  id="dash-threshold-<?= htmlspecialchars($topic) ?>-temp"
                  name="temp_threshold[<?= $id !== null ? (int) $id : '' ?>]"
                  value="<?= htmlspecialchars((string) $row['temp']) ?>"
                  <?= $id === null ? 'disabled' : '' ?>
                  required>
              </label>
              <label>
                <?= htmlspecialchars(__('dash.threshold_humidity_pct')) ?>
                <input
                  type="number"
                  step="0.1"
                  min="0"
                  max="100"
                  id="dash-threshold-<?= htmlspecialchars($topic) ?>-humidity"
                  name="humidity_threshold[<?= $id !== null ? (int) $id : '' ?>]"
                  value="<?= htmlspecialchars((string) $row['hum']) ?>"
                  <?= $id === null ? 'disabled' : '' ?>
                  required>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="threshold-save"><?= htmlspecialchars(__('dash.threshold_save')) ?></button>
      </form>
    </section>

    <a href="<?= htmlspecialchars($base) ?>/notifications" class="notification-link" aria-label="<?= htmlspecialchars(__('dash.open_notifications')) ?>">
      <button type="button" class="icon-button">
        <span class="material-symbols-outlined">notifications</span>
        <span class="icon-button__badge" id="notification-count">0</span>
      </button>
    </a>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <?php include __DIR__ . '/common/flash.php'; ?>
  <script src="<?= hs(public_asset_href('js/dashboard.js')) ?>"></script>
  <script src="<?= hs(public_asset_href('js/threshold_alerts.js')) ?>"></script>
</body>

</html>