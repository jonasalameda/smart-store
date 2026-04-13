<?php
declare(strict_types=1);

$base = defined('APP_BASE_URL') ? rtrim((string) APP_BASE_URL, '/') : '';
$path = locale_redirect_path();
$switchBase = $base . '/locale/switch';
?>
<span class="lang-switcher small">
  <a class="text-decoration-none<?= current_locale() === 'en' ? ' fw-bold' : '' ?>" href="<?= htmlspecialchars($switchBase) ?>?lang=en&amp;redirect=<?= rawurlencode($path) ?>"><?= htmlspecialchars(__('lang.en')) ?></a>
  <span class="text-muted px-1">|</span>
  <a class="text-decoration-none<?= current_locale() === 'fr' ? ' fw-bold' : '' ?>" href="<?= htmlspecialchars($switchBase) ?>?lang=fr&amp;redirect=<?= rawurlencode($path) ?>"><?= htmlspecialchars(__('lang.fr')) ?></a>
</span>
