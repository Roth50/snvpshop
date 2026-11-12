<?php
require __DIR__ . '/../functions.php';
require_admin();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isNew = $id === 0;
$product = ['name' => '', 'category' => 'General', 'description' => '', 'price' => '', 'stock' => 0, 'image_url' => '', 'status' => 'active'];
if (!$isNew) {
    $st = db()->prepare('SELECT * FROM shop_products WHERE id = ?'); $st->execute([$id]);
    $product = $st->fetch();
    if (!$product) { flash('error', 'Product not found.'); redirect('index.php'); }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$p, $errors] = product_input($_POST);
    if (!$errors) {
        [$uploadedImage, $uploadError] = save_product_image($_FILES['product_image'] ?? []);
        if ($uploadError !== null) {
            $errors['product_image'] = $uploadError;
        } else {
            if ($uploadedImage !== '') $p['image_url'] = $uploadedImage;
            if ($isNew) {
                db()->prepare('INSERT INTO shop_products (name, category, description, price, stock, image_url, status) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$p['name'], $p['category'], $p['description'], $p['price'], $p['stock'], $p['image_url'], $p['status']]);
                flash('success', 'Product created.');
            } else {
                db()->prepare('UPDATE shop_products SET name=?, category=?, description=?, price=?, stock=?, image_url=?, status=? WHERE id=?')
                    ->execute([$p['name'], $p['category'], $p['description'], $p['price'], $p['stock'], $p['image_url'], $p['status'], $id]);
                flash('success', 'Product updated.');
            }
            redirect('index.php');
        }
    }
    $product = $p;
}
$title = $isNew ? 'New product' : 'Edit product'; $ROOT = '../';
include __DIR__ . '/../partials/header.php';
?>
<h1><?= $isNew ? 'Add New Product' : 'Edit Product #' . $id ?></h1>
<form method="post" enctype="multipart/form-data" class="panel narrow">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <label>Name <input name="name" value="<?= e($product['name']) ?>" required>
    <?php if (isset($errors['name'])): ?><small class="err"><?= e($errors['name']) ?></small><?php endif; ?></label>
  <label>Category <input name="category" value="<?= e($product['category'] ?? 'General') ?>" placeholder="General" required>
    <?php if (isset($errors['category'])): ?><small class="err"><?= e($errors['category']) ?></small><?php endif; ?></label>
  <label>Description <textarea name="description" rows="4"><?= e($product['description']) ?></textarea></label>
  <div class="two-col tight">
    <label>Price <input type="number" step="0.01" min="0" name="price" value="<?= e($product['price'] < 0 ? '' : $product['price']) ?>" required>
      <?php if (isset($errors['price'])): ?><small class="err"><?= e($errors['price']) ?></small><?php endif; ?></label>
    <label>Stock Quantity <input type="number" min="0" name="stock" value="<?= e($product['stock'] < 0 ? '' : $product['stock']) ?>" required>
      <?php if (isset($errors['stock'])): ?><small class="err"><?= e($errors['stock']) ?></small><?php endif; ?></label>
  </div>
  <label>Image URL <input name="image_url" value="<?= e($product['image_url']) ?>" placeholder="https://...">
    <?php if (isset($errors['image_url'])): ?><small class="err"><?= e($errors['image_url']) ?></small><?php endif; ?></label>
  <label>Or upload product image <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif">
    <small class="muted">JPG, PNG, WEBP, or GIF; maximum 5 MB. Uploading replaces the image URL.</small>
    <?php if (isset($errors['product_image'])): ?><small class="err"><?= e($errors['product_image']) ?></small><?php endif; ?></label>
  <?php if (!empty($product['image_url'])): ?><p class="muted">Current image:</p><img class="product-image-preview" src="<?= e($product['image_url']) ?>" alt="Current product image" onerror="this.style.display='none'"><?php endif; ?>
  <label>Status
    <select name="status">
      <option value="active" <?= $product['status'] !== 'inactive' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select></label>
  <div class="row"><button class="btn">Save</button><a class="btn secondary" href="index.php">Cancel</a></div>
</form>
<?php include __DIR__ . '/../partials/footer.php'; ?>
