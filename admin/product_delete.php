<?php
require __DIR__ . '/../functions.php';
require_admin();
require_post();
db()->prepare('DELETE FROM shop_products WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
flash('success', 'Product deleted.');
redirect('index.php');
