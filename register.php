<?php
require __DIR__ . '/functions.php';
if (!empty($_SESSION['shop_user'])) redirect('index.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$p, $errors] = user_register_input($_POST);
    if (!$errors) {
        $exists = db()->prepare('SELECT id FROM shop_users WHERE email = ?');
        $exists->execute([$p['email']]);
        if ($exists->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $st = db()->prepare('INSERT INTO shop_users (name, email, password_hash) VALUES (?,?,?)');
            $st->execute([$p['name'], $p['email'], password_hash($p['password'], PASSWORD_DEFAULT)]);
            flash('success', 'Registration successful. Please log in.');
            redirect('login.php');
        }
    }
}
$title = 'Create account'; $ROOT = '';
include __DIR__ . '/partials/header.php';
?>
<div class="panel narrow">
  <h1>Create account</h1>
  <form method="post">
    <?= csrf_field() ?>
    <label>Full name <input name="name" value="<?= e($p['name'] ?? '') ?>" autocomplete="name" required></label>
    <?php if (isset($errors['name'])): ?><small class="err"><?= e($errors['name']) ?></small><?php endif; ?>

    <label>Email <input type="email" name="email" value="<?= e($p['email'] ?? '') ?>" autocomplete="email" required></label>
    <?php if (isset($errors['email'])): ?><small class="err"><?= e($errors['email']) ?></small><?php endif; ?>

    <label>Password <input type="password" name="password" autocomplete="new-password" required></label>
    <?php if (isset($errors['password'])): ?><small class="err"><?= e($errors['password']) ?></small><?php endif; ?>

    <label>Confirm password <input type="password" name="password_confirm" autocomplete="new-password" required></label>
    <?php if (isset($errors['password_confirm'])): ?><small class="err"><?= e($errors['password_confirm']) ?></small><?php endif; ?>

    <button class="btn">Register</button>
    <p class="muted">Already have an account? <a href="login.php">Log in</a></p>
  </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
