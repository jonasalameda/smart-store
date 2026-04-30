<?php
/**
 * Reusable threshold form block for one fridge on Dashboard.
 *
 * Expected variables:
 * - $thresholdTopic (string): e.g. Frig1
 * - $thresholdRow (array): ['id','name','temp','hum']
 * - $base (string): app base URL
 * - $showThresholdFlash (bool): whether to render flash message
 * - $flash (array|null): flash payload from controller
 */
$thresholdTopic = (string) ($thresholdTopic ?? '');
$thresholdRow = is_array($thresholdRow ?? null) ? $thresholdRow : [];
$thresholdId = $thresholdRow['id'] ?? null;
?>
<section class="fridge-threshold threshold-section" aria-label="<?= htmlspecialchars(__('dash.threshold_settings')) ?>">
  <h3><?= htmlspecialchars(__('dash.threshold_settings')) ?></h3>
  <?php if (!empty($showThresholdFlash) && !empty($flash['message'])): ?>
    <p class="threshold-flash threshold-flash--<?= htmlspecialchars((string) ($flash['type'] ?? 'info')) ?>">
      <?= htmlspecialchars((string) $flash['message']) ?>
    </p>
  <?php endif; ?>
  <form method="post" action="<?= htmlspecialchars($base) ?>/dashboard/thresholds" class="threshold-form">
    <div class="threshold-form-rows">
      <div class="threshold-row">
        <label>
          <?= htmlspecialchars(__('dash.threshold_temp_c')) ?>
          <input
            type="number"
            step="0.1"
            id="dash-threshold-<?= htmlspecialchars($thresholdTopic) ?>-temp"
            name="temp_threshold[<?= $thresholdId !== null ? (int) $thresholdId : '' ?>]"
            value="<?= htmlspecialchars((string) ($thresholdRow['temp'] ?? '')) ?>"
            <?= $thresholdId === null ? 'disabled' : '' ?>
            required>
        </label>
        <label>
          <?= htmlspecialchars(__('dash.threshold_humidity_pct')) ?>
          <input
            type="number"
            step="0.1"
            min="0"
            max="100"
            id="dash-threshold-<?= htmlspecialchars($thresholdTopic) ?>-humidity"
            name="humidity_threshold[<?= $thresholdId !== null ? (int) $thresholdId : '' ?>]"
            value="<?= htmlspecialchars((string) ($thresholdRow['hum'] ?? '')) ?>"
            <?= $thresholdId === null ? 'disabled' : '' ?>
            required>
        </label>
      </div>
    </div>
    <button type="submit" class="threshold-save"><?= htmlspecialchars(__('dash.threshold_save')) ?></button>
  </form>
</section>
