(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var bar = document.querySelector('.scroll-bar');
  var nav = document.querySelector('.site-nav');
  function onScroll() {
    var h = document.documentElement;
    var pct = h.scrollTop / Math.max(1, h.scrollHeight - h.clientHeight) * 100;
    if (bar) bar.style.width = pct + '%';
    if (nav) nav.classList.toggle('scrolled', h.scrollTop > 30);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  var items = document.querySelectorAll('.reveal, .steps');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.15 });
    items.forEach(function (el) { io.observe(el); });
  } else {
    items.forEach(function (el) { el.classList.add('in'); });
  }


  var hero = document.querySelector('.hero');
  var art = document.querySelector('.hero-art');
  if (hero && !reduce) {
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      hero.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      hero.style.setProperty('--my', (e.clientY - r.top) + 'px');
      if (art) {
        var x = (e.clientX / window.innerWidth - 0.5), y = (e.clientY / window.innerHeight - 0.5);
        art.style.setProperty('--ry', (-6 + x * 10) + 'deg');
        art.style.setProperty('--rx', (3 - y * 10) + 'deg');
      }
    });
  }

  document.querySelectorAll('[data-count]').forEach(function (el) {
    var target = parseInt(el.getAttribute('data-count'), 10), suffix = el.getAttribute('data-suffix') || '';
    if (reduce) { el.textContent = target + suffix; return; }
    var start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / 1600);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
      if (p < 1) requestAnimationFrame(step);
    }
    setTimeout(function () { requestAnimationFrame(step); }, 700);
  });

  if (location.search.indexOf('track=') > -1) {
    var t = document.getElementById('track');
    if (t) setTimeout(function () { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 150);
  }
})();