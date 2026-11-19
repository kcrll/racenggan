<?php
// one time setup to
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$tablesReady = true;
try {
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
} catch (PDOException $ex) {
    $tablesReady = false;
    $adminCount = 0;
}

$errors = [];
$done = false;
$old = ['full_name' => '', 'email' => ''];

if ($tablesReady && $adminCount === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Enter the admin\'s full name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $ins = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?, 'admin')");
        $ins->execute([$old['full_name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT)]);
        $done = true;
        $adminCount = 1;
    }
}

$pageTitle = 'Admin setup';
include __DIR__ . '/includes/head.php';
?>
<body>
<div class="auth">
  <section class="auth-art">
    <a class="brand" href="<?= e(url('index.php')) ?>">
      <span class="brand-mark">R</span>
      <span class="brand-name">RACEL<small>Sash Factory</small></span>
    </a>
    <div>
      <h2>First-time setup</h2>
      <p>Create the owner / manager account that controls orders, production, inventory, payments, and reports.</p>
    </div>
  </section>
  <section class="auth-panel">
    <div class="auth-box">
      <?php if (!$tablesReady) { ?>
        <h1>Database not ready</h1>
        <p class="sub">The tables were not found.</p>
        <div class="alert alert-error">Open phpMyAdmin, click <b>Import</b>, choose <b>database.sql</b>, then reload this page.</div>
      <?php } elseif ($done) { ?>
        <h1>Admin created</h1>
        <p class="sub">Your administrator account is ready.</p>
        <div class="alert alert-success">Sign in with <b><?= e($old['email']) ?></b>. For safety, delete <b>setup.php</b> when you are done.</div>
        <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>">Go to sign in</a>
      <?php } elseif ($adminCount > 0) { ?>
        <h1>Setup complete</h1>
        <p class="sub">An administrator account already exists, so this page is locked.</p>
        <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>">Go to sign in</a>
      <?php } else { ?>
        <h1>Create admin account</h1>
        <p class="sub">This page only works once.</p>
        <?php foreach ($errors as $er) { ?><div class="alert alert-error"><?= e($er) ?></div><?php } ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="field"><label>Full name</label><input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required></div>
          <div class="field"><label>Email (used to sign in)</label><input type="email" name="email" value="<?= e($old['email']) ?>" required></div>
          <div class="row">
            <div class="field"><label for="password">Password</label>
              <div class="password-field">
                <input type="password" id="password" name="password" minlength="8" required>
                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">Show</button>
              </div>
            </div>
            <div class="field"><label for="password2">Confirm</label>
              <div class="password-field">
                <input type="password" id="password2" name="password2" minlength="8" required>
                <button type="button" class="password-toggle" data-toggle-password="password2" aria-label="Show password" aria-pressed="false">Show</button>
              </div>
            </div>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Create admin</button>
        </form>
      <?php } ?>
    </div>
  </section>
</div>
</body>
</html>
