<?php
require_once __DIR__ . '/includes/functions.php';

$user = auth_user();
$products = [];
try {
    $products = db()->query('SELECT * FROM products WHERE is_active = 1 ORDER BY category, name')->fetchAll();
} catch (Throwable $ex) {
    $products = [];
}

$track = trim($_GET['track'] ?? '');
$trackPhone = trim($_GET['phone'] ?? '');
$result = null;
$trackMsg = '';
$steps = 0;
if ($track !== '' && $trackPhone !== '') {
    $digits = (int) preg_replace('/\D/', '', $track);
    $list = array_map('intval', db()->query('SELECT order_no FROM orders WHERE order_no IS NOT NULL ORDER BY order_no ASC')->fetchAll(PDO::FETCH_COLUMN));
    $pos = binary_search($list, $digits, $steps);
    if ($pos >= 0) {
        $st = db()->prepare('SELECT o.order_code, o.status, o.progress, o.est_completion, o.est_days, pr.name AS product_name, u.phone AS customer_phone,
                                    (SELECT COUNT(*) FROM order_assignments a WHERE a.order_id = o.id) AS emp_count
                             FROM orders o JOIN products pr ON pr.id = o.product_id JOIN users u ON u.id = o.customer_id WHERE o.order_no = ?');
        $st->execute([$list[$pos]]);
        $candidate = $st->fetch();
        if ($candidate && preg_replace('/\D/', '', (string) $candidate['customer_phone']) === preg_replace('/\D/', '', $trackPhone)) {
            $result = $candidate;
        } else {
            $trackMsg = 'Order not found. Check your order code and registered phone number.';
        }
    } else {
        $trackMsg = 'Not Found. Please check the Order ID and try again.';
    }
}

$marquee = $products ? array_column($products, 'name') : ['Window Sashes', 'Doors', 'Cabinets', 'Furniture', 'Custom Orders'];

$pageTitle = 'Homepage';
include __DIR__ . '/includes/head.php';
?>
<body class="home">
<div class="scroll-bar"></div>

<nav class="site-nav">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <img src="<?= e(url('assets/img/racel-logo.png')) ?>" alt="RACEL Sash Factory" class="home-logo">
    <span class="brand-name">RACEL<small>Sash Factory</small></span>
  </a>
  <div class="site-links">
    <a href="#how">How it works</a>
    <a href="#products">Products</a>
    <a href="#track">Track order</a>
    <?php if ($user) { ?>
      <a class="btn btn-primary btn-sm" href="<?= e(url(home_for($user['role']))) ?>">Open dashboard</a>
    <?php } else { ?>
      <a class="btn btn-primary btn-sm" href="<?= e(url('login.php')) ?>">Log in</a>
    <?php } ?>
  </div>
</nav>

<header class="hero">
  <span class="blob b1"></span><span class="blob b2"></span>
  <div>
    <span class="eyebrow rise">Custom woodworking <span class="leaf-sway">🍃</span></span>
    <h1 class="rise">Crafted wood for <br><span class="word-highlight">your home.</span></h1>
    <p class="lead rise">RACEL Sash Factory builds window sashes, doors, cabinets, and furniture to your exact measurements. We record every order, coordinate production, and keep you updated from confirmation to final finish.</p>
    <div class="actions rise">
      <a class="btn btn-primary" href="#products">Explore products</a>
      <a class="btn btn-ghost" href="#track">Track an order</a>
    </div>
    <div class="hero-stats rise">
      <div><b data-count="10" data-suffix="+">0</b><span>Product types</span></div>
      <div><b data-count="100" data-suffix="%">0</b><span>Made to order</span></div>
      <div><b data-count="24" data-suffix="/7">0</b><span>Order tracking</span></div>
    </div>
  </div>
  <div class="hero-wrap">
    <div class="hero-art"><img src="<?= e(url('assets/img/hero.jpg')) ?>" alt="Handcrafted RACEL woodwork"></div>
    <span class="chip c1">🪵 Solid hardwood</span>
    <span class="chip c2">📐 Built to your size</span>
    <span class="chip c3">✅ Live order updates</span>
  </div>
</header>

<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <?php for ($i = 0; $i < 4; $i++) { foreach ($marquee as $m) { ?>
      <span><?= e($m) ?> &nbsp;✦</span>
    <?php } } ?>
  </div>
</div>

<section class="section alt" id="how">
  <div class="section-head reveal"><span class="kicker">The process</span><h2>From idea to finished wood</h2><p>A simple process, with updates at every step.</p></div>
  <div class="grid g3 steps">
    <div class="card hover reveal d1"><div class="feature-icon">1</div><h3>Share your requirements</h3><p class="muted">Tell RACEL what you need, including measurements, material preferences, and quantity.</p></div>
    <div class="card hover reveal d2"><div class="feature-icon">2</div><h3>Manager records your order</h3><p class="muted">The manager records the details, confirms the price, and coordinates the production team.</p></div>
    <div class="card hover reveal d3"><div class="feature-icon">3</div><h3>Follow production</h3><p class="muted">Use your order code and registered phone number to check progress and estimated completion.</p></div>
  </div>
</section>

<section class="section" id="products">
  <div class="section-head reveal"><span class="kicker">Our work</span><h2>What we make</h2><p>Every piece is built to order. Prices shown are starting estimates; final price is confirmed after review.</p></div>
  <div class="grid g3">
    <?php foreach ($products as $i => $p) { ?>
      <div class="card hover reveal d<?= ($i % 3) + 1 ?>">
        <span class="badge badge-confirmed"><?= e($p['category']) ?></span>
        <h3 style="margin-top:12px"><?= e($p['name']) ?></h3>
        <p class="muted small" style="margin-top:6px"><?= e($p['description']) ?></p>
        <div class="price">From <?= e(money($p['base_price'])) ?></div>
        <p class="muted small">About <?= (int) $p['default_days'] ?> days</p>
      </div>
    <?php } ?>
    <?php if (!$products) { ?><p class="muted">Products will appear here once the database is set up.</p><?php } ?>
  </div>
</section>

<section class="section alt" id="track">
  <div class="track-box reveal">
    <div class="section-head"><span class="kicker">Order status</span><h2>Track your order</h2><p>Enter your order code and the phone number registered with RACEL.</p></div>
    <form method="get" action="#track" class="inline-form">
      <div class="field"><label>Order code</label><input type="text" name="track" value="<?= e($track) ?>" placeholder="ORD-1045" required></div>
      <div class="field"><label>Registered phone number</label><input type="tel" name="phone" value="<?= e($trackPhone) ?>" placeholder="09XXXXXXXXX" required></div>
      <button class="btn btn-primary" type="submit">Check status</button>
    </form>
    <?php if ($result) { ?>
      <div class="card mt">
        <div class="card-head"><h3><?= e($result['order_code']) ?> · <?= e($result['product_name']) ?></h3><?= status_badge($result['status']) ?></div>
        <div class="progress big"><span style="width:<?= (int) $result['progress'] ?>%"></span></div>
        <p class="muted small" style="margin-top:10px">
          <?= (int) $result['progress'] ?>% complete
          <?php if ($result['est_completion'] && !in_array($result['status'], ['completed', 'cancelled'], true)) { ?>
            · Estimated completion <?= e(fdate($result['est_completion'])) ?>
          <?php } ?>
          <?php if ((int) $result['emp_count'] > 0) { ?> · <?= (int) $result['emp_count'] ?> craftsmen assigned<?php } ?>
        </p>
        <p class="muted small">Order verified using your registered phone number. Sign in to see your full order history and payment details.</p>
      </div>
    <?php } elseif ($track !== '' && $trackPhone === '') { ?>
      <div class="alert alert-info mt">Please enter the phone number registered with this order.</div>
    <?php } elseif ($trackMsg) { ?>
      <div class="alert alert-error mt"><?= e($trackMsg) ?></div>
    <?php } ?>
  </div>
</section>

<footer class="site-footer">
  <span>© <?= date('Y') ?> RACEL Sash Factory. Custom woodworking.</span>
</footer>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<script src="<?= e(url('assets/js/home.js')) ?>"></script>
</body>
</html>