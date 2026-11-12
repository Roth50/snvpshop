<?php
$env = static function (string $primary, ?string $legacy = null, ?string $default = null): string {
    $value = getenv($primary);
    if ($value !== false && $value !== '') return $value;
    if ($legacy !== null) {
        $value = getenv($legacy);
        if ($value !== false && $value !== '') return $value;
    }
    return $default ?? '';
};

$config = [
    'shop_name' => $env('SHOP_NAME', null, 'SNVP'),
    'currency' => $env('SHOP_CURRENCY', 'CURRENCY', 'USD'),

    'db_driver' => $env('SHOP_DB_DRIVER', 'DB_DRIVER', 'mysql'),
    'db_host' => $env('SHOP_DB_HOST', 'DB_HOST', 'localhost'),
    'db_port' => (int) $env('SHOP_DB_PORT', 'DB_PORT', '3306'),
    'db_name' => $env('SHOP_DB_NAME', 'DB_NAME', 'room_booking'),
    'db_user' => $env('SHOP_DB_USER', 'DB_USER', 'root'),
    'db_pass' => $env('SHOP_DB_PASS', 'DB_PASS'),
    'sqlite_file' => getenv('SHOP_SQLITE_FILE') ?: __DIR__ . '/data/shop.db',

    'admin_user' => $env('SHOP_ADMIN_USER', 'ADMIN_USER'),
    'admin_email' => $env('SHOP_ADMIN_EMAIL', 'ADMIN_EMAIL'),
    'admin_pass_hash' => $env('SHOP_ADMIN_PASS_HASH', 'ADMIN_PASS_HASH'),

    'bakong_account_id' => getenv('BAKONG_ACCOUNT_ID') ?: '',
    'merchant_name' => $env('BAKONG_MERCHANT_NAME', 'MERCHANT_NAME', 'SNVP'),
    'merchant_city' => $env('BAKONG_MERCHANT_CITY', 'MERCHANT_CITY', 'Phnom Penh'),
    'bakong_token' => getenv('BAKONG_TOKEN') ?: '',
    'bakong_api_url' => getenv('BAKONG_API_URL') ?: 'https://api-bakong.nbc.gov.kh',
    'qr_expire_minutes' => (int) (getenv('QR_EXPIRE_MINUTES') ?: 3),
];

$localConfigFile = __DIR__ . '/config.local.php';
if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;
    if (!is_array($localConfig)) {
        throw new RuntimeException('config.local.php must return a configuration array.');
    }
    $config = array_replace($config, $localConfig);
}

return $config;
