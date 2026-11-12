<?php
// PDO connection (MySQL or SQLite). Creates database/tables automatically on first run.

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $driver = cfg('db_driver', 'mysql');

    if ($driver === 'sqlite') {
        $file = cfg('sqlite_file');
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0775, true);
        $pdo = new PDO('sqlite:' . $file, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string) cfg('db_name'));
        $server = new PDO('mysql:host=' . cfg('db_host') . ';charset=utf8mb4', cfg('db_user'), cfg('db_pass'), $opts);
        $server->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO('mysql:host=' . cfg('db_host') . ";dbname=$name;charset=utf8mb4", cfg('db_user'), cfg('db_pass'), $opts);
    }
    shop_migrate($pdo, $driver);
    return $pdo;
}

function shop_migrate(PDO $pdo, string $driver): void {
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $engine = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_products (
        id $pk,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(64) NOT NULL DEFAULT 'General',
        description TEXT NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        image_url VARCHAR(500) NOT NULL DEFAULT '',
        status VARCHAR(10) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )$engine");

    if ($driver === 'sqlite') {
        $columns = $pdo->query("PRAGMA table_info(shop_products)")->fetchAll(PDO::FETCH_ASSOC);
        $hasCategory = false;
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'category') { $hasCategory = true; break; }
        }
        if (!$hasCategory) {
            $pdo->exec("ALTER TABLE shop_products ADD COLUMN category VARCHAR(64) NOT NULL DEFAULT 'General'");
        }
    } else {
        $column = $pdo->query("SHOW COLUMNS FROM shop_products LIKE 'category'")->fetch();
        if (!$column) {
            $pdo->exec("ALTER TABLE shop_products ADD COLUMN category VARCHAR(64) NOT NULL DEFAULT 'General'");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_users (
        id $pk,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_orders (
        id $pk,
        customer_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        total DECIMAL(12,2) NOT NULL,
        currency VARCHAR(3) NOT NULL DEFAULT 'USD',
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        khqr_string TEXT NULL,
        khqr_md5 VARCHAR(32) NULL,
        expires_at BIGINT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        paid_at TIMESTAMP NULL DEFAULT NULL
    )$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_order_items (
        id $pk,
        order_id INT NOT NULL,
        product_id INT NULL,
        name VARCHAR(255) NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        quantity INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES shop_orders(id) ON DELETE CASCADE
    )$engine");

    $products = [
        ['Classic T-Shirt', 'Apparel', 'Soft 100% cotton tee. Comfortable for everyday wear.', 8.50, 40, 'assets/products/tshirt.svg', 'https://picsum.photos/seed/tshirt/600/450'],
        ['Canvas Backpack', 'Travel', 'Durable canvas backpack with laptop compartment.', 24.00, 15, 'assets/products/backpack.svg', 'https://picsum.photos/seed/backpack/600/450'],
        ['Ceramic Mug', 'Home', 'Handmade 350ml ceramic mug. Dishwasher safe.', 5.25, 60, 'assets/products/mug.svg', 'https://picsum.photos/seed/mug/600/450'],
        ['Wireless Earbuds', 'Electronics', 'Bluetooth 5.3 earbuds with charging case.', 19.90, 0, 'assets/products/earbuds.svg', 'https://picsum.photos/seed/earbuds/600/450'],
        ['Desk Lamp', 'Home', 'Adjustable LED desk lamp with a warm, comfortable glow.', 18.50, 24, 'assets/products/lamp.svg', null],
        ['Water Bottle', 'Outdoors', 'Insulated stainless steel bottle for drinks on the go.', 12.00, 35, 'assets/products/bottle.svg', null],
        ['Leather Wallet', 'Accessories', 'Slim everyday wallet with room for cards and cash.', 16.75, 18, 'assets/products/wallet.svg', null],
        ['Running Sneakers', 'Apparel', 'Lightweight everyday trainers with cushioned soles.', 42.00, 12, 'assets/products/sneakers.svg', null],
        ['Classic Wristwatch', 'Accessories', 'Minimal analog watch with a comfortable strap.', 35.00, 9, 'assets/products/watch.svg', null],
        ['Portable Speaker', 'Electronics', 'Compact Bluetooth speaker for music at home or outdoors.', 29.50, 20, 'assets/products/speaker.svg', null],
        ['Everyday Sunglasses', 'Accessories', 'Classic UV-protective sunglasses with a lightweight frame.', 14.00, 28, 'assets/products/sunglasses.svg', null],
        ['Hardcover Notebook', 'Office', 'A5 lined notebook with a durable hardcover.', 6.50, 50, 'assets/products/notebook.svg', null],
        ['Aluminum Phone Stand', 'Electronics', 'Adjustable aluminum stand for hands-free phone viewing at your desk.', 11.50, 30, 'assets/products/phone-stand.svg', null],
        ['Bluetooth Keyboard', 'Electronics', 'Slim wireless keyboard with quiet keys for work and everyday use.', 27.00, 16, 'assets/products/keyboard.svg', null],
        ['Travel Duffle Bag', 'Travel', 'Roomy carry-on duffle bag with a padded shoulder strap.', 32.00, 14, 'assets/products/duffle-bag.svg', null],
        ['Cotton Tote Bag', 'Accessories', 'Reusable cotton tote with reinforced handles for everyday errands.', 9.00, 45, 'assets/products/tote-bag.svg', null],
        ['Exercise Yoga Mat', 'Outdoors', 'Cushioned non-slip exercise mat for stretching and home workouts.', 21.00, 22, 'assets/products/yoga-mat.svg', null],
        ['Glass French Press', 'Home', 'Classic 800ml glass coffee press with a stainless steel filter.', 17.50, 19, 'assets/products/french-press.svg', null],
        ['Wood Desk Organizer', 'Office', 'Compact wooden organizer with compartments for stationery and accessories.', 15.00, 25, 'assets/products/desk-organizer.svg', null],
        ['Everyday Baseball Cap', 'Apparel', 'Lightweight cotton cap with an adjustable back strap.', 13.00, 32, 'assets/products/baseball-cap.svg', null],
        ['Ceramic Plant Pot', 'Home', 'Simple glazed ceramic planter for small indoor plants.', 10.50, 27, 'assets/products/plant-pot.svg', null],
        ['Portable Power Bank', 'Electronics', 'Compact 10000mAh power bank for charging devices on the go.', 25.00, 18, 'assets/products/power-bank.svg', null],
    ];
    $names = array_column($products, 0);
    $existing = $pdo->prepare('SELECT name FROM shop_products WHERE name IN (' . implode(',', array_fill(0, count($names), '?')) . ')');
    $existing->execute($names);
    $existingNames = array_fill_keys($existing->fetchAll(PDO::FETCH_COLUMN), true);
    $insert = $pdo->prepare('INSERT INTO shop_products (name, category, description, price, stock, image_url, status) VALUES (?,?,?,?,?,?,?)');
    $updateImage = $pdo->prepare('UPDATE shop_products SET category = ?, image_url = ? WHERE name = ? AND image_url = ?');
    foreach ($products as [$name, $category, $description, $price, $stock, $image, $oldImage]) {
        if ($oldImage !== null) $updateImage->execute([$category, $image, $name, $oldImage]);
        if (!isset($existingNames[$name])) $insert->execute([$name, $category, $description, $price, $stock, $image, 'active']);
    }
}
