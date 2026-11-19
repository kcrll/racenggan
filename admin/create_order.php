<?php
require_once __DIR__ . '/../includes/functions.php';
$manager = require_role(['admin']);
$pdo = db();
$customers = $pdo->query("SELECT id, full_name, phone, email FROM users WHERE role='customer' AND is_active=1 ORDER BY full_name")->fetchAll();
$products = $pdo->query("SELECT id, name, base_price, default_days FROM products WHERE is_active=1 ORDER BY category, name")->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $width = trim($_POST['width_cm'] ?? '');
    $height = trim($_POST['height_cm'] ?? '');
    $wood = trim($_POST['wood_type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $price = max(0, (float)($_POST['agreed_price'] ?? 0));
    $days = max(1, (int)($_POST['est_days'] ?? 1));
    $c = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='customer' AND is_active=1");
    $c->execute([$customerId]);
    $productCheck = $pdo->prepare('SELECT id FROM products WHERE id=? AND is_active=1');
    $productCheck->execute([$productId]);
    if (!$c->fetch() || !$productCheck->fetch()) {
        $error = 'Choose a valid customer and product.';
    } elseif (($width !== '' && (!is_numeric($width) || (float)$width <= 0)) || ($height !== '' && (!is_numeric($height) || (float)$height <= 0))) {
        $error = 'Measurements must be positive numbers.';
    } else {
        $pdo->beginTransaction();
        try {
            $next = (int)$pdo->query('SELECT COALESCE(MAX(order_no),1000)+1 FROM orders')->fetchColumn();
            $code = 'ORD-' . $next;
            $ins = $pdo->prepare("INSERT INTO orders (order_no, order_code, customer_id, product_id, manager_id, quantity, width_cm, height_cm, wood_type, notes, agreed_price, status, progress, est_days, est_completion, admin_seen) VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending',0,?,?,1)");
            $ins->execute([$next, $code, $customerId, $productId, $manager['id'], $quantity, $width === '' ? null : $width, $height === '' ? null : $height, $wood ?: null, $notes ?: null, $price, $days, date('Y-m-d', strtotime('+' . $days . ' days'))]);
            $orderId = (int)$pdo->lastInsertId();
            add_update($orderId, 'pending', 'Order recorded by the RACEL manager. Awaiting confirmation.', $manager['id']);
            $pdo->commit();
            flash('success', 'Order ' . $code . ' has been recorded successfully.');
            redirect('admin/order_view.php?id=' . $orderId);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $error = 'The order could not be saved. Please check the details and try again.';
        }
    }
}
$pageTitle = 'Record an order';
$subtitle = 'The manager records customer requirements and coordinates the order.';
$active = 'orders';
include __DIR__ . '/../includes/header.php';
?>
<div class="card reveal order-entry-card">
  <?php if ($error) { ?><div class="alert alert-error"><?= e($error) ?></div><?php } ?>
  <?php if (!$customers) { ?>
    <div class="empty-state"><h3>No customer records yet</h3><p class="muted" style="margin:8px 0 18px">Add the customer first, then return here to record their order.</p><a class="btn btn-primary" href="<?= e(url('admin/customers.php')) ?>">Add customer</a></div>
  <?php } else { ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="section-head"><h2>Customer and product</h2><p>Select the customer and the wooden product requested.</p></div>
    <div class="row">
      <div class="field"><label>Customer</label><select name="customer_id" required><option value="">Select customer</option><?php foreach ($customers as $c) { ?><option value="<?= (int)$c['id'] ?>" <?= (string)($_POST['customer_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?><?= $c['phone'] ? ' · ' . e($c['phone']) : '' ?></option><?php } ?></select></div>
      <div class="field"><label>Product</label><select name="product_id" id="productSelect" required><option value="">Select product</option><?php foreach ($products as $pr) { ?><option value="<?= (int)$pr['id'] ?>" data-price="<?= e($pr['base_price']) ?>" data-days="<?= (int)$pr['default_days'] ?>" <?= (string)($_POST['product_id'] ?? '') === (string)$pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option><?php } ?></select></div>
    </div>
    <div class="section-head mt"><h2>Specifications</h2><p>Record the measurements and preferences discussed with the customer.</p></div>
    <div class="row3">
      <div class="field"><label>Quantity</label><input type="number" name="quantity" min="1" value="<?= e($_POST['quantity'] ?? '1') ?>" required></div>
      <div class="field"><label>Width (cm)</label><input type="number" name="width_cm" min="0.01" step="0.01" value="<?= e($_POST['width_cm'] ?? '') ?>" placeholder="Optional"></div>
      <div class="field"><label>Height (cm)</label><input type="number" name="height_cm" min="0.01" step="0.01" value="<?= e($_POST['height_cm'] ?? '') ?>" placeholder="Optional"></div>
    </div>
    <div class="field"><label>Preferred wood / material</label><input type="text" name="wood_type" value="<?= e($_POST['wood_type'] ?? '') ?>" placeholder="e.g. Mahogany, Narra, Pine"></div>
    <div class="field"><label>Product specifications and notes</label><textarea name="notes" placeholder="Design details, finish, hardware, delivery instructions..."><?= e($_POST['notes'] ?? '') ?></textarea></div>
    <div class="section-head mt"><h2>Initial quotation</h2><p>Enter the estimated price and expected production period. The manager can confirm these after reviewing the order.</p></div>
    <div class="row">
      <div class="field"><label>Estimated / agreed price (₱)</label><input type="number" name="agreed_price" id="priceInput" min="0" step="0.01" value="<?= e($_POST['agreed_price'] ?? '0') ?>" required></div>
      <div class="field"><label>Estimated days</label><input type="number" name="est_days" id="daysInput" min="1" value="<?= e($_POST['est_days'] ?? '7') ?>" required></div>
    </div>
    <div class="actions"><button class="btn btn-primary" type="submit">Save order</button><span></span><a class="btn btn-ghost" href="<?= e(url('admin/orders.php')) ?>">Cancel</a></div>
  </form>
  <?php } ?>
</div>
<script>
(function () {
  var product = document.getElementById('productSelect');
  if (!product) return;
  product.addEventListener('change', function () {
    var option = product.options[product.selectedIndex];
    if (option && option.value) {
      document.getElementById('priceInput').value = option.dataset.price || 0;
      document.getElementById('daysInput').value = option.dataset.days || 7;
    }
  });
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
