<?php
require_once __DIR__ . '/functions.php';
$u = auth_user();
if (!$u) {
    redirect('login.php');
}
$role = $u['role'];

$navs = [
    'admin' => [
        ['dashboard', 'Dashboard',  'admin/dashboard.php'],
        ['orders',    'Orders',     'admin/orders.php'],
        ['customers', 'Customers',  'admin/customers.php'],
        ['employees', 'Employees',  'admin/employees.php'],
        ['inventory', 'Inventory',  'admin/inventory.php'],
        ['payments',  'Payments',   'admin/payments.php'],
        ['expenses',  'Expenses',   'admin/expenses.php'],
        ['reports',   'Reports',    'admin/reports.php'],
    ],
    'employee' => [
        ['orders', 'My Assignments', 'employee/orders.php'],
    ],
    'customer' => [
        ['dashboard', 'My Orders',   'customer/dashboard.php'],
    ],
];

$bodyData = 'data-role="' . e($role) . '" data-base="' . e(BASE_URL) . '"';
$pending = 0;
if ($role === 'admin') {
    $s = db()->query("SELECT COUNT(*) AS c, (SELECT COALESCE(MAX(id),0) FROM orders) AS m FROM orders WHERE status='pending'")->fetch();
    $pending = (int) $s['c'];
    $bodyData .= ' data-last="' . (int) $s['m'] . '"';
}

include __DIR__ . '/head.php';
?>
<body class="app-body" <?= $bodyData ?>>
<div class="app">
  <aside class="sidebar">
    <a class="brand" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(url('assets/img/racel-logo.png')) ?>" alt="RACEL Sash Factory" class="home-logo">
      <span class="brand-name">RACEL<small>Sash Factory</small></span>
    </a>
    <nav class="nav">
      <?php foreach ($navs[$role] as [$key, $label, $path]) { ?>
        <a href="<?= e(url($path)) ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>">
          <span><?= e($label) ?></span>
          <?php if ($role === 'admin' && $key === 'orders') { ?>
            <span class="count <?= $pending === 0 ? 'zero' : '' ?>" data-live="pending"><?= $pending ?></span>
          <?php } ?>
        </a>
      <?php } ?>
    </nav>
    <div class="user-box">
      <strong><?= e($u['full_name']) ?></strong>
      <span><?= e($role) ?></span><br>
      <a href="<?= e(url('logout.php')) ?>">Sign out</a>
    </div>
  </aside>
  <main class="main">
    <header class="topbar">
      <div>
        <h1><?= e($pageTitle ?? '') ?></h1>
        <?php if (!empty($subtitle)) { ?><p><?= e($subtitle) ?></p><?php } ?>
      </div>
      <?php if (!empty($topAction)) { echo $topAction; } ?>
    </header>
    <?php if ($f = flash()) { ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php } ?>
