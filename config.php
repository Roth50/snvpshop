<?php
$config = [
    'shop_name' => getenv('SHOP_NAME') ?: 'SNVP',
    'currency' => getenv('SHOP_CURRENCY') ?: 'USD',

    'db_driver' => getenv('SHOP_DB_DRIVER') ?: 'mysql',
    'db_host' => getenv('SHOP_DB_HOST') ?: 'localhost',
    'db_port' => (int) (getenv('SHOP_DB_PORT') ?: 3306),
    'db_name' => getenv('SHOP_DB_NAME') ?: 'room_booking',
    'db_user' => getenv('SHOP_DB_USER') ?: 'root',
    'db_pass' => getenv('SHOP_DB_PASS') ?: '',
    'sqlite_file' => getenv('SHOP_SQLITE_FILE') ?: __DIR__ . '/data/shop.db',

    'admin_user' => getenv('SHOP_ADMIN_USER') ?: '',
    'admin_email' => getenv('SHOP_ADMIN_EMAIL') ?: '',
    'admin_pass_hash' => getenv('SHOP_ADMIN_PASS_HASH') ?: '',

    'bakong_account_id' => getenv('BAKONG_ACCOUNT_ID') ?: '',
    'merchant_name' => getenv('BAKONG_MERCHANT_NAME') ?: 'SNVP',
    'merchant_city' => getenv('BAKONG_MERCHANT_CITY') ?: 'Phnom Penh',
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
