<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
            flash('error', 'Enter a name, a valid email, and a password of at least 8 characters.');
        } else {
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$email]);

            if ($chk->fetch()) {
                flash('error', 'That email is already used by another account.');
            } else {
                $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, role)
                    VALUES (?, ?, ?, ?, 'employee')
                ")->execute([
                    $name,
                    $email,
                    $phone,
                    password_hash($pass, PASSWORD_DEFAULT)
                ]);

                flash('success', 'Employee account created.');
            }
        }
    }

    if ($action === 'toggle') {
        $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role = 'employee'")->execute([(int) $_POST['id']]);
        flash('success', 'Employee status updated.');
    }
    redirect('admin/employees.php');
}

$rows = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM order_assignments a JOIN orders o ON o.id = a.order_id
                                   WHERE a.employee_id = u.id AND o.status IN ('confirmed','in_production','finishing')) AS active_jobs
                     FROM users u WHERE u.role = 'employee' ORDER BY u.full_name")->fetchAll();

$pageTitle = 'Employees';
$subtitle = 'Craftsmen who can be assigned to orders.';
$active = 'employees';
include __DIR__ . '/../includes/header.php';
?>
<div class="grid g-main">
  <div class="card reveal">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Active jobs</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r) { ?>
          <tr>
            <td><strong><?= e($r['full_name']) ?></strong><div class="muted small"><?= e($r['phone'] ?: '') ?></div><div class="muted small"><?= e($r['skills'] ?: 'Skills not specified') ?></div></td>
            <td><?= e($r['email']) ?></td>
            <td><?= (int) $r['active_jobs'] ?></td>
            <td><span class="badge <?= $r['is_active'] ? 'badge-ok' : 'badge-low' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="right">
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit"><?= $r['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
            </td>
          </tr>
        <?php } ?>
        <?php if (!$rows) { ?><tr><td colspan="5" class="empty">No employees yet. Add the first one.</td></tr><?php } ?>
        </tbody>
      </table>
    </div>
  </div>
  <form class="card reveal" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h2 style="margin-bottom:14px">Add employee</h2>
    <div class="field"><label>Full name</label><input type="text" name="full_name" required></div>
    <div class="field"><label>Email</label><input type="email" name="email" required></div>
    <div class="field"><label>Phone</label><input type="tel" name="phone"></div>
    <div class="field"><label>Skills</label><input type="text" name="skills" placeholder="e.g. Cutting, assembly, sanding"></div>
    <div class="field"><label>Experience</label><input type="text" name="experience" placeholder="e.g. 3 years"></div>
    <div class="field"><label for="emp-password">Temporary password</label>
      <div class="password-field">
        <input type="password" id="emp-password" name="password" minlength="8" required>
        <button type="button" class="password-toggle" data-toggle-password="emp-password" aria-label="Show password" aria-pressed="false">Show</button>
      </div>
      <div class="hint">At least 8 characters.</div>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
