<?php
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['product_id'] ?? 0);
    $st = db()->prepare("SELECT * FROM shop_products WHERE id = ? AND status = 'active'");
    $st->execute([$id]);
    $p = $st->fetch();
    $cart = $_SESSION['cart'] ?? [];

    if ($action === 'add') {
        $back = (string) ($_POST['back'] ?? '');
        if (!preg_match('/^[a-z_]+\.php(\?id=\d+)?$/', $back)) $back = 'index.php';
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        if (!$p) flash('error', 'Product not available.');
        elseif ($p['stock'] <= 0) flash('error', $p['name'] . ' is out of stock.');
        else {
            $new = ($cart[$id] ?? 0) + $qty;
            $cart[$id] = min($new, (int) $p['stock']);
            if ($new > $p['stock']) flash('error', 'Only ' . $p['stock'] . ' of ' . $p['name'] . ' in stock; cart adjusted.');
            else flash('success', 'Added ' . $p['name'] . ' to cart.');
        }
        $_SESSION['cart'] = $cart;
        redirect($back);
    }
    if ($action === 'update') {
        $qty = (int) ($_POST['quantity'] ?? 0);
        if (!$p || $qty < 1) unset($cart[$id]); else $cart[$id] = min($qty, (int) $p['stock']);
    }
    if ($action === 'remove') unset($cart[$id]);
    $_SESSION['cart'] = $cart;
    redirect('cart.php');
}

$cart = cart_load();
$title = 'Cart'; $ROOT = '';
include __DIR__ . '/partials/header.php';
?>
<h1>Your Cart</h1>
<?php if (!$cart['items']): ?>
  <div class="empty"><p style="font-size:3rem;margin:0">🛒</p><p>Your cart is empty.</p><a class="btn" href="index.php">Continue shopping</a></div>
<?php else: ?>
<div class="table-wrap"><table>
  <thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cart['items'] as $i): $p = $i['product']; ?>
    <tr>
      <td><a href="product.php?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a></td>
      <td><?= money($p['price']) ?></td>
      <td>
        <form method="post" class="row">
          <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
          <input class="qty" type="number" name="quantity" value="<?= $i['quantity'] ?>" min="1" max="<?= (int) $p['stock'] ?>">
          <button class="btn small secondary">Update</button>
        </form>
      </td>
      <td><?= money($i['subtotal']) ?></td>
      <td>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
          <button class="btn small danger">Remove</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><td colspan="3" class="right"><strong>Total</strong></td><td colspan="2"><strong class="price"><?= money($cart['total']) ?></strong></td></tr></tfoot>
</table></div>
<p class="row"><a class="btn secondary" href="index.php">Continue shopping</a> <a class="btn" href="checkout.php">Proceed to Checkout</a></p>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
