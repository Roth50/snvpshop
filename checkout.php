<?php
require __DIR__ . '/functions.php';
$cart = cart_load();
if (!$cart['items']) { flash('error', 'Your cart is empty.'); redirect('cart.php'); }

$form = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $k => $_) $form[$k] = trim((string) ($_POST[$k] ?? ''));
    if (mb_strlen($form['full_name']) < 2) $errors['full_name'] = 'Please enter your full name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email.';
    if (!preg_match('/^[+\d][\d\s-]{6,19}$/', $form['phone'])) $errors['phone'] = 'Please enter a valid phone number.';
    if (mb_strlen($form['address']) < 5) $errors['address'] = 'Please enter your shipping address.';

    if (!$errors) {
        $pdo = db();
        $currency = strtoupper((string) cfg('currency', 'USD'));
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO shop_orders (customer_name, email, phone, address, total, currency) VALUES (?,?,?,?,?,?)')
                ->execute([$form['full_name'], $form['email'], $form['phone'], $form['address'], $cart['total'], $currency]);
            $orderId = (int) $pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO shop_order_items (order_id, product_id, name, unit_price, quantity) VALUES (?,?,?,?,?)');
            foreach ($cart['items'] as $i) $ins->execute([$orderId, $i['product']['id'], $i['product']['name'], $i['product']['price'], $i['quantity']]);

            $pdo->commit();
        } catch (Throwable $t) { $pdo->rollBack(); throw $t; }

        $_SESSION['my_orders'][] = $orderId;
        redirect('pay.php?id=' . $orderId);
    }
}

$title = 'Checkout'; $ROOT = '';
include __DIR__ . '/partials/header.php';
?>
<h1>Checkout</h1>
<div class="two-col">
  <form method="post" class="panel" novalidate>
    <?= csrf_field() ?>
    <h2>Your details</h2>
    <?php foreach (['full_name' => ['Full Name', 'text'], 'email' => ['Email Address', 'email'], 'phone' => ['Phone Number', 'tel']] as $n => [$label, $type]): ?>
      <label><?= $label ?>
        <input type="<?= $type ?>" name="<?= $n ?>" value="<?= e($form[$n]) ?>" required>
        <?php if (isset($errors[$n])): ?><small class="err"><?= e($errors[$n]) ?></small><?php endif; ?>
      </label>
    <?php endforeach; ?>
    <label>Shipping Address
      <textarea name="address" rows="3" required><?= e($form['address']) ?></textarea>
      <?php if (isset($errors['address'])): ?><small class="err"><?= e($errors['address']) ?></small><?php endif; ?>
    </label>
    <button class="btn">Place order for approval</button>
  </form>
  <aside class="panel">
    <h2>Order summary</h2>
    <ul class="summary">
      <?php foreach ($cart['items'] as $i): ?><li><span><?= e($i['product']['name']) ?> × <?= $i['quantity'] ?></span><span><?= money($i['subtotal']) ?></span></li><?php endforeach; ?>
    </ul>
    <p class="row between"><strong>Total</strong><strong class="price"><?= money($cart['total']) ?></strong></p>
  </aside>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
