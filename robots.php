<?php
/** Dynamic robots.txt — served at /robots.txt via .htaccess rewrite. */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');

$base = rtrim(BASE_URL, '/');
?>
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /checkout
Disallow: /cart
Disallow: /order-success
Disallow: /set-currency

Sitemap: <?= $base ?>/sitemap.xml
