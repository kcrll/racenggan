<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['customer']);

$st = db()->prepare(orders_base_sql() . ' WHERE o.customer_id = ? ORDER BY o.id DESC');
$st->execute([$u['id']]);
$orders = $st->fetchAll();

$open = array_filter($orders, fn($o) => !in_array($o['status'], ['completed', 'cancelled'], true));
$owed = array_sum(array_map(fn($o) => in_array($o['status'], ['pending', 'cancelled'], true) ? 0 : $o['balance'], $orders));

$pageTitle = 'My Orders';
$subtitle = 'Welcome, ' . $u['full_name'] . '. Here is where your orders stand.';
$active = 'dashboard';
$topAction = '<a class="btn btn-ghost" href="' . e(url('index.php#track')) . '">Track another order</a>';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g3 mb">
  <div class="card stat reveal"><div class="label">Total orders</div><div class="value" data-count="<?= count($orders) ?>">0</div></div>
  <div class="card stat reveal"><div class="label">In progress</div><div class="value" data-count="<?= count($open) ?>">0</div></div>
  <div class="card stat reveal"><div class="label">Balance due</div><div class="value"><?= e(money($owed)) ?></div></div>
</div>

<?php if (!$orders) { ?>
  <div class="card empty-state reveal">
    <h3>No orders yet</h3>
    <p style="margin:8px 0 18px">When RACEL records an order under your customer details, it will appear here.</p>
    <a class="btn btn-primary" href="<?= e(url('index.php#track')) ?>">Track an order</a>
  </div>
<?php } else { ?>
  <div class="grid g2">
    <?php foreach ($orders as $o) { ?>
      <a class="card hover reveal" href="<?= e(url('customer/order_view.php?id=' . $o['id'])) ?>" style="color:inherit">
        <div class="card-head" style="margin-bottom:10px">
          <div><strong style="font-size:16px"><?= e($o['order_code']) ?></strong><div class="muted small"><?= e($o['product_name']) ?> × <?= (int) $o['quantity'] ?></div></div>
          <?= status_badge($o['status']) ?>
        </div>
        <div class="progress"><span style="width:<?= (int) $o['progress'] ?>%"></span></div>
        <div class="card-head" style="margin:10px 0 0">
          <span class="muted small"><?= (int) $o['progress'] ?>% complete<?= $o['est_completion'] && !in_array($o['status'], ['completed','cancelled'], true) ? ' · Est. ' . e(fdate($o['est_completion'])) : '' ?></span>
          <span class="small"><strong><?= e(money($o['balance'])) ?></strong> <span class="muted">balance</span></span>
        </div>
      </a>
    <?php } ?>
  </div>
<?php } ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
