<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/algorithms.php';

const ORDER_STATUSES = [
    'pending'       => 'Pending',
    'confirmed'     => 'Confirmed',
    'in_production' => 'In Production',
    'finishing'     => 'Finishing',
    'ready'         => 'Ready for Pickup',
    'completed'     => 'Completed',
    'cancelled'     => 'Cancelled',
];

function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }
function money($n): string { return '₱' . number_format((float) $n, 2); }
function fdate($d, string $fmt = 'M d, Y'): string { return $d ? date($fmt, strtotime((string) $d)) : '—'; }

function flash(?string $type = null, ?string $msg = null)
{
    if ($type !== null) {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expired. Go back, refresh the page, and try again.');
    }
}
function auth_user(): ?array { return $_SESSION['user'] ?? null; }

function home_for(string $role): string
{
    return match ($role) {
        'admin'    => 'admin/dashboard.php',
        'employee' => 'employee/orders.php',
        default    => 'customer/dashboard.php',
    };
}

function require_role(array $roles): array
{
    $u = auth_user();
    if (!$u) {
        redirect('login.php');
    }
    if (!in_array($u['role'], $roles, true)) {
        redirect(home_for($u['role']));
    }
    return $u;
}

function status_label(string $s): string { return ORDER_STATUSES[$s] ?? ucfirst($s); }
function status_badge(string $s): string
{
    return '<span class="badge badge-' . e($s) . '">' . e(status_label($s)) . '</span>';
}

function orders_base_sql(): string
{
    return "SELECT o.*, pr.name AS product_name, u.full_name AS customer_name, u.phone AS customer_phone,
                   u.email AS customer_email,
                   COALESCE(p.paid, 0) AS paid,
                   (o.agreed_price - COALESCE(p.paid, 0)) AS balance,
                   (SELECT COUNT(*) FROM order_assignments a WHERE a.order_id = o.id) AS emp_count
            FROM orders o
            JOIN products pr ON pr.id = o.product_id
            JOIN users u     ON u.id  = o.customer_id
            LEFT JOIN (SELECT order_id, SUM(amount) AS paid FROM payments GROUP BY order_id) p
                   ON p.order_id = o.id ";
}

function add_update(int $orderId, string $status, string $message, ?int $userId): void
{
    $st = db()->prepare('INSERT INTO order_updates (order_id, status, message, created_by) VALUES (?,?,?,?)');
    $st->execute([$orderId, $status, $message, $userId]);
    $tracking = db()->prepare('INSERT INTO product_tracking (order_id, production_status, remarks) VALUES (?,?,?)');
    $tracking->execute([$orderId, $status, $message]);
}

function dashboard_stats(): array
{
    $pdo = db();
    $monthStart = date('Y-m-01');
    $one = function (string $sql, array $args = []) use ($pdo) {
        $s = $pdo->prepare($sql);
        $s->execute($args);
        return $s->fetchColumn() ?: 0;
    };

    $outstanding = $one("SELECT COALESCE(SUM(o.agreed_price - COALESCE(p.paid,0)),0)
        FROM orders o LEFT JOIN (SELECT order_id, SUM(amount) paid FROM payments GROUP BY order_id) p ON p.order_id = o.id
        WHERE o.status NOT IN ('pending','cancelled')");

    return [
        'pending'     => (int) $one("SELECT COUNT(*) FROM orders WHERE status='pending'"),
        'active'      => (int) $one("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed','in_production','finishing')"),
        'ready'       => (int) $one("SELECT COUNT(*) FROM orders WHERE status='ready'"),
        'outstanding' => money($outstanding),
        'sales'       => money($one('SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at >= ?', [$monthStart])),
        'expenses'    => money($one('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= ?', [$monthStart])),
        'last_id'     => (int) $one('SELECT COALESCE(MAX(id),0) FROM orders'),
    ];
}

/** Table rows for the live order list (shared by dashboard and the polling API). */
function render_order_rows(array $rows): string
{
    ob_start();
    foreach ($rows as $o) { ?>
        <tr class="<?= $o['admin_seen'] ? '' : 'is-new' ?>">
            <td>
                <a class="link-strong" href="<?= e(url('admin/order_view.php?id=' . $o['id'])) ?>"><?= e($o['order_code']) ?></a>
                <?php if (!$o['admin_seen']) { ?><span class="tag-new">New</span><?php } ?>
            </td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['product_name']) ?> <span class="muted">× <?= (int) $o['quantity'] ?></span></td>
            <td><?= status_badge($o['status']) ?></td>
            <td style="min-width:120px"><div class="progress"><span style="width:<?= (int) $o['progress'] ?>%"></span></div></td>
            <td><?= money($o['balance']) ?></td>
            <td class="muted"><?= e(fdate($o['created_at'], 'M d, h:i A')) ?></td>
        </tr>
    <?php }
    if (!$rows) {
        echo '<tr><td colspan="7" class="empty">No orders recorded yet. Use Record Order to add the first customer request.</td></tr>';
    }
    return ob_get_clean();
}
