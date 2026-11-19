<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_role(['admin']);
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$st = $pdo->prepare(orders_base_sql() . ' WHERE o.id = ?');
$st->execute([$id]);
$o = $st->fetch();
if (!$o) {
    flash('error', 'Order not found.');
    redirect('admin/orders.php');
}

if (!$o['admin_seen']) {
    $pdo->prepare('UPDATE orders SET admin_seen = 1 WHERE id = ?')->execute([$id]);
}

$back = 'admin/order_view.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm' && $o['status'] === 'pending') {
        $price = max(0, (float) ($_POST['agreed_price'] ?? 0));
        $down  = max(0, (float) ($_POST['down_payment'] ?? 0));
        $days  = max(1, (int) ($_POST['est_days'] ?? 1));
        if ($down > $price) {
            flash('error', 'Down payment cannot be higher than the agreed price.');
            redirect($back);
        }
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE orders SET status='confirmed', agreed_price=?, est_days=?, est_completion=?, confirmed_at=NOW() WHERE id=?")
            ->execute([$price, $days, date('Y-m-d', strtotime("+$days days")), $id]);
        if ($down > 0) {
            $pdo->prepare("INSERT INTO payments (order_id, amount, type, method, note, recorded_by) VALUES (?,?, 'down_payment', ?, 'Down payment', ?)")
                ->execute([$id, $down, $_POST['method'] ?? 'Cash', $admin['id']]);
        }
        add_update($id, 'confirmed', 'Your order has been confirmed. Estimated completion in ' . $days . ' days.', $admin['id']);
        $pdo->commit();
        flash('success', 'Order confirmed. The customer can now follow the production progress.');
        redirect($back);
    }

    if ($action === 'cancel' && !in_array($o['status'], ['completed', 'cancelled'], true)) {
        $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$id]);
        add_update($id, 'cancelled', 'This order has been cancelled.', $admin['id']);
        flash('success', 'Order cancelled.');
        redirect($back);
    }

    if ($action === 'production' && !in_array($o['status'], ['pending', 'cancelled'], true)) {
        $newStatus = $_POST['status'] ?? $o['status'];
        if (!in_array($newStatus, ['confirmed', 'in_production', 'finishing', 'ready', 'completed'], true)) {
            $newStatus = $o['status'];
        }
        $progress = max(0, min(100, (int) ($_POST['progress'] ?? 0)));
        $days = max(0, (int) ($_POST['est_days'] ?? 0));
        $emps = array_unique(array_map('intval', (array) ($_POST['employees'] ?? [])));
        $note = trim($_POST['message'] ?? '');

        if ($newStatus === 'completed') $progress = 100;

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE orders SET status=?, progress=?, est_days=?, est_completion=?, completed_at=? WHERE id=?')
            ->execute([
                $newStatus, $progress, $days,
                date('Y-m-d', strtotime("+$days days")),
                $newStatus === 'completed' ? date('Y-m-d H:i:s') : null,
                $id,
            ]);
        $pdo->prepare('DELETE FROM order_assignments WHERE order_id = ?')->execute([$id]);
        $ins = $pdo->prepare('INSERT INTO order_assignments (order_id, employee_id) SELECT ?, id FROM users WHERE id = ? AND role = \'employee\' AND is_active = 1');
        foreach ($emps as $eid) {
            $ins->execute([$id, $eid]);
        }
        $count = count($emps);
        $msg = $note !== '' ? $note
            : status_label($newStatus) . ' · ' . $progress . '% complete · ' . $count . ' craftsmen assigned · estimated ' . $days . ' days remaining.';
        add_update($id, $newStatus, $msg, $admin['id']);
        $pdo->commit();
        flash('success', 'Production details updated and shared with the customer.');
        redirect($back);
    }

    if ($action === 'material_add' && !in_array($o['status'], ['cancelled'], true)) {
        $materialId = (int)($_POST['material_id'] ?? 0);
        $quantityUsed = (float)($_POST['quantity_used'] ?? 0);
        $checkMaterial = $pdo->prepare('SELECT id, quantity FROM materials WHERE id = ?');
        $checkMaterial->execute([$materialId]);
        $materialRow = $checkMaterial->fetch();
        if (!$materialRow || $quantityUsed <= 0) {
            flash('error', 'Choose a material and enter a quantity greater than zero.');
        } elseif ($quantityUsed > (float)$materialRow['quantity']) {
            flash('error', 'The quantity used cannot exceed the available stock.');
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO order_materials (order_id, material_id, quantity_used) VALUES (?,?,?)')->execute([$id, $materialId, $quantityUsed]);
            $pdo->prepare('UPDATE materials SET quantity = quantity - ? WHERE id = ?')->execute([$quantityUsed, $materialId]);
            $pdo->commit();
            flash('success', 'Material usage recorded and inventory stock updated.');
        }
        redirect($back);
    }
    if ($action === 'material_remove') {
        $rowId = (int)($_POST['material_row_id'] ?? 0);
        $findUsage = $pdo->prepare('SELECT material_id, quantity_used FROM order_materials WHERE id = ? AND order_id = ?');
        $findUsage->execute([$rowId, $id]);
        $usage = $findUsage->fetch();
        if ($usage) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE materials SET quantity = quantity + ? WHERE id = ?')->execute([$usage['quantity_used'], $usage['material_id']]);
            $pdo->prepare('DELETE FROM order_materials WHERE id = ? AND order_id = ?')->execute([$rowId, $id]);
            $pdo->commit();
            flash('success', 'Material usage removed and stock returned to inventory.');
        }
        redirect($back);
    }

    if ($action === 'payment' && $o['status'] !== 'cancelled' && $o['status'] !== 'pending') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $method = trim($_POST['method'] ?? 'Cash');
        $note   = trim($_POST['note'] ?? '');
        if ($amount <= 0) {
            flash('error', 'Enter a payment amount greater than zero.');
        } elseif ($amount > (float) $o['balance'] + 0.001) {
            flash('error', 'That payment is higher than the remaining balance (' . money($o['balance']) . ').');
        } else {
            $type = abs($amount - (float) $o['balance']) < 0.005 ? 'full' : 'partial';
            $pdo->prepare('INSERT INTO payments (order_id, amount, type, method, note, recorded_by) VALUES (?,?,?,?,?,?)')
                ->execute([$id, $amount, $type, $method, $note, $admin['id']]);
            add_update($id, $o['status'], 'Payment of ' . money($amount) . ' received. Thank you!', $admin['id']);
            flash('success', 'Payment recorded.');
        }
        redirect($back);
    }
    redirect($back);
}

$employees = $pdo->query("SELECT id, full_name FROM users WHERE role='employee' AND is_active=1 ORDER BY full_name")->fetchAll();
$assigned = $pdo->prepare('SELECT employee_id FROM order_assignments WHERE order_id = ?');
$assigned->execute([$id]);
$assignedIds = array_map('intval', $assigned->fetchAll(PDO::FETCH_COLUMN));

$ups = $pdo->prepare('SELECT * FROM order_updates WHERE order_id = ? ORDER BY id DESC');
$ups->execute([$id]);
$updates = $ups->fetchAll();

$materials = $pdo->query('SELECT id, name, unit, quantity FROM materials ORDER BY name')->fetchAll();
$usedMaterials = $pdo->prepare('SELECT om.id, om.quantity_used, m.name, m.unit FROM order_materials om JOIN materials m ON m.id = om.material_id WHERE om.order_id = ? ORDER BY om.id DESC');
$usedMaterials->execute([$id]);
$usedMaterials = $usedMaterials->fetchAll();

$pays = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC');
$pays->execute([$id]);
$payments = $pays->fetchAll();

$pageTitle = $o['order_code'];
$subtitle = $o['customer_name'] . ' · placed ' . fdate($o['created_at'], 'M d, Y h:i A');
$active = 'orders';
$topAction = status_badge($o['status']);
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g-main">
  <div class="grid">

    <?php if ($o['status'] === 'pending') { ?>
      <form class="card reveal" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="confirm">
        <h2>Confirm this order</h2>
        <p class="muted mb">Review the manager-recorded details, finalize the quotation, and confirm the order.</p>
        <div class="row3">
          <div class="field"><label>Agreed price (₱)</label><input type="number" step="0.01" min="0" name="agreed_price" value="<?= e($o['agreed_price']) ?>" required></div>
          <div class="field"><label>Down payment (₱)</label><input type="number" step="0.01" min="0" name="down_payment" value="0"></div>
          <div class="field"><label>Estimated days</label><input type="number" min="1" name="est_days" value="<?= (int) $o['est_days'] ?>" required></div>
        </div>
        <div class="field"><label>Payment method</label>
          <select name="method"><option>Cash</option><option>GCash</option><option>Bank transfer</option></select></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn btn-primary" type="submit">Confirm order</button>
        </div>
      </form>
    <?php } ?>

    <?php if (!in_array($o['status'], ['pending', 'cancelled'], true)) { ?>
      <form class="card reveal" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="production">
        <h2>Production tracking</h2>
        <p class="muted mb">Updates here are shown to the customer in their progress tracker.</p>
        <div class="row">
          <div class="field"><label>Stage</label>
            <select name="status">
              <?php foreach (['confirmed', 'in_production', 'finishing', 'ready', 'completed'] as $s) { ?>
                <option value="<?= e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
              <?php } ?>
            </select></div>
          <div class="field"><label>Estimated days remaining</label><input type="number" min="0" name="est_days" value="<?= (int) $o['est_days'] ?>"></div>
        </div>
        <div class="field"><label>Progress <strong id="pv"></strong></label>
          <input type="range" name="progress" min="0" max="100" step="5" value="<?= (int) $o['progress'] ?>" data-out="pv"></div>
        <div class="field"><label>Assigned employees</label>
          <?php if ($employees) { ?>
            <div class="checks">
              <?php foreach ($employees as $emp) { ?>
                <label class="check"><input type="checkbox" name="employees[]" value="<?= (int) $emp['id'] ?>" <?= in_array((int) $emp['id'], $assignedIds, true) ? 'checked' : '' ?>><?= e($emp['full_name']) ?></label>
              <?php } ?>
            </div>
          <?php } else { ?>
            <p class="muted small">No employees yet. <a href="<?= e(url('admin/employees.php')) ?>">Add an employee</a> first.</p>
          <?php } ?>
        </div>
        <div class="field"><label>Message to customer (optional)</label><textarea name="message" placeholder="Leave empty to send an automatic summary."></textarea></div>
        <button class="btn btn-primary" type="submit">Save &amp; notify customer</button>
      </form>
    <?php } ?>

    <div class="card reveal">
      <div class="card-head"><h2>Materials used</h2><span class="muted small">OrderMaterial records</span></div>
      <?php if ($usedMaterials) { ?>
        <div class="table-wrap"><table><thead><tr><th>Material</th><th>Quantity used</th><th></th></tr></thead><tbody>
          <?php foreach ($usedMaterials as $used) { ?><tr><td><?= e($used['name']) ?></td><td><?= e($used['quantity_used']) ?> <?= e($used['unit']) ?></td><td class="right"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="material_remove"><input type="hidden" name="material_row_id" value="<?= (int)$used['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Remove</button></form></td></tr><?php } ?>
        </tbody></table></div>
      <?php } else { ?><p class="muted small">No materials have been recorded for this order yet.</p><?php } ?>
      <?php if ($materials) { ?><form method="post" class="mt">
        <?= csrf_field() ?><input type="hidden" name="action" value="material_add">
        <div class="row"><div class="field"><label>Material</label><select name="material_id" required><option value="">Choose material</option><?php foreach ($materials as $material) { ?><option value="<?= (int)$material['id'] ?>"><?= e($material['name']) ?> (<?= e($material['quantity']) ?> <?= e($material['unit']) ?> in stock)</option><?php } ?></select></div>
        <div class="field"><label>Quantity used</label><input type="number" name="quantity_used" min="0.01" step="0.01" required></div></div>
        <button class="btn btn-primary btn-sm" type="submit">Add material usage</button>
      </form><?php } ?>
    </div>

    <div class="card reveal">
      <h2 style="margin-bottom:16px">Activity timeline</h2>
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
        <dt>Customer</dt><dd><?= e($o['customer_name']) ?></dd>
        <dt>Contact</dt><dd><?= e($o['customer_phone'] ?: '—') ?><br><span class="muted small"><?= e($o['customer_email']) ?></span></dd>
        <dt>Product</dt><dd><?= e($o['product_name']) ?> × <?= (int) $o['quantity'] ?></dd>
        <dt>Size</dt><dd><?= $o['width_cm'] ? e($o['width_cm']) . ' × ' . e($o['height_cm']) . ' cm' : '—' ?></dd>
        <dt>Wood</dt><dd><?= e($o['wood_type'] ?: '—') ?></dd>
        <dt>Notes</dt><dd><?= e($o['notes'] ?: '—') ?></dd>
        <dt>Due</dt><dd><?= e(fdate($o['est_completion'])) ?></dd>
      </dl>
    </div>

    <div class="card reveal">
      <h2 style="margin-bottom:14px">Payments</h2>
      <dl class="kv">
        <dt>Agreed price</dt><dd><?= e(money($o['agreed_price'])) ?></dd>
        <dt>Paid</dt><dd><?= e(money($o['paid'])) ?></dd>
        <dt>Balance</dt><dd><strong><?= e(money($o['balance'])) ?></strong></dd>
      </dl>
      <?php foreach ($payments as $p) { ?>
        <div class="card-head small" style="margin:12px 0 0"><span><?= e(fdate($p['paid_at'])) ?> · <?= e($p['method']) ?> · <?= e(str_replace('_', ' ', $p['type'])) ?></span><strong><?= e(money($p['amount'])) ?></strong></div>
      <?php } ?>
      <?php if (!in_array($o['status'], ['pending', 'cancelled'], true) && (float) $o['balance'] > 0) { ?>
        <form method="post" style="margin-top:18px">
          <?= csrf_field() ?><input type="hidden" name="action" value="payment">
          <div class="row">
            <div class="field"><label>Amount (₱)</label><input type="number" step="0.01" min="0.01" name="amount" required></div>
            <div class="field"><label>Method</label><select name="method"><option>Cash</option><option>GCash</option><option>Bank transfer</option></select></div>
          </div>
          <div class="field"><label>Note</label><input type="text" name="note"></div>
          <button class="btn btn-dark btn-sm" type="submit">Record payment</button>
        </form>
      <?php } ?>
    </div>

    <?php if (!in_array($o['status'], ['completed', 'cancelled'], true)) { ?>
      <form method="post" class="reveal">
        <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
        <button class="btn btn-danger btn-block" type="submit" data-confirm="Cancel this order? The customer will be notified.">Cancel order</button>
      </form>
    <?php } ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
