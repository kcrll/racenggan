<?php
require_once __DIR__ . '/../includes/functions.php';
$manager = require_role(['admin']);
$customerError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $customerError = 'Enter a name, valid email, and temporary password with at least 8 characters.';
    } else {
        try {
            $add = db()->prepare("INSERT INTO users (full_name,email,phone,password_hash,role) VALUES (?,?,?,?,'customer')");
            $add->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]);
            flash('success', 'Customer profile created. Give the customer their sign-in details securely.');
            redirect('admin/customers.php');
        } catch (PDOException $ex) { $customerError = 'That email address is already in use.'; }
    }
}

$rows = db()->query("SELECT u.id, u.full_name, u.email, u.phone, u.created_at,
        COUNT(o.id) AS orders,
        COALESCE(SUM(CASE WHEN o.status NOT IN ('pending','cancelled') THEN o.agreed_price - COALESCE(p.paid,0) ELSE 0 END),0) AS balance
    FROM users u
    LEFT JOIN orders o ON o.customer_id = u.id
    LEFT JOIN (SELECT order_id, SUM(amount) AS paid FROM payments GROUP BY order_id) p ON p.order_id = o.id
    WHERE u.role = 'customer'
    GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();

$pageTitle = 'Customers';
$subtitle = 'Customer records and order history. Create customer access when needed.';
$active = 'customers';
include __DIR__ . '/../includes/header.php';
?>
<div class="card mb reveal">
  <div class="card-head"><h2>Add customer</h2><span class="muted small">Manager-managed customer record</span></div>
  <?php if ($customerError) { ?><div class="alert alert-error"><?= e($customerError) ?></div><?php } ?>
  <form method="post"><?= csrf_field() ?>
    <div class="row3">
      <div class="field"><label>Full name</label><input name="full_name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Phone</label><input type="tel" name="phone"></div>
    </div>
    <div class="row"><div class="field"><label for="cust-password">Temporary password (minimum 8 characters)</label>
      <div class="password-field">
        <input type="password" id="cust-password" name="password" minlength="8" required>
        <button type="button" class="password-toggle" data-toggle-password="cust-password" aria-label="Show password" aria-pressed="false">Show</button>
      </div>
    </div><div class="field" style="display:flex;align-items:end"><button class="btn btn-primary" type="submit">Create customer</button></div></div>
  </form>
</div>
<div class="card reveal">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Outstanding</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r) { ?>
        <tr>
          <td><strong><?= e($r['full_name']) ?></strong></td>
          <td><?= e($r['email']) ?></td>
          <td><?= e($r['phone'] ?: '—') ?></td>
          <td><?= (int) $r['orders'] ?></td>
          <td><?= e(money($r['balance'])) ?></td>
          <td class="muted"><?= e(fdate($r['created_at'])) ?></td>
        </tr>
      <?php } ?>
      <?php if (!$rows) { ?><tr><td colspan="6" class="empty">No customer records yet.</td></tr><?php } ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
