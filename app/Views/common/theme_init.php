<?php
/**
 * Blocking script: set data-bs-theme before first paint (localStorage or system preference).
 * Include as the first child inside <head>.
 */
?>
<script>
(function () {
  try {
    var k = 'smart-store-theme';
    var s = localStorage.getItem(k);
    var d =
      s === 'dark' || s === 'light'
        ? s
        : window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
          ? 'dark'
          : 'light';
    document.documentElement.setAttribute('data-bs-theme', d);
  } catch (e) {
    document.documentElement.setAttribute('data-bs-theme', 'light');
  }
})();
</script>
