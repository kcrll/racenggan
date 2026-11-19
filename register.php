<?php
require_once __DIR__ . '/includes/functions.php';

if (auth_user()) {
    redirect(home_for(auth_user()['role']));
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Please enter your full name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$old['email']]);
        if ($chk->fetch()) {
            $errors[] = 'That email is already registered. Try signing in instead.';
        }
    }

    if (!$errors) {
        $ins = db()->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?,?,?,?, 'customer')");
        $ins->execute([$old['full_name'], $old['email'], $old['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
        flash('success', 'Account created. You can sign in now.');
        redirect('login.php');
    }
}

$pageTitle = 'Create account';
include __DIR__ . '/includes/head.php';
?>
<body>
<div class="auth">
  <section class="auth-art">
    <a class="brand" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(url('assets/img/racel-logo.png')) ?>" alt="RACEL Sash Factory" class="home-logo">
      <span class="brand-name">RACEL<small>Sash Factory</small></span>
    </a>
    <div>
      <h2>Your orders, all in one place.</h2>
      <p>Create an account to view orders recorded by RACEL, follow production updates, and check payment details.</p>
      <div class="points"><span>Order tracker</span><span>Payment balance</span><span>Progress updates</span></div>
    </div>
  </section>
  <section class="auth-panel">
    <div class="auth-box">
      <h1>Create your account</h1>
      <p class="sub">Customer accounts are for viewing and tracking orders recorded by RACEL.</p>
      <?php foreach ($errors as $er) { ?><div class="alert alert-error"><?= e($er) ?></div><?php } ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label>Full name</label><input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required></div>
        <div class="row">
          <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($old['email']) ?>" required></div>
          <div class="field"><label>Phone</label><input type="tel" name="phone" value="<?= e($old['phone']) ?>"></div>
        </div>
        <div class="row">
          <div class="field"><label for="password">Password</label>
            <div class="password-field">
              <input type="password" id="password" name="password" required minlength="8">
              <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">Show</button>
            </div>
          </div>
          <div class="field"><label for="password2">Confirm</label>
            <div class="password-field">
              <input type="password" id="password2" name="password2" required minlength="8">
              <button type="button" class="password-toggle" data-toggle-password="password2" aria-label="Show password" aria-pressed="false">Show</button>
            </div>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
      </form>
      <p class="auth-foot">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
    </div>
  </section>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
