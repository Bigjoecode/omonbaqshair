<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$posts = fetchAll('SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY COALESCE(published_at, created_at) DESC');
$pageTitle = 'Hair Journal — Tips, Care & Inspiration — ' . SITE_NAME;
$pageDesc  = 'Expert hair care tips, styling guides and inspiration from ' . SITE_NAME . ' — wigs, bundles, frontals, closures & raw hair.';
$jsonLd = [ld_breadcrumb([
    ['name' => 'Home', 'url' => url('index.php')],
    ['name' => 'Journal', 'url' => url('blog.php')],
])];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Journal</div>
  <span class="eyebrow" style="margin-top:14px">Tips, Care &amp; Inspiration</span>
  <h1>The Hair Journal</h1>
</div></section>

<section class="section" style="padding-top:50px"><div class="container">
  <div class="product-grid" style="grid-template-columns:repeat(3,1fr)">
    <?php foreach ($posts as $i => $p): ?>
      <a href="<?= url('post.php?slug=' . urlencode($p['slug'])) ?>" class="product-card reveal d<?= ($i%3)+1 ?>" style="display:block">
        <div class="pc-media" style="aspect-ratio:16/11"><img class="main" src="<?= asset('img/' . ($p['cover_image'] ?: 'blog-1.jpg')) ?>" alt="<?= e($p['title']) ?>" loading="lazy" decoding="async"></div>
        <div class="pc-body" style="text-align:left;padding:22px">
          <div class="pc-cat"><?= e(date('M j, Y', strtotime($p['published_at'] ?: $p['created_at']))) ?> · <?= e($p['author']) ?></div>
          <h3 class="pc-title" style="font-size:1.3rem"><?= e($p['title']) ?></h3>
          <p class="muted" style="font-size:.9rem"><?= e($p['excerpt']) ?></p>
          <span class="badge-pill mt-2">Read More <i class="fa-solid fa-arrow-right-long"></i></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
