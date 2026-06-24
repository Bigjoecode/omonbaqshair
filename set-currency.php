<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$c = strtoupper($_GET['c'] ?? '');
if (isset($GLOBALS['CURRENCIES'][$c])) {
    setcookie('currency', $c, time() + 60 * 60 * 24 * 90, '/');
    $_SESSION['currency'] = $c;
}
$back = $_SERVER['HTTP_REFERER'] ?? url('index.php');
header('Location: ' . $back);
