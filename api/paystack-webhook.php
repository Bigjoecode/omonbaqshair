<?php
/**
 * Paystack webhook — marks orders paid on charge.success.
 * Set your webhook URL in the Paystack dashboard to …/api/paystack-webhook.php
 * Verified via the x-paystack-signature header (HMAC SHA512 of the body).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$payload = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!PAYSTACK_SECRET_KEY || str_contains(PAYSTACK_SECRET_KEY, 'REPLACE_ME')) {
    http_response_code(503); echo 'not configured'; exit;
}
$expected = hash_hmac('sha512', $payload, PAYSTACK_SECRET_KEY);
if (!hash_equals($expected, $sig)) {
    http_response_code(401); echo 'invalid signature'; exit;
}

$event = json_decode($payload, true) ?: [];
if (($event['event'] ?? '') === 'charge.success') {
    $data    = $event['data'] ?? [];
    $orderNo = $data['reference'] ?? ($data['metadata']['order_number'] ?? '');
    if ($orderNo) {
        $upd = q('UPDATE orders SET payment_status = ?, status = ?, stripe_intent = ? WHERE order_number = ? AND payment_status <> ?',
            ['paid', 'processing', (string)($data['reference'] ?? ''), $orderNo, 'paid']);
        // Act only on the FIRST transition to paid (prevents double email/points)
        if ($upd->rowCount() > 0) {
            $ord = fetch('SELECT * FROM orders WHERE order_number = ?', [$orderNo]);
            if ($ord) {
                decrement_order_stock((int)$ord['id']);
                royalty_award_for_order((int)$ord['id']);
                $items = fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$ord['id']]);
                email_order_confirmation($ord, $items);
                email_admin_new_order($ord, $items);
            }
        }
    }
}
http_response_code(200);
echo 'ok';
