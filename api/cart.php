<?php
/**
 * Cart API — JSON. Actions: get, add, inc, dec, remove, clear, set
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = $raw ? (json_decode($raw, true) ?: []) : $_POST;
}
$action = $input['action'] ?? ($_GET['action'] ?? 'get');

switch ($action) {
    case 'add':
        $pid = (int)($input['product_id'] ?? 0);
        $qty = max(1, (int)($input['qty'] ?? 1));
        $variant = $input['variant'] ?? null;
        if ($pid) cart_add($pid, $qty, $variant ?: null);
        break;
    case 'inc':
    case 'dec':
        $key = $input['key'] ?? '';
        $items = cart();
        if (isset($items[$key])) {
            $newQty = $items[$key]['qty'] + ($action === 'inc' ? 1 : -1);
            cart_set($key, $newQty);
        }
        break;
    case 'set':
        cart_set($input['key'] ?? '', (int)($input['qty'] ?? 1));
        break;
    case 'remove':
        cart_remove($input['key'] ?? '');
        break;
    case 'clear':
        cart_clear();
        break;
    case 'set_coupon':
        $code = strtoupper(trim($input['code'] ?? ''));
        $c = $code ? fetch('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [$code]) : null;
        if ($c) $_SESSION['coupon'] = $code;
        break;
}

$detail = cart_detailed();
$lines = array_map(function ($l) {
    return [
        'key'           => $l['key'],
        'name'          => $l['product']['name'],
        'variant'       => $l['variant'],
        'qty'           => $l['qty'],
        'image'         => $l['image'],
        'price_display' => round(convert_price($l['price']), 2),
        'line_display'  => round(convert_price($l['line_total']), 2),
    ];
}, $detail['lines']);

echo json_encode([
    'count'             => $detail['count'],
    'subtotal_display'  => round(convert_price($detail['subtotal']), 2),
    'currency'          => current_currency(),
    'lines'             => $lines,
]);
