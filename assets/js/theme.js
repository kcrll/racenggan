(function () {
  var KEY = 'racel-theme';
  var root = document.documentElement;

  function saved() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
  var theme = saved() || (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  root.setAttribute('data-theme', theme);

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'theme-toggle no-print';

    function paint() {
      var dark = root.getAttribute('data-theme') === 'dark';
      btn.textContent = dark ? '☀️' : '🌙';
      btn.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
      btn.setAttribute('aria-label', btn.title);
    }

    btn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem(KEY, next); } catch (e) {}
      paint();
    });

    paint();
    document.body.appendChild(btn);
  });
})();