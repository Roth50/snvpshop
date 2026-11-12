<?php
require __DIR__ . '/functions.php';
$order = my_order((int) ($_GET['id'] ?? 0));
$ROOT = '';
if (!$order || $order['status'] !== 'paid') { http_response_code(404); $title = 'Not found'; include __DIR__ . '/partials/header.php'; echo '<h1>Order not found</h1><p><a class="btn" href="index.php">Back to shop</a></p>'; include __DIR__ . '/partials/footer.php'; exit; }
$st = db()->prepare('SELECT * FROM shop_order_items WHERE order_id = ?');
$st->execute([$order['id']]);
$items = $st->fetchAll();
$_SESSION['cart'] = [];
$title = 'Order confirmed';
include __DIR__ . '/partials/header.php';
?>
<div class="panel narrow center">
  <div class="success-mark">✓</div>
  <h1>Thank you! Your order is confirmed.</h1>
  <p>Order ID: <strong>#<?= (int) $order['id'] ?></strong></p>
</div>
<div class="panel narrow">
  <h2>Customer details</h2>
  <p><strong><?= e($order['customer_name']) ?></strong><br><?= e($order['email']) ?><br><?= e($order['phone']) ?></p>
  <p class="prewrap"><?= e($order['address']) ?></p>
  <h2>Items</h2>
  <ul class="summary">
    <?php foreach ($items as $i): ?><li><span><?= e($i['name']) ?> × <?= (int) $i['quantity'] ?></span><span><?= money($i['unit_price'] * $i['quantity'], $order['currency']) ?></span></li><?php endforeach; ?>
  </ul>
  <p class="row between"><strong>Total Amount</strong><strong class="price"><?= money($order['total'], $order['currency']) ?></strong></p>
  <p class="center"><a class="btn" href="index.php">Continue shopping</a></p>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
