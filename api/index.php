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
try {
    require dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
} catch (Throwable $error) {
    error_log('Shop request failed (' . get_class($error) . '): ' . $error->getMessage());
    http_response_code(503);
    if ($path === 'pay_status.php') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'unavailable']);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Shop temporarily unavailable</title><body><main><h1>Shop temporarily unavailable</h1><p>The shop database is not configured or is temporarily unreachable. Please try again later.</p></main></body></html>';
}
