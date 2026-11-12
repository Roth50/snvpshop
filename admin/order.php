<?php
require __DIR__ . '/../functions.php';
require_admin();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM shop_orders WHERE id = ?'); $st->execute([$id]);
$o = $st->fetch();
if (!$o) redirect('orders.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    $action = (string) ($_POST['action'] ?? '');
    if ($o['status'] !== 'pending') {
        flash('error', 'Only orders awaiting approval can be changed.');
    } elseif ($action === 'approve') {
        $qr = khqr_generate((int) $o['id'], (float) $o['total'], (string) $o['currency']);
        $update = db()->prepare("UPDATE shop_orders SET status = 'approved', khqr_string = ?, khqr_md5 = ?, expires_at = ? WHERE id = ? AND status = 'pending'");
        $update->execute([$qr['qr'], $qr['md5'], $qr['expires_at'], $id]);
        flash($update->rowCount() === 1 ? 'success' : 'error', $update->rowCount() === 1 ? 'Order approved. The customer can now pay.' : 'This order is no longer awaiting approval.');
    } elseif ($action === 'reject') {
        $update = db()->prepare("UPDATE shop_orders SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $update->execute([$id]);
        flash($update->rowCount() === 1 ? 'success' : 'error', $update->rowCount() === 1 ? 'Order rejected.' : 'This order is no longer awaiting approval.');
    } else {
        http_response_code(400);
        exit('Invalid order action.');
    }
    redirect('order.php?id=' . $id);
}

$st = db()->prepare('SELECT * FROM shop_order_items WHERE order_id = ?'); $st->execute([$o['id']]);
$items = $st->fetchAll();
$title = 'Order #' . $o['id']; $ROOT = '../';
include __DIR__ . '/../partials/header.php';
?>
<p><a href="orders.php">&larr; All orders</a></p>
<div class="panel narrow">
  <h1>Order #<?= (int) $o['id'] ?> <span class="tag <?= $o['status'] === 'paid' ? 'ok' : (in_array($o['status'], ['pending', 'approved'], true) ? 'warn' : 'bad') ?>"><?= e($o['status']) ?></span></h1>
  <?php if ($o['status'] === 'pending'): ?>
    <p>This order is waiting for your approval. Approving it creates the customer’s KHQR payment.</p>
    <div class="row">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn">Approve order</button></form>
      <form method="post" onsubmit="return confirm('Reject this order?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn danger">Reject order</button></form>
    </div>
  <?php elseif ($o['status'] === 'approved'): ?>
    <p>Approved; waiting for customer payment.</p>
  <?php endif; ?>
  <p>Placed: <?= e($o['created_at']) ?><?php if ($o['paid_at']): ?><br>Paid: <?= e($o['paid_at']) ?><?php endif; ?></p>
  <p><strong><?= e($o['customer_name']) ?></strong><br><?= e($o['email']) ?><br><?= e($o['phone']) ?></p>
  <p class="prewrap"><?= e($o['address']) ?></p>
  <ul class="summary"><?php foreach ($items as $i): ?><li><span><?= e($i['name']) ?> × <?= (int) $i['quantity'] ?> @ <?= money($i['unit_price'], $o['currency']) ?></span><span><?= money($i['unit_price'] * $i['quantity'], $o['currency']) ?></span></li><?php endforeach; ?></ul>
  <p class="row between"><strong>Total</strong><strong class="price"><?= money($o['total'], $o['currency']) ?></strong></p>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
