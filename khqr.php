<?php
// KHQR (Bakong) individual-merchant QR generator + payment check. No dependencies.

function khqr_tlv(string $tag, string $value): string {
    return $tag . str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function khqr_crc16(string $s): string {
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        $crc ^= ord($s[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? ((($crc << 1) ^ 0x1021) & 0xFFFF) : (($crc << 1) & 0xFFFF);
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

/**
 * @return array{qr:string, md5:string, expires_at:int}
 */
function khqr_generate(int $orderId, float $amount, string $currency, ?int $nowMs = null): array {
    $nowMs ??= (int) round(microtime(true) * 1000);
    $expiresAt = $nowMs + (int) cfg('qr_expire_minutes', 10) * 60 * 1000;
    $isKhr = strtoupper($currency) === 'KHR';

    $account = khqr_tlv('00', (string) cfg('bakong_account_id'));
    $name = mb_substr((string) cfg('merchant_name', 'Shop'), 0, 25);
    $city = mb_substr((string) cfg('merchant_city', 'Phnom Penh'), 0, 15);
    $amountStr = $isKhr ? (string) round($amount) : number_format($amount, 2, '.', '');

    $payload =
        khqr_tlv('00', '01') .
        khqr_tlv('01', '12') .                                  // 12 = dynamic QR (amount + expiry)
        khqr_tlv('29', $account) .
        khqr_tlv('52', '5999') .
        khqr_tlv('53', $isKhr ? '116' : '840') .
        khqr_tlv('54', $amountStr) .
        khqr_tlv('58', 'KH') .
        khqr_tlv('59', $name) .
        khqr_tlv('60', $city) .
        khqr_tlv('62', khqr_tlv('01', 'ORD-' . $orderId)) .
        khqr_tlv('99', khqr_tlv('00', (string) $nowMs) . khqr_tlv('01', (string) $expiresAt)) .
        '6304';
    $qr = $payload . khqr_crc16($payload);
    return ['qr' => $qr, 'md5' => md5($qr), 'expires_at' => $expiresAt];
}

/** Ask the Bakong Open API whether a transaction with this md5 has been paid. */
function khqr_check_paid(string $md5): bool {
    $token = (string) cfg('bakong_token', '');
    if ($token === '') return false;
    $ch = curl_init(rtrim((string) cfg('bakong_api_url'), '/') . '/v1/check_transaction_by_md5');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_POSTFIELDS => json_encode(['md5' => $md5]),
    ]);
    $body = curl_exec($ch);
    if ($body === false) { error_log('Bakong check failed: ' . curl_error($ch)); curl_close($ch); return false; }
    curl_close($ch);
    $j = json_decode($body, true);
    return is_array($j) && ($j['responseCode'] ?? 1) === 0 && !empty($j['data']);
}
