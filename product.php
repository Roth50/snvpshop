<?php
require __DIR__ . '/functions.php';
$st = db()->prepare("SELECT * FROM shop_products WHERE id = ? AND status = 'active'");
$st->execute([(int) ($_GET['id'] ?? 0)]);
$p = $st->fetch();
$ROOT = '';
if (!$p) { http_response_code(404); $title = 'Not found'; include __DIR__ . '/partials/header.php'; echo '<h1>Product not found</h1><p><a class="btn" href="index.php">Back to shop</a></p>'; include __DIR__ . '/partials/footer.php'; exit; }
$url = shop_url('product.php?id=' . (int) $p['id']);
$fb = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url);
$tg = 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($p['name']);
$title = $p['name'];
include __DIR__ . '/partials/header.php';
?>
<p><a href="index.php">&larr; All products</a></p>
<div class="detail">
  <img src="<?= e($p['image_url'] ?: 'assets/placeholder.svg') ?>" alt="<?= e($p['name']) ?>" onerror="this.onerror=null;this.src='assets/placeholder.svg'">
  <div>
    <h1><?= e($p['name']) ?></h1>
    <p><span class="tag warn"><?= e($p['category'] ?? 'General') ?></span></p>
    <p class="price big"><?= money($p['price']) ?></p>
    <p><?php if ($p['stock'] > 0): ?><span class="tag ok">In stock (<?= (int) $p['stock'] ?> available)</span><?php else: ?><span class="tag bad">Out of stock</span><?php endif; ?></p>
    <p class="prewrap"><?= e($p['description']) ?></p>
    <?php if ($p['stock'] > 0): ?>
    <form method="post" action="cart.php" class="row">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
      <input type="hidden" name="back" value="product.php?id=<?= (int) $p['id'] ?>">
      <label>Quantity <input type="number" name="quantity" value="1" min="1" max="<?= (int) $p['stock'] ?>" class="qty"></label>
      <button class="btn">Add to Cart</button>
    </form>
    <?php endif; ?>
    <div class="share">
      <span>Share:</span>
      <a class="btn secondary" target="_blank" rel="noopener" href="<?= e($fb) ?>">Facebook</a>
      <a class="btn secondary" target="_blank" rel="noopener" href="<?= e($tg) ?>">Telegram</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
