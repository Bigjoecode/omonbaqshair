<?php
/**
 * Replace the homepage section imagery with real lace-wig photos:
 *   - category tiles  -> assets/img/cat-<slug>.jpg  (+ categories.image)
 *   - hero background -> assets/img/hero-main.jpg
 * Re-runnable.  php scripts/import-sections.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

@set_time_limit(0);
$IMG = ROOT_PATH . '/assets/img';

function hg(string $url): string
{
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $r = curl_exec($c); curl_close($c);
    return is_string($r) ? $r : '';
}
function shopify(string $base, int $pages = 2): array
{
    $o = [];
    for ($pg = 1; $pg <= $pages; $pg++) {
        $d = json_decode(hg(rtrim($base, '/') . "/products.json?limit=250&page=$pg"), true);
        if (!$d || empty($d['products'])) break;
        $o = array_merge($o, $d['products']);
        if (count($d['products']) < 250) break;
    }
    return $o;
}
function cat_of(string $t): ?string
{
    $t = strtolower($t);
    if (str_contains($t, 'wig')) return 'wig';
    if (str_contains($t, 'frontal') || str_contains($t, '13x4') || str_contains($t, '13x6') || str_contains($t, '360 lace')) return 'frontal';
    if (str_contains($t, 'closure') || str_contains($t, '4x4') || str_contains($t, '5x5') || str_contains($t, '6x6')) return 'closure';
    if (str_contains($t, 'bundle') || str_contains($t, 'weave') || str_contains($t, 'weft')) return 'bundle';
    return null;
}

$JUNK = ['size', 'chart', 'guide', 'measure', 'swatch', 'ring', 'length', 'install', 'step', 'how', 'review', 'logo', 'icon', 'detail-', 'banner', 'deal', 'color-', 'colour-'];

echo "Collecting category imagery...\n";
$bk = ['wig' => [], 'frontal' => [], 'closure' => [], 'bundle' => []];
// Premium US brands shoot clean studio editorials (no price/returns banners baked into the image)
foreach (['https://www.indiquehair.com', 'https://www.hermosahair.com', 'https://www.mayvenn.com'] as $site) {
    foreach (shopify($site, 1) as $p) {
        $cat = cat_of($p['title']);
        if (!$cat || empty($p['images'])) continue;
        $src = $p['images'][0]['src'] ?? '';   // first image = main model shot
        if (!$src) continue;
        $bn = strtolower(basename(parse_url($src, PHP_URL_PATH)));
        foreach ($JUNK as $j) if (str_contains($bn, $j)) continue 2;
        $bk[$cat][] = $src;
    }
}
foreach ($bk as $k => $v) echo "  $k: " . count($v) . "\n";

/** download a portrait (or wide for hero) to a path */
function grab(array &$pool, string $dest, int $minW, bool $portrait): ?string
{
    while ($pool) {
        $url = array_shift($pool);
        $bytes = hg($url . (str_contains($url, '?') ? '&' : '?') . 'width=' . max(1000, $minW));
        if (strlen($bytes) < 6000) continue;
        $info = @getimagesizefromstring($bytes);
        if (!$info || $info[0] < $minW) continue;
        [$w, $h] = $info;
        if ($portrait && $h < $w) continue;        // portrait tiles
        if (file_put_contents($dest, $bytes) !== false) return $dest;
    }
    return null;
}

/* ---- Category tiles ----
 * Bundles/raw/deals draw from the deep, clean single-model WIG pool (the bundle
 * feed is tiny and full of text "DEAL" collages). Deterministic order = repeatable. */
$map = ['wigs' => 'wig', 'bundles' => 'wig', 'frontals' => 'frontal', 'closures' => 'closure', 'raw-hair' => 'wig', 'bundle-deals' => 'wig'];
foreach ($map as $slug => $cat) {
    $file = "cat-$slug.jpg";
    if (grab($bk[$cat], "$IMG/$file", 600, true)) {
        q('UPDATE categories SET image = ? WHERE slug = ?', [$file, $slug]);
        echo "  tile $slug <- $cat ($file)\n";
    } else {
        echo "  ! tile $slug — no image\n";
    }
}

/* ---- Hero background ---- */
$heroPool = array_merge($bk['wig'], $bk['frontal']);
shuffle($heroPool);
if (grab($heroPool, "$IMG/hero-main.jpg", 1200, true)) {
    echo "  hero <- new image (hero-main.jpg)\n";
} else {
    echo "  ! hero — no image\n";
}

echo "Done.\n";
