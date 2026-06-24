<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$homePage = fetch("SELECT * FROM pages WHERE slug = 'index' AND is_published = 1");
if ($homePage) {
    // If the title is 'Home', default to standard SEO title, otherwise use the custom title
    $pageTitle = strtolower(trim($homePage['title'])) === 'home' 
        ? SITE_NAME . ' — ' . SITE_TAGLINE 
        : $homePage['title'] . ' — ' . SITE_NAME;
    $pageDesc  = trim(mb_substr(strip_tags($homePage['body'] ?? ''), 0, 200)) ?: ($homePage['eyebrow'] ?: $homePage['title']);
    if ($homePage['cover_image'] && function_exists('media_url')) $pageImage = media_url($homePage['cover_image']);
}
// The Home page's cover image powers the "Our Promise" brand-story photo (falls back to the bundled image).
$storyImg = ($homePage && !empty($homePage['cover_image']) && function_exists('media_url'))
    ? media_url($homePage['cover_image'])
    : asset('img/about.jpg');

require_once __DIR__ . '/includes/header.php';

$heroEyebrow = setting('hero_eyebrow', 'Because you deserve hair that matches your radiance');
$heroTitle   = setting('hero_title', 'Home of luxury hairs & more');
$heroSub     = setting('hero_subtitle', 'Luxury wigs, bundles & raw hair for every queen — every skin tone, every texture, every crown.');

// Render the hero title with the last words in gold for an elegant accent.
$heroWords = preg_split('/\s+/', trim($heroTitle));
$goldFrom  = count($heroWords) > 3 ? 3 : max(1, count($heroWords) - 1);
$heroHead  = implode(' ', array_slice($heroWords, 0, $goldFrom));
$heroTail  = implode(' ', array_slice($heroWords, $goldFrom));

$featured = fetchAll(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.id DESC LIMIT 8"
);
if (count($featured) < 8) {
    $featured = fetchAll(
        "SELECT p.*, c.name AS category_name FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.is_active = 1 ORDER BY p.is_bestseller DESC, p.id DESC LIMIT 8"
    );
}
$bestsellers = fetchAll(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_bestseller = 1 ORDER BY p.review_count DESC LIMIT 4"
);
$cats = fetchAll("SELECT * FROM categories WHERE is_featured = 1 AND is_active = 1 ORDER BY sort_order, name LIMIT 8");
$testimonials = fetchAll("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order LIMIT 3");
?>

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="hero-bg"><img src="<?= asset('img/hero-main.jpg') ?>" alt="Omonblaq's luxury hair — premium wigs, bundles & raw hair" width="1920" height="1280" fetchpriority="high" decoding="async"></div>
  <div class="particles"></div>
  <div class="container hero-content">
    <span class="eyebrow"><?= e($heroEyebrow) ?></span>
    <h1 class="display"><?= e($heroHead) ?> <span class="text-gold"><?= e($heroTail) ?></span></h1>
    <p><?= e($heroSub) ?></p>
    <div class="hero-cta">
      <a href="<?= url('shop.php') ?>" class="btn btn-gold btn-lg">Shop The Collection</a>
      <a href="<?= url('shop.php?filter=bestseller') ?>" class="btn btn-ghost btn-lg">Best Sellers</a>
    </div>
  </div>
  <div class="scroll-cue"><span>Scroll</span><div class="line"></div></div>
</section>

<!-- ============ TRUST BAR ============ -->
<div class="trust-bar">
  <div class="trust-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span class="trust-item"><i class="fa-solid fa-crown"></i> 100% Virgin &amp; Raw Hair</span>
      <span class="trust-item"><i class="fa-solid fa-truck-fast"></i> Worldwide Shipping</span>
      <span class="trust-item"><i class="fa-solid fa-lock"></i> Secure Checkout — Stripe &amp; Paystack</span>
      <span class="trust-item"><i class="fa-solid fa-rotate-left"></i> Easy Returns</span>
      <span class="trust-item"><i class="fa-solid fa-headset"></i> AI Stylist 24/7</span>
      <span class="trust-item"><i class="fa-solid fa-gem"></i> Loved in 🇨🇦 &amp; 🇳🇬</span>
    <?php endfor; ?>
  </div>
</div>

<!-- ============ CATEGORIES ============ -->
<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Shop By Category</span>
      <h2>Find Your Texture</h2>
      <div class="divider-gold"></div>
      <p>From melt-skin HD wigs to single-donor raw hair — curated for every crown.</p>
    </div>
    <div class="tiles">
      <?php foreach ($cats as $i => $c): ?>
        <a href="<?= e(category_url($c)) ?>" class="tile reveal d<?= $i + 1 ?>">
          <img src="<?= e(media_url($c['image']) ?: asset('img/cat-wigs.jpg')) ?>" alt="<?= e($c['name']) ?> — shop at <?= e(SITE_NAME) ?>" loading="lazy" decoding="async">
          <div class="tile-border"></div>
          <div class="tile-body">
            <h3><?= e($c['name']) ?></h3>
            <span>Explore <i class="fa-solid fa-arrow-right-long"></i></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="section" style="background:var(--noir)">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Handpicked For You</span>
      <h2>The Signature Edit</h2>
      <div class="divider-gold"></div>
      <p>Our most-loved units, bundles and frontals — chosen by stylists, adored by queens.</p>
    </div>
    <div class="product-grid">
      <?php foreach ($featured as $i => $product):
        $reveal = 'reveal d' . (($i % 4) + 1);
        include __DIR__ . '/includes/product-card.php';
      endforeach; ?>
    </div>
    <div class="text-center mt-4 reveal">
      <a href="<?= url('shop.php') ?>" class="btn btn-outline btn-lg">View All Products</a>
    </div>
  </div>
</section>

<!-- ============ BRAND STORY SPLIT ============ -->
<section class="section">
  <div class="container split">
    <div class="split-media reveal">
      <img src="<?= e($storyImg) ?>" alt="<?= e($homePage['title'] ?? "Omonblaq's Hair") ?>" loading="lazy" decoding="async">
      <div class="frame"></div>
    </div>
    <div class="split-body reveal d1">
      <span class="eyebrow"><?= e($homePage['eyebrow'] ?? 'Our Promise') ?></span>
      <h2><?= $homePage && strtolower(trim($homePage['title'])) !== 'home' ? $homePage['title'] : 'Luxury Hair, <span class="text-gold">For Everyone</span>' ?></h2>
      <?php if ($homePage && $homePage['body']): ?>
        <?= preg_replace_callback('/\{\{\s*(asset|url):\s*([^}]+?)\s*\}\}/', fn($m) => $m[1] === 'asset' ? asset(trim($m[2])) : url(trim($m[2])), $homePage['body']) ?>
      <?php else: ?>
        <p>Omonblaq's Hair was born for every woman who deserves to feel like royalty. Our hair is for every skin tone and every texture — wigs, bundles, frontals, closures and raw hair, crafted to the highest standard.</p>
        <ul class="feature-list">
          <li><i class="fa-solid fa-crown"></i><div><h4>Single-Donor Raw Hair</h4><p>Unprocessed, cuticle-aligned and built to last for years.</p></div></li>
          <li><i class="fa-solid fa-wand-magic-sparkles"></i><div><h4>Melt-Skin HD Lace</h4><p>Pre-plucked, bleached knots and an undetectable hairline.</p></div></li>
          <li><i class="fa-solid fa-earth-africa"></i><div><h4>Canada &amp; Nigeria Shipping</h4><p>Fast, tracked delivery with prices in CAD, NGN &amp; USD.</p></div></li>
        </ul>
        <a href="<?= url('about.php') ?>" class="btn btn-gold">Read Our Story</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ BESTSELLERS ============ -->
<?php if ($bestsellers): ?>
<section class="section" style="background:var(--noir)">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Crowd Favourites</span>
      <h2>Best Sellers</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="product-grid">
      <?php foreach ($bestsellers as $i => $product):
        $reveal = 'reveal d' . (($i % 4) + 1);
        include __DIR__ . '/includes/product-card.php';
      endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ AI STYLIST CTA ============ -->
<section class="section" style="background:linear-gradient(135deg,var(--black),var(--ink))">
  <div class="container text-center reveal" style="max-width:720px">
    <span class="eyebrow">Meet Omon</span>
    <h2 class="mt-2">Your Personal AI Stylist</h2>
    <div class="divider-gold"></div>
    <p class="muted">Not sure what to choose? Tell Omon your budget, occasion and the look you love — and Omon will find your perfect hair in seconds and help you check out.</p>
    <button class="btn btn-gold btn-lg mt-3" id="openChatCta"><i class="fa-solid fa-crown"></i> Chat With Omon</button>
  </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<?php if ($testimonials): ?>
<section class="section testimonials">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">Loved Worldwide</span>
      <h2>Queens Who Trust Us</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="testi-grid">
      <?php foreach ($testimonials as $i => $t): ?>
        <div class="testi-card reveal d<?= $i + 1 ?>">
          <span class="quote">&rdquo;</span>
          <div class="stars"><?= str_repeat('★', (int)$t['rating']) ?></div>
          <p><?= e($t['body']) ?></p>
          <div class="testi-author">
            <div class="av"><?= e(strtoupper(substr($t['author'], 0, 1))) ?></div>
            <div><strong><?= e($t['author']) ?></strong><span><?= e($t['location']) ?></span></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>document.getElementById('openChatCta')?.addEventListener('click',()=>document.getElementById('chatToggle').click());</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
