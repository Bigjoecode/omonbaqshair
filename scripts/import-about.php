<?php
/**
 * Pull editorial model portraits from the lace-wig retailers and:
 *   - replace the homepage brand-story image (assets/img/about.jpg)
 *   - add about-1..about-4.jpg for the About page
 *   - rewrite the About CMS page with richer, image-led content
 * Re-runnable.  php scripts/import-about.php
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

echo "Collecting editorial model portraits...\n";
$pool = [];
$JUNK = ['size', 'chart', 'guide', 'measure', 'swatch', 'ring', 'length', 'install', 'step', 'how', 'review', 'logo', 'icon', 'detail-', 'banner', 'deal'];
foreach (['https://www.hairvivi.com', 'https://luvmehair.com', 'https://www.hermosahair.com', 'https://www.indiquehair.com', 'https://www.mayvenn.com'] as $site) {
    foreach (shopify($site, 1) as $p) {
        $t = strtolower($p['title']);
        if (!str_contains($t, 'wig')) continue;          // model-on-wig shots
        foreach (array_slice($p['images'] ?? [], 0, 2) as $img) {
            $src = $img['src'] ?? '';
            if (!$src) continue;
            $bn = strtolower(basename(parse_url($src, PHP_URL_PATH)));
            foreach ($JUNK as $j) if (str_contains($bn, $j)) continue 2;
            $pool[$src] = true;
        }
    }
}
$pool = array_keys($pool);
echo '  candidates: ' . count($pool) . "\n";

/** download, keep only clear portraits */
function grab(array &$pool, string $dest, int $minRatio100 = 115): ?string
{
    while ($pool) {
        $url = array_shift($pool);
        $bytes = hg($url . (str_contains($url, '?') ? '&' : '?') . 'width=1100');
        if (strlen($bytes) < 6000) continue;
        $info = @getimagesizefromstring($bytes);
        if (!$info || $info[0] < 600) continue;
        [$w, $h] = $info;
        if ($h * 100 < $w * $minRatio100) continue;       // require portrait
        if (file_put_contents($dest, $bytes) !== false) return $dest;
    }
    return null;
}

shuffle($pool); // variety on each run
$targets = ['about.jpg', 'about-1.jpg', 'about-2.jpg', 'about-3.jpg', 'about-4.jpg'];
$saved = [];
foreach ($targets as $name) {
    $f = grab($pool, "$IMG/$name");
    if ($f) { $saved[] = $name; echo "  saved $name\n"; }
}
if (count($saved) < 5) { fwrite(STDERR, "WARN: only saved " . count($saved) . " images\n"); }

/* ---- Rewrite the About page ---- */
$body = <<<HTML
<div class="about-editorial">
  <div class="about-split reveal">
    <div class="about-hero-text">
      <p><strong>Omonblaq's Hair — Home of Luxury Hair &amp; More.</strong> We were born from a simple belief: every woman, of every skin tone and every texture, deserves to feel like royalty. From our home in Brampton, Ontario, we hand-curate the finest wigs, bundles, frontals, closures and raw hair — and ship them to queens across Canada, Nigeria, the United States, the United Kingdom and all of Africa.</p>
    </div>
    <div class="about-media">
      <img src="{{asset:img/about-1.jpg}}" alt="Omonblaq's Hair luxury HD lace wig" loading="lazy" decoding="async">
    </div>
  </div>

  <div class="about-mission-banner reveal d1">
    <h2>Our Mission</h2>
    <p>To make premium, ethically-sourced human hair accessible and effortless — pairing flawless quality with a shopping experience that feels as elevated as the hair itself. No gatekeeping, no compromise. Just gorgeous, natural-looking hair that lasts for years.</p>
  </div>

  <div class="about-staggered reveal d2">
    <figure><img src="{{asset:img/about-2.jpg}}" alt="HD lace frontal wig install" loading="lazy" decoding="async"></figure>
    <figure><img src="{{asset:img/about-3.jpg}}" alt="Body wave bundles" loading="lazy" decoding="async"></figure>
  </div>

  <div class="about-features-wrap reveal">
    <div class="section-head text-center" style="margin-bottom: 40px;">
      <h2>The Omonblaq Standard</h2>
      <div class="divider-gold"></div>
    </div>
    <ul class="about-features-grid">
      <li class="about-glass-card"><strong>100% Virgin &amp; Raw Human Hair</strong><span>Cuticle-aligned, single-donor where stated, and built to last.</span></li>
      <li class="about-glass-card"><strong>Melt-Skin HD Lace</strong><span>Pre-plucked hairlines and bleached knots for an undetectable, glass-skin finish.</span></li>
      <li class="about-glass-card"><strong>For Every Queen</strong><span>Wigs, bundles, frontals, closures and raw hair for every skin tone and texture.</span></li>
      <li class="about-glass-card"><strong>A Worldwide Family</strong><span>Fast, tracked delivery with transparent pricing in CAD, USD &amp; NGN.</span></li>
    </ul>
  </div>

  <div class="about-conclusion reveal d1">
    <h2>Hair For Every Woman</h2>
    <p>Whether you're a first-timer reaching for a glueless ready-to-wear unit or a connoisseur investing in raw Vietnamese straight, our stylists — and our AI stylist, Omon — help you choose the perfect hair for your budget, your look and your occasion. Beauty has no limits here.</p>
    
    <div class="about-media" style="max-width:800px; margin:40px auto; aspect-ratio: 16/9;">
      <img src="{{asset:img/about-4.jpg}}" alt="Omonblaq's Hair customer wearing a luxury wig" loading="lazy" decoding="async">
    </div>

    <div class="about-stats">
      <div class="about-stat"><b>10k+</b><span>Queens Served</span></div>
      <div class="about-stat"><b>4.9★</b><span>Average Rating</span></div>
      <div class="about-stat"><b>5</b><span>Continents Shipped</span></div>
      <div class="about-stat"><b>100%</b><span>Human Hair</span></div>
    </div>

    <p class="prose-cta mt-4"><a class="btn btn-gold btn-lg" href="{{url:shop.php}}" style="color: black;">Shop The Collection</a></p>
  </div>
</div>
HTML;

$cover = in_array('about-1.jpg', $saved, true) ? 'about-1.jpg' : 'about.jpg';
q('UPDATE pages SET body = ?, cover_image = ?, eyebrow = ?, title = ? WHERE slug = ?',
  [$body, $cover, 'The Omonblaq Story', 'Crowned In Luxury', 'about']);

echo "About page updated (cover: $cover). Done.\n";
