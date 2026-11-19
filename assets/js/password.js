(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var target = e.target;
    if (!target || !target.closest) return;

    var btn = target.closest('[data-toggle-password]');
    if (!btn) return;

    var input = document.getElementById(btn.getAttribute('data-toggle-password'));
    if (!input) return;

    var showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';
    btn.textContent = showing ? 'Hide' : 'Show';
    btn.setAttribute('aria-label', (showing ? 'Hide' : 'Show') + ' password');
    btn.setAttribute('aria-pressed', String(showing));
    input.focus();
  });
})();
