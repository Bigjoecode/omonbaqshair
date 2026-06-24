<?php
/** Back-in-stock alert signup. */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$pid = (int)($body['product_id'] ?? 0);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$pid || !$email) {
    echo json_encode(['ok' => false, 'message' => 'Please provide a valid email.']);
    exit;
}
try {
    q('INSERT IGNORE INTO stock_alerts (product_id, email) VALUES (?, ?)', [$pid, $email]);
    echo json_encode(['ok' => true, 'message' => "We'll email you the moment it's back in stock! 💛"]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Something went wrong — please try again.']);
}
