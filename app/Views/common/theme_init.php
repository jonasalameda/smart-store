<?php
/**
 * Blocking script: set data-bs-theme before first paint (localStorage, else fridge-dashboard dark).
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
        : 'dark';
    document.documentElement.setAttribute('data-bs-theme', d);
  } catch (e) {
    document.documentElement.setAttribute('data-bs-theme', 'dark');
  }
})();
</script>
