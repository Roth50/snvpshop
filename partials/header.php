<?php
// expects: $ROOT ('' or '../'), $title
$ROOT = $ROOT ?? '';
$flash = take_flash();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $title ? e($title) . ' · ' : '' ?><?= e(cfg('shop_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Khmer:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $ROOT ?>assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= $ROOT ?>index.php"><span class="logo">SNVP</span><span class="name">SNVP</span></a>
    <nav>
      <a href="<?= $ROOT ?>index.php">Shop</a>
      <a href="<?= $ROOT ?>cart.php">🛒 Cart <span class="badge"><?= cart_count() ?></span></a>
      <?php if (!empty($_SESSION['shop_user'])): $user = $_SESSION['shop_user']; ?>
        <span class="muted">Hi, <?= e($user['name']) ?></span>
        <form method="post" action="<?= $ROOT ?>logout.php" class="inline"><?= csrf_field() ?><button class="link">Logout</button></form>
      <?php elseif (!empty($_SESSION['shop_admin'])): ?>
        <a href="<?= $ROOT ?>admin/index.php">Products</a>
        <a href="<?= $ROOT ?>admin/orders.php">Orders</a>
        <form method="post" action="<?= $ROOT ?>admin/logout.php" class="inline"><?= csrf_field() ?><button class="link">Logout</button></form>
      <?php else: ?>
        <a href="<?= $ROOT ?>login.php">Login</a>
        <a href="<?= $ROOT ?>register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
