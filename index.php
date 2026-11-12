<?php
require __DIR__ . '/functions.php';
$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$categories = product_categories();
$where = ["status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR description LIKE ? OR category LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
}
$sql = 'SELECT * FROM shop_products WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();
$title = 'Home'; $ROOT = '';
include __DIR__ . '/partials/header.php';
?>
<section class="hero">
  <h1>Welcome to <?= e(cfg('shop_name')) ?></h1>
  <p>Pick your favourites, add them to your cart and pay instantly by scanning KHQR with Bakong or any banking app.</p>
  <form class="search" method="get" action="index.php">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products…" aria-label="Search products">
    <select name="category" aria-label="Filter by category">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn">Search</button>
  </form>
</section>
<div class="section-head">
  <h2><?= $q !== '' || $category !== '' ? 'Results for “' . e($q !== '' ? $q : $category) . '”' : 'All products' ?></h2>
  <span class="muted"><?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?></span>
</div>
<?php if (!$products): ?><div class="empty"><p>😕 No products found.</p><a class="btn secondary" href="index.php">Show all products</a></div><?php endif; ?>
<div class="grid">
<?php foreach ($products as $p): $desc = $p['description']; ?>
  <article class="card">
    <a class="thumb" href="product.php?id=<?= (int) $p['id'] ?>"><img src="<?= e($p['image_url'] ?: 'assets/placeholder.svg') ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='assets/placeholder.svg'"></a>
    <div class="card-body">
      <div class="row"><span class="tag warn"><?= e($p['category'] ?? 'General') ?></span></div>
      <h3><a href="product.php?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a></h3>
      <p class="desc"><?= e(mb_strlen($desc) > 90 ? mb_substr($desc, 0, 90) . '…' : $desc) ?></p>
      <div class="row">
        <strong class="price"><?= money($p['price']) ?></strong>
        <?php if ($p['stock'] > 0): ?><span class="tag ok">In stock</span><?php else: ?><span class="tag bad">Out of stock</span><?php endif; ?>
      </div>
      <div class="row actions">
        <a class="btn secondary" href="product.php?id=<?= (int) $p['id'] ?>">View Details</a>
        <form method="post" action="cart.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="back" value="index.php">
          <button class="btn" <?= $p['stock'] > 0 ? '' : 'disabled' ?>>Add to Cart</button>
        </form>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
