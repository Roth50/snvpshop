<?php
require __DIR__ . '/../functions.php';
require_admin();
$orders = db()->query('SELECT o.*, (SELECT COALESCE(SUM(quantity),0) FROM shop_order_items WHERE order_id = o.id) AS items FROM shop_orders o ORDER BY o.id DESC')->fetchAll();
$title = 'Admin · Orders'; $ROOT = '../';
include __DIR__ . '/../partials/header.php';
?>
<h1>Order History</h1>
<div class="table-wrap"><table>
  <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= (int) $o['id'] ?></td><td><?= e($o['created_at']) ?></td>
      <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['email']) ?></small></td>
      <td><?= (int) $o['items'] ?></td><td><?= money($o['total'], $o['currency']) ?></td>
      <td><span class="tag <?= in_array($o['status'], ['paid'], true) ? 'ok' : (in_array($o['status'], ['pending', 'approved'], true) ? 'warn' : 'bad') ?>"><?= e($o['status'] === 'pending' ? 'Awaiting approval' : ($o['status'] === 'approved' ? 'Awaiting payment' : $o['status'])) ?></span></td>
      <td><a class="btn small secondary" href="order.php?id=<?= (int) $o['id'] ?>">View</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="7" class="muted">No orders yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
