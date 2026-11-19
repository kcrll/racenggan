<?php
require_once __DIR__ . '/includes/functions.php';

if (auth_user()) {
    redirect(home_for(auth_user()['role']));
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user && $user['is_active'] && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ];
        redirect(home_for($user['role']));
    }
    $error = 'Incorrect email or password.';
}

$pageTitle = 'Sign in';
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
      <h2>Crafted to fit. Built to last.</h2>
      <p>Custom wooden windows, doors, and cabinets, made to your exact measurements and tracked from first order to final finish.</p>
      <div class="points"><span>Solid mahogany &amp; narra</span><span>Made to measure</span><span>Live order tracking</span></div>
    </div>
  </section>
  <section class="auth-panel">
    <div class="auth-box">
      <h1>Welcome back</h1>
      <p class="sub">Sign in to manage or track your orders.</p>
      <?php if ($error) { ?><div class="alert alert-error"><?= e($error) ?></div><?php } ?>
      <?php if ($f = flash()) { ?><div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php } ?>
      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field"><label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus></div>
        <div class="field"><label for="password">Password</label>
          <div class="password-field">
            <input type="password" id="password" name="password" required>
            <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">Show</button>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
      </form>
      <p class="auth-foot">New customer? <a href="<?= e(url('register.php')) ?>">Create an account</a><br>
        <a href="<?= e(url('index.php')) ?>">← Back to website</a></p>
    </div>
  </section>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
