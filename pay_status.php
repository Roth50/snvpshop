<?php
require __DIR__ . '/functions.php';
header('Content-Type: application/json');
session_write_close();                      // don't block other requests while polling Bakong
$order = my_order((int) ($_GET['id'] ?? 0));
if (!$order) { http_response_code(404); echo json_encode(['status' => 'not_found']); exit; }

if ($order['status'] === 'approved') {
    if (khqr_check_paid($order['khqr_md5'])) {
        mark_paid((int) $order['id']);
        echo json_encode(['status' => 'paid']); exit;
    }
    if ((int) round(microtime(true) * 1000) > (int) $order['expires_at']) {
        db()->prepare("UPDATE shop_orders SET status = 'expired' WHERE id = ? AND status = 'approved'")->execute([$order['id']]);
        echo json_encode(['status' => 'expired']); exit;
    }
}
echo json_encode(['status' => $order['status']]);
