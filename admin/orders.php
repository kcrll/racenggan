<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$pdo = db();

$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$all = $pdo->query(orders_base_sql() . ' ORDER BY o.id DESC')->fetchAll();

$list = $all;
$searchNote = '';
$searchFail = false;

if ($q !== '') {
    /* Order ID lookup through the hash table (Chapter 9): average O(1) retrieval by key. */
    $ht = new HashTable(32);
    foreach ($all as $o) {
        $ht->put(strtoupper($o['order_code']), $o);
    }
    $key = strtoupper($q);
    if (ctype_digit($key)) $key = 'ORD-' . $key;
    $hit = $ht->get($key);
    if ($hit) {
        $list = [$hit];
        $searchNote = 'Found ' . $key . ' in hash bucket ' . $ht->bucketOf($key) . ' of ' . $ht->size() . '.';
    } else {
        $list = [];
        $searchFail = true;
    }
} elseif ($status !== '' && isset(ORDER_STATUSES[$status])) {
    $list = array_values(array_filter($all, fn($o) => $o['status'] === $status));
}

$pageTitle = 'Orders';
$subtitle = 'Orders recorded and coordinated by the manager.';
$topAction = '<a class="btn btn-primary" href="' . e(url('admin/create_order.php')) . '">+ Record order</a>';
$active = 'orders';
include __DIR__ . '/../includes/header.php';
?>
<div class="card mb reveal">
  <form method="get" class="inline-form">
    <div class="field"><label>Order ID</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="ORD-1045 or 1045"></div>
    <div class="field"><label>Status</label>
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach (ORDER_STATUSES as $k => $v) { ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php } ?>
      </select></div>
    <button class="btn btn-dark" type="submit">Search</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/orders.php')) ?>">Reset</a>
  </form>
  <?php if ($searchNote) { ?><p class="muted small" style="margin-top:12px"><?= e($searchNote) ?></p><?php } ?>
  <?php if ($searchFail) { ?><p class="small" style="margin-top:12px;color:#8a3a2e">Not Found. No order matches “<?= e($q) ?>”.</p><?php } ?>
</div>

<div class="card reveal">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order</th><th>Customer</th><th>Product</th><th>Status</th><th>Progress</th><th>Balance</th><th>Placed</th></tr></thead>
      <tbody><?= render_order_rows($list) ?></tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
