<?php
require __DIR__ . '/../functions.php';
require_admin();
$products = db()->query('SELECT * FROM shop_products ORDER BY id DESC')->fetchAll();
$title = 'Admin · Products'; $ROOT = '../';
include __DIR__ . '/../partials/header.php';
?>
<div class="row between"><h1>Products</h1><a class="btn" href="product_form.php">+ Add New Product</a></div>
<div class="table-wrap"><table>
  <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= (int) $p['id'] ?></td><td><?= e($p['name']) ?></td><td><span class="tag warn"><?= e($p['category'] ?? 'General') ?></span></td><td><?= money($p['price']) ?></td><td><?= (int) $p['stock'] ?></td>
      <td><span class="tag <?= $p['status'] === 'active' ? 'ok' : 'bad' ?>"><?= $p['status'] === 'active' ? 'Active' : 'Inactive' ?></span></td>
      <td class="row">
        <a class="btn small secondary" href="product_form.php?id=<?= (int) $p['id'] ?>">Edit</a>
        <form method="post" action="product_delete.php" onsubmit="return confirm('Delete this product?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button class="btn small danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$products): ?><tr><td colspan="7" class="muted">No products yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
