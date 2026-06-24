<?php
/**
 * Renders a CMS page. Set $cmsSlug before including this file.
 * Falls back to a 404-style message if the page is missing/unpublished.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/seo.php';

$cmsSlug = $cmsSlug ?? '';
$page = $cmsSlug ? fetch('SELECT * FROM pages WHERE slug = ? AND is_published = 1', [$cmsSlug]) : null;

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Page not found — ' . SITE_NAME;
    $noindex = true;
    require_once __DIR__ . '/header.php';
    echo '<section class="page-hero"><div class="container"><h1>Page not found</h1>'
       . '<a class="btn btn-outline mt-3" href="' . url('index.php') . '">Back home</a></div></section>';
    require_once __DIR__ . '/footer.php';
    return;
}

$coverUrl  = media_url($page['cover_image'] ?? null);
// Portable tokens in body (survive host changes):
//   {{asset:foo.jpg}} -> resolved image URL   ·   {{url:shop.php}} -> clean site URL
$pageBody = preg_replace_callback('/\{\{\s*(asset|url):\s*([^}]+?)\s*\}\}/', function ($m) {
    return $m[1] === 'asset' ? media_url(trim($m[2])) : url(trim($m[2]));
}, (string)$page['body']);
$pageTitle = $page['title'] . ' — ' . SITE_NAME;
$pageDesc  = trim(mb_substr(strip_tags($page['body'] ?? ''), 0, 200)) ?: ($page['eyebrow'] ?: $page['title']);
if ($coverUrl) $pageImage = $coverUrl;
$jsonLd = [ld_breadcrumb([
    ['name' => 'Home', 'url' => url('index.php')],
    ['name' => strip_tags($page['title']), 'url' => canonical_url()],
])];
require_once __DIR__ . '/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / <?= strip_tags($page['title']) ?></div>
    <?php if ($page['eyebrow']): ?><span class="eyebrow" style="margin-top:14px"><?= e($page['eyebrow']) ?></span><?php endif; ?>
    <h1><?= $page['title'] ?></h1>
  </div>
</section>

<section class="section" style="padding-top:50px">
  <div class="container" style="max-width:860px">
    <?php if ($coverUrl): ?>
      <div class="prose-cover reveal"><img src="<?= e($coverUrl) ?>" alt="<?= e(strip_tags($page['title'])) ?>"></div>
    <?php endif; ?>
    <div class="prose reveal"><?= $pageBody ?></div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
