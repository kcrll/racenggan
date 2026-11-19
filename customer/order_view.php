<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['customer']);
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare(orders_base_sql() . ' WHERE o.id = ? AND o.customer_id = ?');
$st->execute([$id, $u['id']]);
$o = $st->fetch();
if (!$o) {
    flash('error', 'Order not found.');
    redirect('customer/dashboard.php');
}

$ups = $pdo->prepare('SELECT * FROM order_updates WHERE order_id = ? ORDER BY id DESC');
$ups->execute([$id]);
$updates = $ups->fetchAll();

$pays = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC');
$pays->execute([$id]);
$payments = $pays->fetchAll();

$flow = ['pending' => 'Placed', 'confirmed' => 'Confirmed', 'in_production' => 'Production', 'finishing' => 'Finishing', 'ready' => 'Ready', 'completed' => 'Completed'];
$keys = array_keys($flow);
$curIdx = array_search($o['status'], $keys, true);
$cancelled = $o['status'] === 'cancelled';

$pageTitle = $o['order_code'];
$subtitle = $o['product_name'] . ' × ' . (int) $o['quantity'];
$active = 'dashboard';
$topAction = status_badge($o['status']);
include __DIR__ . '/../includes/header.php';
?>
<div class="card mb reveal">
  <?php if ($cancelled) { ?>
    <div class="alert alert-error" style="margin:0">This order was cancelled.</div>
  <?php } else { ?>
    <div class="stepper">
      <?php foreach ($keys as $i => $k) {
          $cls = $i < $curIdx ? 'done' : ($i === $curIdx ? 'current' : '');
      ?>
        <div class="step <?= $cls ?>"><i><?= $i < $curIdx ? '✓' : $i + 1 ?></i><?= e($flow[$k]) ?></div>
      <?php } ?>
    </div>
    <div class="progress big mt"><span style="width:<?= (int) $o['progress'] ?>%"></span></div>
    <p class="muted small" style="margin-top:8px"><?= (int) $o['progress'] ?>% complete</p>
  <?php } ?>
</div>

<div class="grid g-main">
  <div class="grid">
    <div class="card reveal">
      <div class="card-head"><h2>Production</h2></div>
      <?php if ($o['status'] === 'pending') { ?>
        <p class="muted">Your order is waiting for confirmation. Once confirmed, you will see the assigned craftsmen and estimated completion date here.</p>
      <?php } else { ?>
        <dl class="kv">
          <dt>Craftsmen assigned</dt><dd><?= (int) $o['emp_count'] ?></dd>
          <dt>Estimated days</dt><dd><?= $o['est_days'] !== null ? (int) $o['est_days'] . ' days' : '—' ?></dd>
          <dt>Estimated completion</dt><dd><?= e(fdate($o['est_completion'])) ?></dd>
          <dt>Confirmed on</dt><dd><?= e(fdate($o['confirmed_at'])) ?></dd>
        </dl>
      <?php } ?>
    </div>

    <div class="card reveal">
      <h2 style="margin-bottom:16px">Progress updates</h2>
      <ul class="timeline">
        <?php foreach ($updates as $up) { ?>
          <li><div class="when"><?= e(fdate($up['created_at'], 'M d, Y · h:i A')) ?> · <?= e(status_label($up['status'])) ?></div><?= e($up['message']) ?></li>
        <?php } ?>
      </ul>
    </div>
  </div>

  <div class="grid">
    <div class="card reveal">
      <h2 style="margin-bottom:14px">Order details</h2>
      <dl class="kv">
        <dt>Placed</dt><dd><?= e(fdate($o['created_at'], 'M d, Y')) ?></dd>
        <dt>Size</dt><dd><?= $o['width_cm'] ? e($o['width_cm']) . ' × ' . e($o['height_cm']) . ' cm' : '—' ?></dd>
        <dt>Wood</dt><dd><?= e($o['wood_type'] ?: '—') ?></dd>
        <dt>Notes</dt><dd><?= e($o['notes'] ?: '—') ?></dd>
      </dl>
    </div>
    <div class="card reveal">
      <h2 style="margin-bottom:14px">Payments</h2>
      <dl class="kv">
        <dt><?= $o['status'] === 'pending' ? 'Estimated price' : 'Agreed price' ?></dt><dd><?= e(money($o['agreed_price'])) ?></dd>
        <dt>Paid</dt><dd><?= e(money($o['paid'])) ?></dd>
        <dt>Balance</dt><dd><strong><?= e(money($o['balance'])) ?></strong></dd>
      </dl>
      <?php if ($payments) { ?>
        <hr style="border:0;border-top:1px solid #eadfcc;margin:16px 0">
        <?php foreach ($payments as $p) { ?>
          <div class="card-head small" style="margin-bottom:6px"><span><?= e(fdate($p['paid_at'])) ?> · <?= e($p['method']) ?></span><strong><?= e(money($p['amount'])) ?></strong></div>
        <?php } ?>
      <?php } ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
