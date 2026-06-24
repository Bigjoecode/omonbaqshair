<?php
/**
 * Stripe webhook — marks orders paid on checkout.session.completed.
 * Configure endpoint in Stripe dashboard and set STRIPE_WEBHOOK_SECRET.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$payload = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify signature (if secret configured)
if (STRIPE_WEBHOOK_SECRET && !str_contains(STRIPE_WEBHOOK_SECRET, 'REPLACE_ME')) {
    $parts = [];
    foreach (explode(',', $sig) as $kv) { [$k, $v] = array_pad(explode('=', $kv, 2), 2, ''); $parts[$k] = $v; }
    $signedPayload = ($parts['t'] ?? '') . '.' . $payload;
    $expected = hash_hmac('sha256', $signedPayload, STRIPE_WEBHOOK_SECRET);
    if (!hash_equals($expected, $parts['v1'] ?? '')) {
        http_response_code(400); echo 'Invalid signature'; exit;
    }
}

$event = json_decode($payload, true) ?: [];
if (($event['type'] ?? '') === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? [];
    $orderNo = $session['client_reference_id'] ?? ($session['metadata']['order_number'] ?? '');
    if ($orderNo) {
        $upd = q('UPDATE orders SET payment_status = ?, status = ?, stripe_intent = ? WHERE order_number = ? AND payment_status <> ?',
            ['paid', 'processing', $session['payment_intent'] ?? '', $orderNo, 'paid']);
        // Only act on the FIRST transition to paid (prevents double email/points if order-success ran first)
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
