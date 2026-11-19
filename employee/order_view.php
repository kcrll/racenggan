<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['employee']);
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare(orders_base_sql() . ' WHERE o.id = ? AND EXISTS (SELECT 1 FROM order_assignments a WHERE a.order_id = o.id AND a.employee_id = ?)');
$st->execute([$id, $u['id']]);
$o = $st->fetch();
if (!$o) {
    flash('error', 'That order is not assigned to you.');
    redirect('employee/orders.php');
}

$allowed = ['in_production', 'finishing', 'ready'];
$locked = in_array($o['status'], ['pending', 'completed', 'cancelled'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    csrf_verify();
    $status = $_POST['status'] ?? '';
    $progress = max(0, min(100, (int) ($_POST['progress'] ?? 0)));
    $note = trim($_POST['message'] ?? '');

    if (in_array($status, $allowed, true)) {
        if ($status === 'ready') $progress = max($progress, 95);
        $pdo->prepare('UPDATE orders SET status = ?, progress = ? WHERE id = ?')->execute([$status, $progress, $id]);
        $msg = $note !== '' ? $note : 'Production update: ' . status_label($status) . ' (' . $progress . '% complete).';
        add_update($id, $status, $msg, $u['id']);
        flash('success', 'Production status updated.');
    }
    redirect('employee/order_view.php?id=' . $id);
}

$pageTitle = $o['order_code'];
$subtitle = $o['product_name'] . ' × ' . (int) $o['quantity'];
$active = 'orders';
$topAction = status_badge($o['status']);
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g2">
  <div class="card reveal">
    <h2 style="margin-bottom:14px">Product specifications</h2>
    <dl class="kv">
      <dt>Product</dt><dd><?= e($o['product_name']) ?></dd>
      <dt>Quantity</dt><dd><?= (int) $o['quantity'] ?></dd>
      <dt>Width × Height</dt><dd><?= $o['width_cm'] ? e($o['width_cm']) . ' × ' . e($o['height_cm']) . ' cm' : '—' ?></dd>
      <dt>Wood type</dt><dd><?= e($o['wood_type'] ?: '—') ?></dd>
      <dt>Notes</dt><dd><?= e($o['notes'] ?: '—') ?></dd>
      <dt>Due date</dt><dd><?= e(fdate($o['est_completion'])) ?></dd>
    </dl>
  </div>
  <form class="card reveal" method="post">
    <h2 style="margin-bottom:14px">Update production</h2>
    <?php if ($locked) { ?>
      <p class="muted">This order is not open for production updates right now.</p>
    <?php } else { ?>
      <?= csrf_field() ?>
      <div class="field"><label>Status</label>
        <select name="status">
          <?php foreach ($allowed as $s) { ?><option value="<?= e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option><?php } ?>
        </select></div>
      <div class="field"><label>Progress <strong id="pv"></strong></label>
        <input type="range" name="progress" min="0" max="100" step="5" value="<?= (int) $o['progress'] ?>" data-out="pv"></div>
      <div class="field"><label>Note for the customer (optional)</label><textarea name="message"></textarea></div>
      <button class="btn btn-primary" type="submit">Save update</button>
    <?php } ?>
  </form>
</div>
<p class="mt"><a href="<?= e(url('employee/orders.php')) ?>">← Back to assignments</a></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
