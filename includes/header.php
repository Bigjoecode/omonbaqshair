<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/seo.php';

$pageTitle  = $pageTitle  ?? SITE_NAME . ' — ' . SITE_TAGLINE;
$pageDesc   = $pageDesc   ?? 'Shop luxury wigs, bundles, frontals, closures and raw human hair at ' . SITE_NAME . '. Fast, tracked delivery across Canada, Nigeria, the US, UK & Africa. Prices in CAD, USD & NGN.';
$pageImage  = $pageImage  ?? asset('img/hero-main.jpg');
$ogType     = $ogType     ?? 'website';
$canonical  = $canonical  ?? canonical_url();
$noindex    = $noindex    ?? false;
$bodyClass  = $bodyClass  ?? '';

$navCategories = [];
try {
    $navCategories = fetchAll('SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order, name');
} catch (Throwable $e) {}

$cc = current_currency();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="robots" content="<?= $noindex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large' ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#0a0908">
<meta name="author" content="<?= e(SITE_NAME) ?>">
<meta name="geo.region" content="CA-ON">
<meta name="geo.placename" content="Brampton, Ontario">
<link rel="icon" type="image/png" href="<?= asset('img/logo.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo.png') ?>">

<!-- Open Graph -->
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:image" content="<?= e($pageImage) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:locale" content="en_CA">
<meta property="og:locale:alternate" content="en_NG">
<meta property="og:locale:alternate" content="en_GB">
<meta property="og:locale:alternate" content="en_US">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($pageDesc) ?>">
<meta name="twitter:image" content="<?= e($pageImage) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Italianno&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= asset('css/styles.css') ?>?v=12">

<!-- Structured data -->
<?= jsonld_tag(ld_organization()) ?>
<?= jsonld_tag(ld_website()) ?>
<?= jsonld_tag(ld_localbusiness()) ?>
<?php foreach (($jsonLd ?? []) as $__block): if (is_array($__block)) echo jsonld_tag($__block); endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<script>window.OMB = { base: "<?= rtrim(BASE_URL,'/') ?>", currency: "<?= e(current_currency()) ?>" };</script>

<!-- Announcement -->
<div class="announce">
  <div class="marquee">
    <span>Free shipping on orders over <?= money(150) ?></span>
    <span>100% Virgin &amp; Raw Human Hair</span>
    <span>Worldwide Delivery — Canada · Nigeria · USA</span>
    <span>Secure payments — Stripe &amp; Paystack</span>
    <span>Free shipping on orders over <?= money(150) ?></span>
    <span>100% Virgin &amp; Raw Human Hair</span>
    <span>Worldwide Delivery — Canada · Nigeria · USA</span>
    <span>Secure payments — Stripe &amp; Paystack</span>
  </div>
</div>

<header class="site-header" id="siteHeader">
  <div class="container nav">
    <a href="<?= url('index.php') ?>" class="brand">
      <img src="<?= asset('img/logo.png') ?>" alt="<?= e(SITE_NAME) ?> — luxury hair" width="150" height="60" fetchpriority="high" decoding="async">
    </a>

    <nav>
      <ul class="nav-menu" id="navMenu">
        <li><a href="<?= url('index.php') ?>">Home</a></li>
        <li class="has-mega">
          <a href="<?= url('shop.php') ?>">Shop <i class="fa-solid fa-angle-down" style="font-size:.7em"></i></a>
          <div class="mega">
            <?php foreach ($navCategories as $c): ?>
              <a href="<?= e(category_url($c)) ?>">
                <?= e($c['name']) ?>
                <?php if (!empty($c['description'])): ?><small><?= e($c['description']) ?></small><?php endif; ?>
              </a>
            <?php endforeach; ?>
            <?php if (!$navCategories): ?>
              <a href="<?= url('shop.php') ?>">All Products</a>
            <?php endif; ?>
          </div>
        </li>
        <li><a href="<?= url('build-bundle.php') ?>">Build a Bundle</a></li>
        <li><a href="<?= url('rewards.php') ?>"><i class="fa-solid fa-crown" style="font-size:.8em;color:var(--gold)"></i> Rewards</a></li>
        <li><a href="<?= url('about.php') ?>">Our Story</a></li>
        <li><a href="<?= url('blog.php') ?>">Journal</a></li>
        <li><a href="<?= url('contact.php') ?>">Contact</a></li>
      </ul>
    </nav>

    <div class="nav-actions">
      <div class="currency-switch">
        <button type="button"><i class="fa-solid fa-globe"></i> <?= e($cc) ?> <i class="fa-solid fa-angle-down" style="font-size:.7em"></i></button>
        <div class="currency-menu">
          <?php foreach ($GLOBALS['CURRENCIES'] as $code => $info): ?>
            <a href="<?= url('set-currency.php?c=' . $code) ?>"><?= e($info['symbol']) ?> <?= e($code) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?= url('shop.php') ?>" class="icon-btn" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></a>
      <a href="<?= url('wishlist.php') ?>" class="icon-btn" aria-label="Wishlist" id="wishLink">
        <i class="fa-regular fa-heart"></i>
        <span class="cart-count" id="wishCount" style="display:none">0</span>
      </a>
      <button class="icon-btn cart-toggle" aria-label="Cart" id="cartToggle">
        <i class="fa-solid fa-bag-shopping"></i>
        <span class="cart-count" id="cartCount"><?= cart_count() ?></span>
      </button>
      <button class="nav-toggle" id="navToggle" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>

<!-- Cart drawer -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="dh">
    <h3>Your Bag</h3>
    <button class="icon-btn" id="cartClose"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="cart-lines" id="cartLines"><!-- filled by JS --></div>
  <div class="cart-foot" id="cartFoot"></div>
</aside>

<main>
