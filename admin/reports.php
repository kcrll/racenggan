<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$pdo = db();

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
$from = $month . '-01';
$to   = date('Y-m-d', strtotime($from . ' +1 month'));

$scalar = function (string $sql, array $a) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($a);
    return (float) $s->fetchColumn();
};
$sales    = $scalar('SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at >= ? AND paid_at < ?', [$from, $to]);
$expenses = $scalar('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ? AND expense_date < ?', [$from, $to]);
$newOrders = (int) $scalar('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ?', [$from, $to]);
$completed = (int) $scalar("SELECT COUNT(*) FROM orders WHERE status='completed' AND completed_at >= ? AND completed_at < ?", [$from, $to]);

$series = new DynamicArray(4);
for ($i = 5; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("$from -$i month"));
    $end   = date('Y-m-d', strtotime("$start +1 month"));
    $series->append([
        'label' => date('M', strtotime($start)),
        'sales' => $scalar('SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at >= ? AND paid_at < ?', [$start, $end]),
        'exp'   => $scalar('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ? AND expense_date < ?', [$start, $end]),
    ]);
}
$months = $series->toArray();
$maxVal = max(1, ...array_map(fn($m) => max($m['sales'], $m['exp']), $months));

$owing = $pdo->query("SELECT u.full_name, COUNT(o.id) AS orders, SUM(o.agreed_price - COALESCE(p.paid,0)) AS balance
    FROM orders o JOIN users u ON u.id = o.customer_id
    LEFT JOIN (SELECT order_id, SUM(amount) AS paid FROM payments GROUP BY order_id) p ON p.order_id = o.id
    WHERE o.status NOT IN ('pending','cancelled')
    GROUP BY u.id HAVING balance > 0")->fetchAll();
$sortedOwing = merge_sort($owing, fn($a, $b) => (float) $b['balance'] <=> (float) $a['balance']);
$totalOwing = array_sum(array_column($owing, 'balance'));

$pageTitle = 'Financial Reports';
$subtitle = 'Summary for ' . date('F Y', strtotime($from)) . '.';
$active = 'reports';
$topAction = '<div class="no-print" style="display:flex;gap:10px"><form method="get"><input class="inline-input" type="month" name="month" value="' . e($month) . '" onchange="this.form.submit()"></form>'
           . '<button class="btn btn-dark" onclick="window.print()">Print report</button></div>';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g4 mb">
  <div class="card stat reveal"><div class="label">Sales</div><div class="value"><?= e(money($sales)) ?></div></div>
  <div class="card stat reveal"><div class="label">Expenses</div><div class="value"><?= e(money($expenses)) ?></div></div>
  <div class="card stat reveal"><div class="label">Net income</div><div class="value" style="<?= $sales - $expenses < 0 ? 'color:#8a3a2e' : '' ?>"><?= e(money($sales - $expenses)) ?></div></div>
  <div class="card stat reveal"><div class="label">Orders</div><div class="value"><?= $newOrders ?></div><div class="sub"><?= $completed ?> completed</div></div>
</div>

<div class="grid g2">
  <div class="card reveal">
    <h2 style="margin-bottom:6px">Sales vs. expenses</h2>
    <p class="muted small">Last six months</p>
    <div class="bars">
      <?php foreach ($months as $m) { ?>
        <div class="bar-col">
          <div class="bar-pair">
            <div class="bar sales" title="Sales <?= e(money($m['sales'])) ?>" style="height:<?= round($m['sales'] / $maxVal * 100) ?>%"></div>
            <div class="bar exp" title="Expenses <?= e(money($m['exp'])) ?>" style="height:<?= round($m['exp'] / $maxVal * 100) ?>%"></div>
          </div>
          <span class="muted small"><?= e($m['label']) ?></span>
        </div>
      <?php } ?>
    </div>
    <div class="legend"><span><i style="background:#c8862a"></i>Sales</span><span><i style="background:#6f4a32"></i>Expenses</span></div>
  </div>

  <div class="card reveal">
    <div class="card-head"><h2>Unpaid balances</h2><strong><?= e(money($totalOwing)) ?></strong></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Orders</th><th class="right">Balance</th></tr></thead>
        <tbody>
        <?php foreach ($sortedOwing as $i => $r) { ?>
          <tr><td class="muted"><?= $i + 1 ?></td><td><?= e($r['full_name']) ?></td><td><?= (int) $r['orders'] ?></td><td class="right"><strong><?= e(money($r['balance'])) ?></strong></td></tr>
        <?php } ?>
        <?php if (!$sortedOwing) { ?><tr><td colspan="4" class="empty">No unpaid balances.</td></tr><?php } ?>
        </tbody>
      </table>
    </div>
    <p class="muted small" style="margin-top:12px">Sorted from highest to lowest.</p>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
