<?php
/** Royalty API — balance lookup. */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'balance';
if ($action === 'balance') {
    $email = strtolower(trim($_GET['email'] ?? ''));
    $pts = $email ? royalty_points($email) : 0;
    echo json_encode([
        'points'        => $pts,
        'value_cad'     => round($pts * POINTS_VALUE_CAD, 2),
        'value_display' => money($pts * POINTS_VALUE_CAD),
    ]);
    exit;
}
echo json_encode(['error' => 'unknown action']);
