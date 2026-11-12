<?php
require __DIR__ . '/functions.php';
if (!empty($_SESSION['shop_user'])) redirect('index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $credentials = user_login_input($_POST);
    $email = $credentials['email'];
    $password = $credentials['password'];
    $st = db()->prepare('SELECT * FROM shop_users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user && password_verify($password, (string) $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['shop_user'] = ['id' => (int) $user['id'], 'name' => (string) $user['name'], 'email' => (string) $user['email']];
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect('index.php');
    }
    flash('error', 'Invalid email or password.');
    redirect('login.php');
}
$title = 'Log in'; $ROOT = '';
include __DIR__ . '/partials/header.php';
?>
<div class="panel narrow">
  <h1>Log in</h1>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" autocomplete="email" required></label>
    <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn">Sign in</button>
    <p class="muted">No account yet? <a href="register.php">Register here</a></p>
  </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
