/**
 * Click to toggle password visibility.
 * <button type="button" data-password-toggle-for="inputId" data-label-show="..." data-label-hide="...">
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-password-toggle-for]').forEach(function (btn) {
    var id = btn.getAttribute('data-password-toggle-for');
    var input = id ? document.getElementById(id) : null;
    if (!input) {
      return;
    }
    var labelShow = btn.getAttribute('data-label-show') || 'Show password';
    var labelHide = btn.getAttribute('data-label-hide') || 'Hide password';
    var icon = btn.querySelector('i');

    btn.setAttribute('type', 'button');
    btn.setAttribute('aria-label', labelShow);

    function setRevealed(show) {
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? labelHide : labelShow);
      if (icon) {
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
      }
    }

    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      setRevealed(show);
    });
  });
});
