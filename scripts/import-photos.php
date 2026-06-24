<?php
/**
 * Import REAL product photos from luxury lace-wig retailers and map them onto
 * the catalogue by category (wig / frontal / closure / bundle) + texture.
 *
 *   Wigs/frontals/closures/bundles -> indiquehair, hairvivi, truegloryhair,
 *                                     luvmehair, mayvenn, hermosahair (Shopify)
 *   Hair-care / accessories        -> haircarecanada.ca (Shopify)
 *
 * Builds rich galleries (up to 4 images / product). Re-runnable.
 *   php scripts/import-photos.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

@set_time_limit(0);
$DEST = UPLOAD_PATH . '/products';
if (!is_dir($DEST)) mkdir($DEST, 0775, true);
const IMAGES_PER_PRODUCT = 4;

function hg(string $url): string
{
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $r = curl_exec($c);
    curl_close($c);
    return is_string($r) ? $r : '';
}

function shopify_all(string $base, int $maxPages = 2): array
{
    $out = [];
    for ($pg = 1; $pg <= $maxPages; $pg++) {
        $d = json_decode(hg(rtrim($base, '/') . "/products.json?limit=250&page=$pg"), true);
        if (!$d || empty($d['products'])) break;
        foreach ($d['products'] as $p) $out[$p['handle'] . '|' . $pg] = $p;
        if (count($d['products']) < 250) break;
    }
    return $out;
}

/* ---- texture from a title/name ---- */
function texture_of(string $s): string
{
    $s = strtolower($s);
    if (str_contains($s, 'deep wave') || str_contains($s, 'deep curl')) return 'deep';
    if (str_contains($s, 'water wave')) return 'water';
    if (str_contains($s, 'loose wave') || str_contains($s, 'natural wave') || str_contains($s, 'loose deep')) return 'loose';
    if (str_contains($s, 'body wave') || str_contains($s, 'ocean wave')) return 'body';
    if (str_contains($s, 'kinky') || str_contains($s, 'coily') || str_contains($s, 'afro') || str_contains($s, 'jerry')) return 'kinky';
    if (str_contains($s, 'curl')) return 'curly';
    if (str_contains($s, 'straight') || str_contains($s, 'silky') || str_contains($s, 'bone') || str_contains($s, 'yaki')) return 'straight';
    if (str_contains($s, 'wave') || str_contains($s, 'wavy')) return 'body';
    return 'any';
}

/* ---- category bucket of a SOURCE product (wig wins over frontal/closure) ---- */
function source_category(string $title): ?string
{
    $t = strtolower($title);
    if (str_contains($t, 'wig')) return 'wig';
    if (str_contains($t, 'frontal') || str_contains($t, ' 13x4') || str_contains($t, '13x6') || str_contains($t, '360 lace')) return 'frontal';
    if (str_contains($t, 'closure') || str_contains($t, '4x4') || str_contains($t, '5x5') || str_contains($t, '6x6') || str_contains($t, '7x7')) return 'closure';
    if (str_contains($t, 'bundle') || str_contains($t, 'weave') || str_contains($t, 'weft') || str_contains($t, 'extension')) return 'bundle';
    return null;
}

$JUNK = ['deal', 'promo', 'banner', 'size', 'chart', 'guide', 'measure', 'swatch', 'ring', 'colour-card', 'color-card',
         'length', 'install', 'step', 'how-to', 'review', 'gift', 'coupon', 'sale', 'logo', 'icon', 'compare', 'faq', 'detail-'];

echo "Fetching luxury lace-wig catalogues...\n";
$sites = ['www.indiquehair.com', 'www.hairvivi.com', 'www.truegloryhair.com', 'luvmehair.com', 'www.mayvenn.com', 'www.hermosahair.com'];
$hair = [];
foreach ($sites as $s) {
    $got = shopify_all("https://$s");
    foreach ($got as $k => $p) { $p['__src'] = $s; $hair["$s|$k"] = $p; }
    echo '  ' . str_pad($s, 24) . ' ' . count($got) . "\n";
}
$care = shopify_all('https://www.haircarecanada.ca');
echo '  hair: ' . count($hair) . ' | care: ' . count($care) . "\n";

/* ---- Build cat:texture buckets ---- */
$buckets = []; // key "cat:tex" => [urls]
foreach ($hair as $p) {
    if (empty($p['images'])) continue;
    $cat = source_category($p['title']);
    if (!$cat) continue;
    $tex = texture_of($p['title']);
    $imgs = array_slice($p['images'], 0, 4); // main + a few gallery angles
    foreach ($imgs as $img) {
        $src = $img['src'] ?? '';
        if (!$src) continue;
        $bn = strtolower(basename(parse_url($src, PHP_URL_PATH)));
        foreach ($JUNK as $j) if (str_contains($bn, $j)) continue 2;
        $buckets["$cat:$tex"][] = $src;
        $buckets["$cat:any"][] = $src;   // also feed the catch-all for the category
    }
}
echo "  buckets:\n";
foreach ($buckets as $k => $v) if (!str_ends_with($k, ':any') || true) { if (str_ends_with($k, ':any')) echo '    ' . str_pad($k, 14) . count($v) . "\n"; }

/* ---- care buckets ---- */
$cbuckets = ['oil' => [], 'shampoo' => [], 'conditioner' => [], 'styling' => []];
foreach ($care as $p) {
    if (empty($p['images'])) continue;
    $t = strtolower($p['title'] . ' ' . ($p['product_type'] ?? ''));
    if (str_contains($t, 'oil') || str_contains($t, 'serum') || str_contains($t, 'elixir')) $b = 'oil';
    elseif (str_contains($t, 'shampoo') || str_contains($t, 'cleanse') || str_contains($t, 'wash')) $b = 'shampoo';
    elseif (str_contains($t, 'condition') || str_contains($t, 'mask') || str_contains($t, 'treat') || str_contains($t, 'repair') || str_contains($t, 'leave-in')) $b = 'conditioner';
    else $b = 'styling';
    foreach (array_slice($p['images'], 0, 4) as $img) if (!empty($img['src'])) $cbuckets[$b][] = $img['src'];
}

/** Download with a width cap; reject wide banners & tiny images. */
function download_image(string $url, string $dest, string $name): ?string
{
    $clean = $url . (str_contains($url, '?') ? '&' : '?') . 'width=900';
    $bytes = hg($clean);
    if (strlen($bytes) < 4096) return null;
    $info = @getimagesizefromstring($bytes);
    if (!$info || $info[0] < 380) return null;
    [$w, $h] = $info;
    if ($w > $h * 1.25) return null; // drop landscape banners / size charts
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime']] ?? 'jpg';
    $file = $name . '.' . $ext;
    return file_put_contents($dest . '/' . $file, $bytes) !== false ? $file : null;
}

/* ---- product -> (bucketCat, texture) ---- */
function map_product(string $catSlug, string $name): array
{
    $cat = match ($catSlug) {
        'wigs' => 'wig', 'frontals' => 'frontal', 'closures' => 'closure',
        default => 'bundle', // bundles, raw-hair, bundle-deals
    };
    return [$cat, texture_of($name)];
}

$pdo = db();
$insImg = $pdo->prepare('INSERT INTO product_images (product_id, image, is_primary, sort_order) VALUES (?,?,?,?)');
$products = fetchAll('SELECT p.id, p.name, c.slug AS cat FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id');

$used = [];        // global url -> true (avoid repeating the exact same image)
$cursor = [];      // pool key -> offset
$report = [];

foreach ($products as $p) {
    $id = (int)$p['id'];
    $isCare = in_array($p['cat'], ['hair-care', 'accessories'], true);

    if ($isCare) {
        $cb = (function ($n) {
            $n = strtolower($n);
            if (str_contains($n, 'oil')) return 'oil';
            if (str_contains($n, 'shampoo')) return 'shampoo';
            if (str_contains($n, 'condition') || str_contains($n, 'mask') || str_contains($n, 'repair')) return 'conditioner';
            return 'styling';
        })($p['name']);
        $pools = [$cbuckets[$cb], $cbuckets['styling'], $cbuckets['conditioner'], $cbuckets['shampoo']];
        $label = "care/$cb";
    } else {
        [$cat, $tex] = map_product($p['cat'], $p['name']);
        $order = ["$cat:$tex", "$cat:any", "bundle:$tex", "wig:$tex", "$cat:body", "wig:any", "bundle:any"];
        $pools = [];
        foreach ($order as $k) if (!empty($buckets[$k])) $pools[] = $buckets[$k];
        $label = "$cat/$tex";
    }

    // flatten pools in priority order, dedupe
    $pool = [];
    foreach ($pools as $pl) foreach ($pl as $u) $pool[$u] = true;
    $pool = array_keys($pool);
    if (!$pool) { $report[] = "  ! p$id {$p['name']} — empty pool ($label)"; continue; }

    $start = $cursor[$label] ?? 0;
    $got = [];
    $scan = 0; $n = count($pool);
    while (count($got) < IMAGES_PER_PRODUCT && $scan < min($n, 40)) {
        $url = $pool[($start + $scan) % $n];
        $scan++;
        if (isset($used[$url])) continue;
        $f = download_image($url, $DEST, "real_p{$id}_" . (count($got) + 1) . '_' . substr(md5($url), 0, 6));
        if ($f) { $got[] = $f; $used[$url] = true; }
    }
    $cursor[$label] = $start + $scan;

    // if we couldn't get enough unique ones, allow reuse to reach at least 2
    if (count($got) < 2) {
        $scan = 0;
        while (count($got) < 2 && $scan < min($n, 40)) {
            $url = $pool[$scan++];
            $f = download_image($url, $DEST, "real_p{$id}_" . (count($got) + 1) . '_' . substr(md5($url . $scan), 0, 6));
            if ($f) $got[] = $f;
        }
    }
    if (!$got) { $report[] = "  ! p$id {$p['name']} — download failed ($label)"; continue; }

    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
    foreach ($got as $i => $f) $insImg->execute([$id, $f, $i === 0 ? 1 : 0, $i]);
    $report[] = "  ok p$id [$label] " . count($got) . " imgs";
}

echo "\n=== RESULT ===\n" . implode("\n", $report) . "\n";
echo "\nDone. " . count($products) . " products processed.\n";
