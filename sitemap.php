<?php
/**
 * Dynamic XML sitemap. Served at /sitemap.xml via .htaccess rewrite.
 * Lists the homepage, key landing pages, all categories, active products,
 * CMS pages and published blog posts.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [];
$add = function (string $loc, string $lastmod = '', string $changefreq = 'weekly', string $priority = '0.6') use (&$urls) {
    $urls[] = compact('loc', 'lastmod', 'changefreq', 'priority');
};

// Core pages
$add(url('index.php'),         date('Y-m-d'), 'daily',  '1.0');
$add(url('shop.php'),          date('Y-m-d'), 'daily',  '0.9');
$add(url('build-bundle.php'),  '', 'monthly', '0.7');
$add(url('rewards.php'),       '', 'monthly', '0.6');
$add(url('blog.php'),          date('Y-m-d'), 'weekly', '0.6');
$add(url('contact.php'),       '', 'yearly',  '0.4');

// Categories
try {
    foreach (fetchAll('SELECT slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name') as $c) {
        $add(url('shop.php?category=' . urlencode($c['slug'])), '', 'weekly', '0.8');
    }
} catch (Throwable $e) {}

// Products
try {
    foreach (fetchAll('SELECT slug FROM products WHERE is_active = 1') as $p) {
        $add(url('product.php?slug=' . urlencode($p['slug'])), '', 'weekly', '0.8');
    }
} catch (Throwable $e) {}

// CMS pages
try {
    foreach (fetchAll('SELECT slug, updated_at FROM pages WHERE is_published = 1') as $pg) {
        $add(url($pg['slug'] . '.php'), substr((string)$pg['updated_at'], 0, 10), 'monthly', '0.5');
    }
} catch (Throwable $e) {}

// Blog posts
try {
    foreach (fetchAll('SELECT slug, published_at, created_at FROM blog_posts WHERE is_published = 1') as $b) {
        $add(url('post.php?slug=' . urlencode($b['slug'])), substr((string)($b['published_at'] ?: $b['created_at']), 0, 10), 'monthly', '0.6');
    }
} catch (Throwable $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    if ($u['lastmod']) echo "    <lastmod>{$u['lastmod']}</lastmod>\n";
    echo "    <changefreq>{$u['changefreq']}</changefreq>\n    <priority>{$u['priority']}</priority>\n  </url>\n";
}
echo '</urlset>';
