<?php
/**
 * Theme toggle (Bootstrap color mode via data-bs-theme). Include from desktop + mobile nav;
 * script registers once (see $GLOBALS guard).
 */
$toggle_to_dark = htmlspecialchars(__('common.theme_use_dark'), ENT_QUOTES, 'UTF-8');
$toggle_to_light = htmlspecialchars(__('common.theme_use_light'), ENT_QUOTES, 'UTF-8');
?>
<span class="theme-toggle-wrap d-inline-flex align-items-center">
  <button
    type="button"
    class="btn btn-sm theme-toggle-btn border-0 rounded-pill px-2 py-1"
    data-theme-toggle
    aria-label="<?= htmlspecialchars(__('common.theme_toggle'), ENT_QUOTES, 'UTF-8') ?>"
    data-label-dark="<?= $toggle_to_dark ?>"
    data-label-light="<?= $toggle_to_light ?>"
  >
    <i class="bi bi-moon-stars-fill theme-icon-when-light" aria-hidden="true"></i>
    <i class="bi bi-sun-fill theme-icon-when-dark d-none" aria-hidden="true"></i>
  </button>
</span>
<?php
if (empty($GLOBALS['__theme_toggle_script_done'])) {
    $GLOBALS['__theme_toggle_script_done'] = true;
    ?>
<script>
(function () {
  var KEY = 'smart-store-theme';
  function current() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
  }
  function applyIcons() {
    var dark = current() === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-label', dark ? btn.getAttribute('data-label-light') : btn.getAttribute('data-label-dark'));
      var a = btn.querySelector('.theme-icon-when-light');
      var b = btn.querySelector('.theme-icon-when-dark');
      if (a) a.classList.toggle('d-none', dark);
      if (b) b.classList.toggle('d-none', !dark);
    });
  }
  function setTheme(mode) {
    document.documentElement.setAttribute('data-bs-theme', mode);
    try {
      localStorage.setItem(KEY, mode);
    } catch (e) {}
    applyIcons();
  }
  function bind() {
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setTheme(current() === 'dark' ? 'light' : 'dark');
      });
    });
    applyIcons();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
</script>
<?php
}
?>
