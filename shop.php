<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$catSlug   = $_GET['category'] ?? '';
$filter    = $_GET['filter'] ?? '';
$search    = trim($_GET['q'] ?? '');
$texture   = $_GET['texture'] ?? '';
$origin    = $_GET['origin'] ?? '';
$sort      = $_GET['sort'] ?? 'featured';

$where = ['p.is_active = 1'];
$params = [];

$activeCat = null;
if ($catSlug) {
    $activeCat = fetch('SELECT * FROM categories WHERE slug = ?', [$catSlug]);
    if ($activeCat) { $where[] = 'p.category_id = ?'; $params[] = $activeCat['id']; }
}
if ($filter === 'bestseller') $where[] = 'p.is_bestseller = 1';
if ($filter === 'new')        $where[] = 'p.is_new = 1';
if ($filter === 'sale')       $where[] = 'p.sale_price IS NOT NULL';
if ($search) { $where[] = '(p.name LIKE ? OR p.description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($texture) { $where[] = 'p.texture = ?'; $params[] = $texture; }
if ($origin)  { $where[] = 'p.origin = ?';  $params[] = $origin; }

$order = match ($sort) {
    'price_low'  => 'COALESCE(p.sale_price,p.price) ASC',
    'price_high' => 'COALESCE(p.sale_price,p.price) DESC',
    'newest'     => 'p.id DESC',
    'rating'     => 'p.rating DESC, p.review_count DESC',
    default      => 'p.is_featured DESC, p.is_bestseller DESC, p.id DESC',
};

$whereSql = implode(' AND ', $where);
$products = fetchAll(
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql ORDER BY $order",
    $params
);

$allCats   = fetchAll('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name');
$textures  = fetchAll("SELECT DISTINCT texture FROM products WHERE texture IS NOT NULL AND texture <> '' ORDER BY texture");
$origins   = fetchAll("SELECT DISTINCT origin FROM products WHERE origin IS NOT NULL AND origin <> '' ORDER BY origin");

$pageTitle = ($activeCat['name'] ?? ($search ? "Search: $search" : 'Shop All Hair')) . ' — ' . SITE_NAME;
$pageDesc  = !empty($activeCat['description'])
    ? $activeCat['description'] . ' Shop ' . $activeCat['name'] . ' at ' . SITE_NAME . ' — worldwide delivery.'
    : 'Shop ' . ($activeCat['name'] ?? 'luxury wigs, bundles, frontals, closures & raw hair') . ' at ' . SITE_NAME . '. Filter by texture, origin & price. Delivery across Canada, Nigeria, US, UK & Africa.';
$noindex   = ($search !== '' || $texture !== '' || $origin !== '' || $sort !== 'featured'); // keep crawl budget on canonical category pages

$crumbs = [['name' => 'Home', 'url' => url('index.php')]];
$crumbs[] = ['name' => 'Shop', 'url' => url('shop.php')];
if ($activeCat) $crumbs[] = ['name' => $activeCat['name'], 'url' => category_url($activeCat)];
$jsonLd = [ld_breadcrumb($crumbs)];
if ($products) {
    $itemList = [];
    foreach (array_slice($products, 0, 24) as $i => $pr) {
        $itemList[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => product_url($pr), 'name' => $pr['name']];
    }
    $jsonLd[] = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $itemList];
}

function qs(array $over = []): string { return '?' . http_build_query(array_merge($_GET, $over)); }
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / <?= e($activeCat['name'] ?? 'Shop') ?></div>
    <span class="eyebrow" style="margin-top:14px">The Collection</span>
    <h1><?= e($activeCat['name'] ?? ($filter === 'bestseller' ? 'Best Sellers' : ($search ? 'Search Results' : 'All Products'))) ?></h1>
    <?php if (!empty($activeCat['description'])): ?><p class="muted"><?= e($activeCat['description']) ?></p><?php endif; ?>
  </div>
</section>

<section class="section" style="padding-top:50px">
  <div class="container shop-wrap">
    <!-- Filters -->
    <aside class="filters">
      <form method="get" id="shopSearch" class="filter-block">
        <h4>Search</h4>
        <div style="display:flex;gap:8px">
          <input class="form-control" type="text" name="q" value="<?= e($search) ?>" placeholder="Search hair...">
          <button class="btn btn-gold" style="padding:0 16px"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
      </form>
      <div class="filter-block">
        <h4>Categories</h4>
        <a href="<?= url('shop.php') ?>" class="<?= !$catSlug ? 'active' : '' ?>">All Products</a>
        <?php foreach ($allCats as $c): ?>
          <a href="<?= e(category_url($c)) ?>" class="<?= $catSlug === $c['slug'] ? 'active' : '' ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php if ($textures): ?>
      <div class="filter-block">
        <h4>Texture</h4>
        <a href="<?= e(qs(['texture' => null])) ?>" class="<?= !$texture ? 'active' : '' ?>">All Textures</a>
        <?php foreach ($textures as $t): ?>
          <a href="<?= e(qs(['texture' => $t['texture']])) ?>" class="<?= $texture === $t['texture'] ? 'active' : '' ?>"><?= e($t['texture']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if ($origins): ?>
      <div class="filter-block" style="border-bottom:none;margin-bottom:0;padding-bottom:0">
        <h4>Origin</h4>
        <a href="<?= e(qs(['origin' => null])) ?>" class="<?= !$origin ? 'active' : '' ?>">All Origins</a>
        <?php foreach ($origins as $o): ?>
          <a href="<?= e(qs(['origin' => $o['origin']])) ?>" class="<?= $origin === $o['origin'] ? 'active' : '' ?>"><?= e($o['origin']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </aside>

    <!-- Results -->
    <div>
      <div class="shop-toolbar">
        <span class="muted"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></span>
        <form method="get" onchange="this.submit()">
          <?php foreach ($_GET as $k => $v): if ($k === 'sort') continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endforeach; ?>
          <select name="sort">
            <option value="featured"   <?= $sort === 'featured' ? 'selected' : '' ?>>Featured</option>
            <option value="newest"     <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_low"  <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="rating"     <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
          </select>
        </form>
      </div>

      <?php if ($products): ?>
        <div class="product-grid">
          <?php foreach ($products as $i => $product):
            $reveal = 'reveal d' . (($i % 4) + 1);
            include __DIR__ . '/includes/product-card.php';
          endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center" style="padding:80px 0">
          <i class="fa-solid fa-magnifying-glass" style="font-size:3rem;color:var(--line);margin-bottom:20px"></i>
          <h3>No products found</h3>
          <p class="muted mt-1">Try a different search or browse all products.</p>
          <a href="<?= url('shop.php') ?>" class="btn btn-outline mt-3">View All Products</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
