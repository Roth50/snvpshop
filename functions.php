<?php
declare(strict_types=1);

function cfg(string $key, $default = null) {
    static $c = null;
    $c ??= require __DIR__ . '/config.php';
    return $c[$key] ?? $default;
}

// Own session name so it never clashes with the room-booking login session.
if (session_status() === PHP_SESSION_NONE) {
    session_name('shop_session');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/khqr.php';

function e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function money($n, ?string $currency = null): string {
    $currency = $currency ?? (string) cfg('currency', 'USD');
    return strtoupper($currency) === 'KHR'
        ? number_format((float) $n, 0) . ' ៛'
        : '$' . number_format((float) $n, 2);
}

function redirect(string $to): never { header('Location: ' . $to); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

// ---- CSRF ----
function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419); exit('Invalid or expired form. Go back, refresh and try again.');
    }
}
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
    csrf_check();
}

// ---- Cart (stored in session as [product_id => qty]) ----
function cart_count(): int { return array_sum($_SESSION['cart'] ?? []); }

function cart_load(): array {
    $cart = $_SESSION['cart'] ?? [];
    $items = []; $total = 0.0;
    $st = db()->prepare("SELECT * FROM shop_products WHERE id = ? AND status = 'active'");
    foreach ($cart as $id => $qty) {
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p || (int) $p['stock'] <= 0) { unset($cart[$id]); continue; }
        $qty = min((int) $qty, (int) $p['stock']);
        $cart[$id] = $qty;
        $sub = $qty * (float) $p['price'];
        $total += $sub;
        $items[] = ['product' => $p, 'quantity' => $qty, 'subtotal' => $sub];
    }
    $_SESSION['cart'] = $cart;
    return ['items' => $items, 'total' => round($total, 2)];
}

// ---- Orders ----
function my_order(int $id): ?array {
    if (!in_array($id, $_SESSION['my_orders'] ?? [], true)) return null;
    $st = db()->prepare('SELECT * FROM shop_orders WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Marks an approved order as paid and reduces stock. Safe to call twice. */
function mark_paid(int $orderId): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("UPDATE shop_orders SET status = 'paid', paid_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'approved'");
        $st->execute([$orderId]);
        if ($st->rowCount() === 1) {
            $items = $pdo->prepare('SELECT product_id, quantity FROM shop_order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            $dec = $pdo->prepare('UPDATE shop_products SET stock = CASE WHEN stock >= ? THEN stock - ? ELSE 0 END WHERE id = ?');
            foreach ($items->fetchAll() as $i) $dec->execute([$i['quantity'], $i['quantity'], $i['product_id']]);
        }
        $pdo->commit();
    } catch (Throwable $t) { $pdo->rollBack(); throw $t; }
}

// ---- Admin ----
function require_admin(): void {
    if (empty($_SESSION['shop_admin'])) redirect('login.php');
}

// ---- Customer auth ----
function current_user(): ?array {
    $user = $_SESSION['shop_user'] ?? null;
    if (!is_array($user) || empty($user['id'])) return null;
    return $user;
}

function require_user(): void {
    if (!current_user()) redirect('login.php');
}

function user_register_input(array $in): array {
    $p = [
        'name' => trim((string) ($in['name'] ?? '')),
        'email' => strtolower(trim((string) ($in['email'] ?? ''))),
        'password' => (string) ($in['password'] ?? ''),
        'password_confirm' => (string) ($in['password_confirm'] ?? ''),
    ];
    $err = [];
    if ($p['name'] === '') $err['name'] = 'Full name is required.';
    if (!filter_var($p['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Enter a valid email address.';
    if (strlen($p['password']) < 6) $err['password'] = 'Password must be at least 6 characters long.';
    if ($p['password'] !== $p['password_confirm']) $err['password_confirm'] = 'Passwords do not match.';
    return [$p, $err];
}

function user_login_input(array $in): array {
    return [
        'email' => strtolower(trim((string) ($in['email'] ?? ''))),
        'password' => (string) ($in['password'] ?? ''),
    ];
}

// Absolute URL of a page in the shop folder (for share links).
function shop_url(string $page): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (substr($dir, -6) === '/admin') $dir = substr($dir, 0, -6);
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/' . $page;
}

function product_input(array $in): array {
    $p = [
        'name' => trim((string) ($in['name'] ?? '')),
        'category' => trim((string) ($in['category'] ?? '')),
        'description' => trim((string) ($in['description'] ?? '')),
        'price' => is_numeric($in['price'] ?? null) ? (float) $in['price'] : -1,
        'stock' => ctype_digit(trim((string) ($in['stock'] ?? ''))) ? (int) $in['stock'] : -1,
        'image_url' => trim((string) ($in['image_url'] ?? '')),
        'status' => ($in['status'] ?? '') === 'inactive' ? 'inactive' : 'active',
    ];
    if ($p['category'] === '') $p['category'] = 'General';
    $err = [];
    if ($p['name'] === '') $err['name'] = 'Name is required.';
    if ($p['category'] === '') $err['category'] = 'Category is required.';
    if ($p['price'] < 0) $err['price'] = 'Enter a valid price.';
    if ($p['stock'] < 0) $err['stock'] = 'Enter a valid stock quantity.';
    if ($p['image_url'] !== '' && !preg_match('#^(https?://|/|[\w./-]+$)#i', $p['image_url'])) $err['image_url'] = 'Enter a valid image URL.';
    return [$p, $err];
}

function save_product_image(array $file): array {
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return ['', null];
    if ($error !== UPLOAD_ERR_OK) {
        return ['', 'Image upload failed. Please try again with a file smaller than 5 MB.'];
    }
    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['', 'Image must be 5 MB or smaller.'];
    }
    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return ['', 'The uploaded image could not be verified. Please try again.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($extensions[$mime])) {
        return ['', 'Choose a JPG, PNG, WEBP, or GIF image.'];
    }

    $directory = __DIR__ . '/assets/uploads/products';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['', 'The image upload folder could not be created.'];
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmpName, $directory . DIRECTORY_SEPARATOR . $filename)) {
        return ['', 'The image could not be saved. Check that the uploads folder is writable.'];
    }
    return ['assets/uploads/products/' . $filename, null];
}

function product_categories(): array {
    $st = db()->query("SELECT DISTINCT category FROM shop_products WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
    return array_map('strval', array_column($st->fetchAll(), 'category'));
}
