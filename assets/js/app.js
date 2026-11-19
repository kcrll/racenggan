(function () {
  'use strict';

  var body = document.body;
  var base = body.dataset.base || '';

  document.querySelectorAll('[data-count]').forEach(function (el) {
    var target = parseInt(el.dataset.count, 10) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    var start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / 700, 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  document.querySelectorAll('input[type=range][data-out]').forEach(function (r) {
    var out = document.getElementById(r.dataset.out);
    function sync() { if (out) out.textContent = r.value + '%'; }
    r.addEventListener('input', sync);
    sync();
  });

  var stack = document.querySelector('.toast-stack');
  function toast(html) {
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      body.appendChild(stack);
    }
    var t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = html;
    stack.appendChild(t);
    setTimeout(function () {
      t.style.transition = 'opacity .4s, transform .4s';
      t.style.opacity = '0';
      t.style.transform = 'translateX(20px)';
      setTimeout(function () { t.remove(); }, 450);
    }, 8000);
  }

  document.querySelectorAll('.alert-success').forEach(function (a) {
    setTimeout(function () { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; }, 5000);
  });

  if (body.dataset.role === 'admin') {
    var lastId = parseInt(body.dataset.last || '0', 10);
    var busy = false;

    function esc(s) {
      var d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    function tick() {
      if (busy || document.hidden) return;
      busy = true;
      fetch(base + '/api/poll.php?last=' + lastId, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) {
          if (!d) return;
          Object.keys(d.stats).forEach(function (k) {
            document.querySelectorAll('[data-live="' + k + '"]').forEach(function (el) {
              el.textContent = d.stats[k];
              if (k === 'pending') el.classList.toggle('zero', Number(d.stats[k]) === 0);
            });
          });
          var tb = document.getElementById('liveOrders');
          if (tb && d.rows_html && tb.innerHTML !== d.rows_html) {
                tb.innerHTML = d.rows_html;
            }
          if (d.new_orders && d.new_orders.length) {
            d.new_orders.forEach(function (o) {
              toast('<strong>New order ' + esc(o.order_code) + '</strong><br>' + esc(o.customer_name) +
                ' · ' + esc(o.product_name) + '<br><a href="' + base + '/admin/order_view.php?id=' + o.id + '">Review order →</a>');
            });
          }
          lastId = Math.max(lastId, d.latest_id);
        })
        .catch(function () {})
        .then(function () { busy = false; });
    }
    setInterval(tick, 5000);
  }
})();
