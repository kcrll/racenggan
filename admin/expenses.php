<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_role(['admin']);
$pdo = db();

$categories = ['Materials', 'Transportation', 'Equipment maintenance', 'Utilities', 'Wages', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $desc = trim($_POST['description'] ?? '');
        $amt  = (float) ($_POST['amount'] ?? 0);
        $cat  = in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Other';
        $date = $_POST['expense_date'] ?? date('Y-m-d');
        if ($desc === '' || $amt <= 0) {
            flash('error', 'Enter a description and an amount greater than zero.');
        } else {
            $pdo->prepare('INSERT INTO expenses (description, category, amount, expense_date, created_by) VALUES (?,?,?,?,?)')
                ->execute([$desc, $cat, $amt, $date, $admin['id']]);
            flash('success', 'Expense recorded.');
        }
    }
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Expense removed.');
    }
    redirect('admin/expenses.php');
}

$da = new DynamicArray(4);
foreach ($pdo->query('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC') as $row) {
    $da->append($row);
}
$all = $da->toArray();

$q = trim($_GET['q'] ?? '');
$list = $all;
$comparisons = 0;
if ($q !== '') {
    $texts = array_map(fn($r) => $r['description'] . ' ' . $r['category'], $all);
    $hits = linear_search_all($texts, $q, $comparisons);
    $list = array_map(fn($i) => $all[$i], $hits);
}
$total = array_sum(array_column($list, 'amount'));

$pageTitle = 'Expenses';
$subtitle = 'Materials, transportation, maintenance, and other operating costs.';
$active = 'expenses';
include __DIR__ . '/../includes/header.php';
?>
<div class="card mb reveal">
  <form method="get" class="inline-form">
    <div class="field"><label>Search expenses by keyword</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="e.g. varnish"></div>
    <button class="btn btn-dark" type="submit">Search</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/expenses.php')) ?>">Reset</a>
  </form>
  <?php if ($q !== '') { ?>
    <p class="muted small" style="margin-top:12px">
      <?= $list ? count($list) . ' match(es)' : '<span style="color:#8a3a2e">Not Found</span>' ?> · checked <?= (int) $comparisons ?> entries one by one (linear search).
    </p>
  <?php } ?>
</div>

<div class="grid g-main">
  <div class="card reveal">
    <div class="card-head"><h2>Expense records</h2><strong><?= e(money($total)) ?></strong></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Description</th><th>Category</th><th class="right">Amount</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $r) { ?>
          <tr>
            <td class="muted"><?= e(fdate($r['expense_date'])) ?></td>
            <td><?= e($r['description']) ?></td>
            <td><span class="badge badge-confirmed"><?= e($r['category']) ?></span></td>
            <td class="right"><strong><?= e(money($r['amount'])) ?></strong></td>
            <td class="right">
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete this expense?">Delete</button></form>
            </td>
          </tr>
        <?php } ?>
        <?php if (!$list) { ?><tr><td colspan="5" class="empty">No expenses to show.</td></tr><?php } ?>
        </tbody>
      </table>
    </div>
    <p class="muted small" style="margin-top:14px">Storage array: <?= (int) $da->size() ?> records, capacity <?= (int) $da->capacity() ?> (doubled <?= (int) $da->resizes ?> time<?= $da->resizes === 1 ? '' : 's' ?>).</p>
  </div>

  <form class="card reveal" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h2 style="margin-bottom:14px">Add expense</h2>
    <div class="field"><label>Description</label><input type="text" name="description" placeholder="e.g. Varnish, 5 liters" required></div>
    <div class="field"><label>Category</label>
      <select name="category"><?php foreach ($categories as $c) { ?><option><?= e($c) ?></option><?php } ?></select></div>
    <div class="row">
      <div class="field"><label>Amount (₱)</label><input type="number" step="0.01" min="0.01" name="amount" required></div>
      <div class="field"><label>Date</label><input type="date" name="expense_date" value="<?= e(date('Y-m-d')) ?>" required></div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Save expense</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
