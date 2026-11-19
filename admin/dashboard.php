<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$pdo = db();

$stats = dashboard_stats();
$rows = $pdo->query(orders_base_sql() . ' ORDER BY o.id DESC LIMIT 8')->fetchAll();
$board = $pdo->query(orders_base_sql() . " WHERE o.status IN ('confirmed','in_production','finishing','ready') ORDER BY o.est_completion ASC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$subtitle = 'Manager overview of customer orders, production activity, and finances.';
$active = 'dashboard';
$topAction = '<a class="btn btn-primary" href="' . e(url('admin/create_order.php')) . '">+ Record order</a>';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g4 mb">
  <div class="card stat reveal"><div class="label">Pending orders</div><div class="value" data-live="pending"><?= (int) $stats['pending'] ?></div><div class="sub">Waiting for confirmation</div></div>
  <div class="card stat reveal"><div class="label">In production</div><div class="value" data-live="active"><?= (int) $stats['active'] ?></div><div class="sub">Confirmed to finishing</div></div>
  <div class="card stat reveal"><div class="label">Outstanding balance</div><div class="value" data-live="outstanding"><?= e($stats['outstanding']) ?></div><div class="sub">From confirmed orders</div></div>
  <div class="card stat reveal"><div class="label">Sales this month</div><div class="value" data-live="sales"><?= e($stats['sales']) ?></div><div class="sub">Expenses: <span data-live="expenses"><?= e($stats['expenses']) ?></span></div></div>
</div>

<div class="grid g-main">
  <div class="card reveal">
    <div class="card-head"><h2>Recent orders</h2><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/orders.php')) ?>">View all</a></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Product</th><th>Status</th><th>Progress</th><th>Balance</th><th>Placed</th></tr></thead>
        <tbody id="liveOrders"><?= render_order_rows($rows) ?></tbody>
      </table>
    </div>
  </div>

  <div class="card reveal">
    <div class="card-head"><h2>Production board</h2></div>
    <?php foreach ($board as $b) { ?>
      <a href="<?= e(url('admin/order_view.php?id=' . $b['id'])) ?>" style="display:block;color:inherit;margin-bottom:18px">
        <div class="card-head" style="margin-bottom:6px">
          <strong><?= e($b['order_code']) ?></strong><?= status_badge($b['status']) ?>
        </div>
        <div class="progress"><span style="width:<?= (int) $b['progress'] ?>%"></span></div>
        <div class="muted small" style="margin-top:6px"><?= (int) $b['emp_count'] ?> assigned · due <?= e(fdate($b['est_completion'])) ?></div>
      </a>
    <?php } ?>
    <?php if (!$board) { ?><p class="muted">Nothing in production yet. Confirm a pending order to begin.</p><?php } ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
