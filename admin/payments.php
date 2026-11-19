<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$rows = db()->query("SELECT p.*, o.order_code, u.full_name AS customer_name
                     FROM payments p JOIN orders o ON o.id = p.order_id JOIN users u ON u.id = o.customer_id
                     ORDER BY p.id DESC")->fetchAll();
$total = array_sum(array_column($rows, 'amount'));

$pageTitle = 'Payments';
$subtitle = 'Every payment received, with the order it belongs to. Record new payments inside an order.';
$active = 'payments';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g3 mb">
  <div class="card stat reveal"><div class="label">Total received</div><div class="value"><?= e(money($total)) ?></div></div>
  <div class="card stat reveal"><div class="label">Transactions</div><div class="value" data-count="<?= count($rows) ?>">0</div></div>
</div>
<div class="card reveal">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Order</th><th>Customer</th><th>Type</th><th>Method</th><th class="right">Amount</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r) { ?>
        <tr>
          <td class="muted"><?= e(fdate($r['paid_at'], 'M d, Y h:i A')) ?></td>
          <td><a class="link-strong" href="<?= e(url('admin/order_view.php?id=' . $r['order_id'])) ?>"><?= e($r['order_code']) ?></a></td>
          <td><?= e($r['customer_name']) ?></td>
          <td><?= e(ucwords(str_replace('_', ' ', $r['type']))) ?></td>
          <td><?= e($r['method']) ?></td>
          <td class="right"><strong><?= e(money($r['amount'])) ?></strong></td>
        </tr>
      <?php } ?>
      <?php if (!$rows) { ?><tr><td colspan="6" class="empty">No payments recorded yet.</td></tr><?php } ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
