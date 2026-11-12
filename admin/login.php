<?php
require __DIR__ . '/../functions.php';
if (!empty($_SESSION['shop_admin'])) redirect('index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $identifier = trim((string) ($_POST['username'] ?? ''));
    $normalizedIdentifier = strtolower($identifier);
    $ok = (hash_equals(strtolower((string) cfg('admin_user')), $normalizedIdentifier)
           || hash_equals(strtolower((string) cfg('admin_email', '')), $normalizedIdentifier))
       && password_verify((string) ($_POST['password'] ?? ''), (string) cfg('admin_pass_hash'));
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['shop_admin'] = true;
        redirect('index.php');
    }
    flash('error', 'Invalid username or password.');
    redirect('login.php');
}
$title = 'Admin login'; $ROOT = '../';
include __DIR__ . '/../partials/header.php';
?>
<form method="post" class="panel narrow">
  <?= csrf_field() ?>
  <h1>Admin login</h1>
  <label>Username or email <input type="text" name="username" autocomplete="username" required></label>
  <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
  <button class="btn">Sign in</button>
</form>
<?php include __DIR__ . '/../partials/footer.php'; ?>
