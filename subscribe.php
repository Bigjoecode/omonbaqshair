<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if ($email) {
        try { q('INSERT IGNORE INTO newsletter (email) VALUES (?)', [$email]); } catch (Throwable $e) {}
        flash('news', 'Welcome to the royalty! Check your inbox for 10% off.');
    } else {
        flash('news', 'Please enter a valid email.');
    }
}
redirect(($_SERVER['HTTP_REFERER'] ?? url('index.php')) . '#newsletter');
