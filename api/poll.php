<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$u = auth_user();
if (!$u || $u['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$last = (int) ($_GET['last'] ?? 0);
$pdo  = db();

$stats = dashboard_stats();
$latest = $stats['last_id'];

$new = $pdo->prepare(orders_base_sql() . ' WHERE o.id > ? ORDER BY o.id ASC LIMIT 10');
$new->execute([$last]);
$newOrders = array_map(fn($o) => [
    'id'            => (int) $o['id'],
    'order_code'    => $o['order_code'],
    'customer_name' => $o['customer_name'],
    'product_name'  => $o['product_name'],
], $new->fetchAll());

$rows = $pdo->query(orders_base_sql() . ' ORDER BY o.id DESC LIMIT 8')->fetchAll();

echo json_encode([
    'stats'      => $stats,
    'latest_id'  => $latest,
    'new_orders' => $newOrders,
    'rows_html'  => render_order_rows($rows),
]);
