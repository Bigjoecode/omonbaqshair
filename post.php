<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$slug = $_GET['slug'] ?? '';
$post = fetch('SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1', [$slug]);
if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post not found — ' . SITE_NAME;
    $noindex = true;
    require __DIR__ . '/includes/header.php';
    echo '<section class="page-hero"><div class="container"><h1>Post not found</h1><a class="btn btn-outline mt-3" href="'.url('blog.php').'">Back to Journal</a></div></section>';
    require_once __DIR__.'/includes/footer.php';
    exit;
}
$postImage = asset('img/' . ($post['cover_image'] ?: 'blog-1.jpg'));
$pageTitle = $post['title'] . ' — ' . SITE_NAME;
$pageDesc  = $post['excerpt'] ?: ('Read "' . $post['title'] . '" on the ' . SITE_NAME . ' Hair Journal.');
$pageImage = $postImage;
$ogType    = 'article';
$jsonLd = [
    ld_breadcrumb([
        ['name' => 'Home', 'url' => url('index.php')],
        ['name' => 'Journal', 'url' => url('blog.php')],
        ['name' => $post['title'], 'url' => url('post.php?slug=' . urlencode($post['slug']))],
    ]),
    ld_article($post, $postImage),
];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero" style="padding-bottom:30px"><div class="container">
  <div class="breadcrumb"><a href="<?= url('blog.php') ?>">Journal</a> / <?= e($post['title']) ?></div>
</div></section>
<article class="section" style="padding-top:20px"><div class="container" style="max-width:820px">
  <div class="reveal">
    <span class="eyebrow"><?= e(date('M j, Y', strtotime($post['published_at'] ?: $post['created_at']))) ?> · <?= e($post['author']) ?></span>
    <h1 style="font-size:clamp(2rem,4vw,3rem);margin:14px 0 24px"><?= e($post['title']) ?></h1>
  </div>
  <div class="split-media reveal" style="aspect-ratio:16/9;margin-bottom:34px"><img src="<?= e($postImage) ?>" alt="<?= e($post['title']) ?>" decoding="async"></div>
  <div class="reveal muted" style="font-size:1.08rem;line-height:1.9"><?= $post['body'] ?></div>
  <a href="<?= url('shop.php') ?>" class="btn btn-gold mt-4">Shop The Look</a>
</div></article>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
