<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['employee']);

$st = db()->prepare(orders_base_sql() . " WHERE EXISTS (SELECT 1 FROM order_assignments a WHERE a.order_id = o.id AND a.employee_id = ?)
                                          ORDER BY (o.status IN ('completed','cancelled')), o.est_completion ASC, o.id DESC");
$st->execute([$u['id']]);
$orders = $st->fetchAll();

$pageTitle = 'My Assignments';
$subtitle = 'Orders assigned to you by the manager.';
$active = 'orders';
include __DIR__ . '/../includes/header.php';
?>
<div class="card reveal">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order</th><th>Product</th><th>Specifications</th><th>Status</th><th>Progress</th><th>Due</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o) { ?>
        <tr>
          <td><strong><?= e($o['order_code']) ?></strong></td>
          <td><?= e($o['product_name']) ?> <span class="muted">× <?= (int) $o['quantity'] ?></span></td>
          <td class="small"><?= $o['width_cm'] ? e($o['width_cm']) . ' × ' . e($o['height_cm']) . ' cm, ' : '' ?><?= e($o['wood_type']) ?></td>
          <td><?= status_badge($o['status']) ?></td>
          <td style="min-width:110px"><div class="progress"><span style="width:<?= (int) $o['progress'] ?>%"></span></div></td>
          <td class="muted small"><?= e(fdate($o['est_completion'])) ?></td>
          <td class="right"><a class="btn btn-ghost btn-sm" href="<?= e(url('employee/order_view.php?id=' . $o['id'])) ?>">Open</a></td>
        </tr>
      <?php } ?>
      <?php if (!$orders) { ?><tr><td colspan="7" class="empty">No orders assigned to you yet.</td></tr><?php } ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
