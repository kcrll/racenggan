<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = strtoupper(trim($_POST['item_code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        if ($code === '' || $name === '') {
            flash('error', 'Item code and name are required.');
        } else {
            try {
                $pdo->prepare('INSERT INTO materials (item_code, name, unit, quantity, reorder_level, unit_cost) VALUES (?,?,?,?,?,?)')
                    ->execute([$code, $name, trim($_POST['unit'] ?? 'pcs'), (float) $_POST['quantity'], (float) $_POST['reorder_level'], (float) $_POST['unit_cost']]);
                flash('success', 'Material added.');
            } catch (PDOException $ex) {
                flash('error', 'That item code already exists.');
            }
        }
    }

    if ($action === 'adjust') {
        $delta = (float) ($_POST['delta'] ?? 0);
        $pdo->prepare('UPDATE materials SET quantity = GREATEST(0, quantity + ?) WHERE id = ?')->execute([$delta, (int) $_POST['id']]);
        flash('success', 'Stock updated.');
    }
    redirect('admin/inventory.php' . (!empty($_POST['q']) ? '?q=' . urlencode($_POST['q']) : ''));
}

$items = $pdo->query('SELECT * FROM materials ORDER BY name')->fetchAll();

$ht = new HashTable(10);
foreach ($items as $it) {
    $ht->put($it['item_code'], $it);
}

$q = strtoupper(trim($_GET['q'] ?? ''));
$found = null;
if ($q !== '') {
    $found = $ht->get($q);
}

$pageTitle = 'Inventory';
$subtitle = 'Raw materials.';
$active = 'inventory';
include __DIR__ . '/../includes/header.php';
?>
<div class="card mb reveal">
  <form method="get" class="inline-form">
    <div class="field"><label>Find by item code</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="e.g. MHG-001"></div>
    <button class="btn btn-dark" type="submit">Look up</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/inventory.php')) ?>">Reset</a>
  </form>
  <?php if ($q !== '') { ?>
    <p class="small" style="margin-top:12px">
      hash(“<?= e($q) ?>”) = sum of character codes % <?= (int) $ht->size() ?> = bucket <strong><?= (int) $ht->bucketOf($q) ?></strong>.
      <?= $found ? 'Record found: <strong>' . e($found['name']) . '</strong>, ' . e($found['quantity']) . ' ' . e($found['unit']) . ' in stock.' : '<span style="color:#8a3a2e">Not Found.</span>' ?>
    </p>
  <?php } ?>
</div>

<div class="grid g-main">
  <div class="card reveal">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Code</th><th>Material</th><th>In stock</th><th>Reorder at</th><th>Unit cost</th><th>Bucket</th><th>Adjust</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it) {
            $low = (float) $it['quantity'] <= (float) $it['reorder_level']; ?>
          <tr style="<?= $found && $found['id'] == $it['id'] ? 'background:#fbefd5' : '' ?>">
            <td><strong><?= e($it['item_code']) ?></strong></td>
            <td><?= e($it['name']) ?></td>
            <td><?= e(rtrim(rtrim(number_format((float) $it['quantity'], 2, '.', ''), '0'), '.')) ?> <span class="muted small"><?= e($it['unit']) ?></span>
                <span class="badge <?= $low ? 'badge-low' : 'badge-ok' ?>"><?= $low ? 'Low' : 'OK' ?></span></td>
            <td><?= e($it['reorder_level']) ?></td>
            <td><?= e(money($it['unit_cost'])) ?></td>
            <td class="muted"><?= (int) $ht->bucketOf($it['item_code']) ?></td>
            <td>
              <form method="post" class="inline-form" style="flex-wrap:nowrap">
                <?= csrf_field() ?><input type="hidden" name="action" value="adjust"><input type="hidden" name="id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
                <input class="inline-input" style="width:84px" type="number" step="0.01" name="delta" placeholder="± qty" required>
                <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
              </form>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <form class="card reveal" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h2 style="margin-bottom:14px">Add material</h2>
    <div class="row">
      <div class="field"><label>Item code</label><input type="text" name="item_code" placeholder="TEK-001" required></div>
      <div class="field"><label>Unit</label><input type="text" name="unit" value="pcs"></div>
    </div>
    <div class="field"><label>Name</label><input type="text" name="name" required></div>
    <div class="row3">
      <div class="field"><label>Qty</label><input type="number" step="0.01" min="0" name="quantity" value="0"></div>
      <div class="field"><label>Reorder</label><input type="number" step="0.01" min="0" name="reorder_level" value="0"></div>
      <div class="field"><label>Cost</label><input type="number" step="0.01" min="0" name="unit_cost" value="0"></div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Add to inventory</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
