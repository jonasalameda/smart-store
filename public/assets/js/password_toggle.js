/**
 * Password visibility:
 * - Default: click the button to toggle show / hide.
 * - Hold: set data-password-hold="1" on the button — press and hold to show, release anywhere to hide.
 *
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
    var holdMode = btn.getAttribute('data-password-hold') === '1' || btn.getAttribute('data-password-hold') === 'true';
    var icon = btn.querySelector('i');

    btn.setAttribute('type', 'button');

    function setRevealed(show) {
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? labelHide : labelShow);
      if (icon) {
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
      }
    }

    if (holdMode) {
      btn.style.userSelect = 'none';
      btn.setAttribute('aria-label', labelShow);
      var holding = false;

      function endHold() {
        if (!holding) {
          return;
        }
        holding = false;
        window.removeEventListener('pointerup', endHold, true);
        window.removeEventListener('pointercancel', endHold, true);
        setRevealed(false);
      }

      btn.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'mouse' && e.button !== 0) {
          return;
        }
        if (holding) {
          return;
        }
        holding = true;
        setRevealed(true);
        window.addEventListener('pointerup', endHold, true);
        window.addEventListener('pointercancel', endHold, true);
      });
      return;
    }

    btn.setAttribute('aria-label', labelShow);
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      setRevealed(show);
    });
  });
});
