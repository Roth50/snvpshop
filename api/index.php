<?php
declare(strict_types=1);

$pages = [
    'index.php',
    'product.php',
    'cart.php',
    'checkout.php',
    'confirmation.php',
    'login.php',
    'logout.php',
    'register.php',
    'pay.php',
    'pay_status.php',
    'admin/index.php',
    'admin/login.php',
    'admin/logout.php',
    'admin/order.php',
    'admin/orders.php',
    'admin/product_delete.php',
    'admin/product_form.php',
];

$path = ltrim(rawurldecode((string) ($_GET['path'] ?? 'index.php')), '/');
if (!in_array($path, $pages, true)) {
    http_response_code(404);
    exit('Not found');
}

$script = '/' . $path;
$_SERVER['SCRIPT_NAME'] = $script;
$_SERVER['PHP_SELF'] = $script;
require dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
