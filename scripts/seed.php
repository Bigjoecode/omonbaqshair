<?php
/**
 * Seed Omonblaq's Hair catalog. Run:  php scripts/seed.php
 * Idempotent: truncates catalog tables and re-inserts.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
echo "Seeding Omonblaq's Hair...\n";

// Available product photos
$imgDir = ROOT_PATH . '/assets/uploads/products';
$pool = [];
foreach (glob($imgDir . '/hair_*.jpg') ?: [] as $f) $pool[] = basename($f);
sort($pool);
if (!$pool) $pool[] = ''; // fall back to placeholder
$pi = 0;
$nextImg = function () use (&$pi, $pool) { return $pool[$pi++ % count($pool)]; };

// Reset catalog
foreach (['product_images','products','categories','testimonials','reviews','blog_posts','coupons'] as $t) {
    $pdo->exec("DELETE FROM `$t`");
    $pdo->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
}

/* -------------------- Categories -------------------- */
$categories = [
    ['Wigs',         'wigs',         'Glueless, HD lace & ready-to-wear units', 'cat-wigs.jpg',     1, 1],
    ['Bundles',      'bundles',      'Virgin weave bundles by the inch',         'cat-bundles.jpg',  2, 1],
    ['Frontals',     'frontals',     '13x4 & 13x6 HD lace frontals',             'cat-frontals.jpg', 3, 1],
    ['Closures',     'closures',     '4x4 & 5x5 lace closures',                  'cat-frontals.jpg', 4, 1],
    ['Raw Hair',     'raw-hair',     'Single-donor raw, unprocessed luxury',     'cat-raw.jpg',      5, 1],
    ['Bundle Deals', 'bundle-deals', 'Bundles + closure/frontal sets, save more','cat-bundles.jpg',  6, 1],
    ['Hair Care',    'hair-care',    'Maintain, protect & grow your hair',       'cat-raw.jpg',      7, 0],
    ['Accessories',  'accessories',  'Caps, glue, edge control & tools',         'cat-wigs.jpg',     8, 0],
];
$catId = [];
$cs = $pdo->prepare('INSERT INTO categories (name,slug,description,image,sort_order,is_featured,is_active) VALUES (?,?,?,?,?,?,1)');
foreach ($categories as $c) {
    $cs->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5]]);
    $catId[$c[1]] = (int)$pdo->lastInsertId();
}
echo "  + " . count($categories) . " categories\n";

/* -------------------- Products -------------------- */
// [name, cat, price, sale, origin, texture, lengths, lace, density, color, badges(feat,best,new), short, desc]
$P = [
  // Wigs
  ['Imperial HD Lace Frontal Wig','wigs',389,329,'Brazilian','Body Wave','16",18",20",22",24"','13x4 HD Lace','180%','Natural Black 1B',[1,1,0],
   'Pre-plucked 13x4 HD lace frontal wig with a melt-into-skin hairline.',
   'Our signature unit. Hand-tied 13x4 HD lace that disappears on every skin tone, pre-plucked hairline with baby hairs, bleached knots and a 180% density for full, bouncy body-wave movement. Glueless install with adjustable straps and combs.'],
  ['Glueless 5x5 Closure Wig — Silky Straight','wigs',329,279,'Peruvian','Straight','14",16",18",20",22"','5x5 HD Closure','180%','Natural Black 1B',[1,0,1],
   'Beginner-friendly glueless wig you can install in minutes.',
   'No glue, no stress. This 5x5 HD closure wig comes ready to wear with elastic band and combs — perfect for first-time wig wearers who still want that luxury, undetectable finish.'],
  ['Royal Curly Bob Lace Wig','wigs',249,219,'Brazilian','Deep Wave','10",12",14"','13x4 Transparent','200%','Natural Black 1B',[0,1,0],
   'Flirty, full curly bob with a transparent lace front.',
   'A statement bob with 200% density for maximum fullness and defined deep-wave curls. Lightweight, breathable and effortlessly chic for the office or a night out.'],
  ['Honey Blonde Ombré Lace Wig #4/27','wigs',459,null,'Brazilian','Body Wave','18",20",22",24"','13x4 HD Lace','200%','Ombré #4/27',[1,0,1],
   'Hand-painted honey-blonde ombré on a luxury HD unit.',
   'Sun-kissed #4/27 ombré, expertly hand-painted on virgin hair so the colour stays rich and the strands stay healthy. A head-turner with full 200% density.'],
  // Bundles
  ['Virgin Body Wave Bundle','bundles',129,109,'Brazilian','Body Wave','12",14",16",18",20",22",24",26"','-','-','Natural Black 1B',[0,1,0],
   'Soft, bouncy body-wave bundle — single donor, double weft.',
   'Cuticle-aligned virgin body wave with a secure double weft that lays flat with no shedding. Dye-friendly up to #27. Sold per bundle — grab 3 for a full install.'],
  ['Silky Straight Virgin Bundle','bundles',119,null,'Peruvian','Straight','12",14",16",18",20",22",24"','-','-','Natural Black 1B',[0,1,0],
   'Sleek, tangle-free straight bundle with natural luster.',
   'Bone-straight virgin hair that flat-irons to glass and reverts beautifully after washing. Minimal shedding, full from root to tip.'],
  ['Deep Wave Virgin Bundle','bundles',135,null,'Brazilian','Deep Wave','12",14",16",18",20",22",24",26"','-','-','Natural Black 1B',[0,0,1],
   'Defined, juicy deep-wave curls that hold their pattern.',
   'Rich deep-wave texture that stays defined wash after wash. Pair with our matching deep-wave frontal for a seamless install.'],
  ['Kinky Curly Virgin Bundle','bundles',139,119,'Brazilian','Kinky Curly','12",14",16",18",20",22"','-','-','Natural Black 1B',[0,0,0],
   'Big, natural-looking kinky curls that blend with 4C hair.',
   'For the natural-texture lover. Blends seamlessly with relaxed-stretched and natural 4C hair for a flawless leave-out.'],
  // Frontals
  ['13x4 HD Lace Frontal — Body Wave','frontals',169,149,'Brazilian','Body Wave','16",18",20",22"','13x4 HD Lace','-','Natural Black 1B',[1,0,0],
   'Ear-to-ear 13x4 HD frontal, pre-plucked & bleached knots.',
   'Melt-skin HD lace from ear to ear with a natural-density hairline and baby hairs already laid. Free, middle or side part.'],
  ['13x6 HD Lace Frontal — Straight','frontals',199,179,'Peruvian','Straight','16",18",20",22"','13x6 HD Lace','-','Natural Black 1B',[0,0,1],
   'Deeper 13x6 parting space for limitless styling.',
   'The deep 13x6 parting gives you more versatility for ponytails and deep side parts, on featherlight HD lace.'],
  // Closures
  ['4x4 HD Lace Closure — Body Wave','closures',99,85,'Brazilian','Body Wave','14",16",18",20"','4x4 HD Closure','-','Natural Black 1B',[0,1,0],
   'Versatile 4x4 HD closure for a quick, natural part.',
   'A everyday-easy 4x4 closure with pre-plucked hairline and bleached knots. Pairs with any of our body-wave bundles.'],
  ['5x5 HD Lace Closure — Deep Wave','closures',119,99,'Brazilian','Deep Wave','14",16",18",20"','5x5 HD Closure','-','Natural Black 1B',[0,0,0],
   'Wider 5x5 closure for more parting freedom.',
   'Extra parting space with a seamless HD lace base. Matches our deep-wave bundles for a flawless finish.'],
  // Raw hair
  ['Raw Vietnamese Straight — Single Donor','raw-hair',229,199,'Vietnamese','Straight','14",16",18",20",22",24",26",28"','-','-','Natural 1B',[1,1,0],
   'Unprocessed single-donor raw hair — wears for years.',
   'The pinnacle of luxury. 100% unprocessed, single-donor raw Vietnamese hair with intact cuticles. Naturally thick from root to tip, colours like a dream and lasts 3+ years with care.'],
  ['Raw Indian Wavy — Temple Hair','raw-hair',239,null,'Raw Indian','Natural Wave','14",16",18",20",22",24",26"','-','-','Natural 1B',[0,0,1],
   'Authentic raw Indian temple hair with a soft natural wave.',
   'Ethically sourced raw Indian temple hair with its own gorgeous natural wave. Steam-processed only — never chemically altered.'],
  ['Raw Burmese Curly','raw-hair',259,229,'Raw Burmese','Curly','14",16",18",20",22"','-','-','Natural 1B',[0,0,0],
   'Bouncy raw Burmese curls with incredible longevity.',
   'Rare raw Burmese curly hair with a springy, defined curl pattern that reverts perfectly after every wash.'],
  // Bundle deals
  ['Body Wave Deal — 3 Bundles + 13x4 Frontal','bundle-deals',489,429,'Brazilian','Body Wave','Up to 24"','13x4 HD Lace','-','Natural Black 1B',[1,1,0],
   'A complete install in one click — and you save.',
   'Three matched virgin body-wave bundles plus a 13x4 HD frontal, colour-matched and ready for a full sew-in. Everything you need for a flawless install in one luxury set.'],
  ['Straight Deal — 3 Bundles + 4x4 Closure','bundle-deals',389,349,'Peruvian','Straight','Up to 22"','4x4 HD Closure','-','Natural Black 1B',[0,1,0],
   'Three silky straight bundles + a matching 4x4 closure.',
   'The easiest way to a full, natural install. Three colour-matched straight bundles with a 4x4 HD closure — luxury made simple.'],
  ['Deep Wave Deal — 4 Bundles + 13x6 Frontal','bundle-deals',579,519,'Brazilian','Deep Wave','Up to 26"','13x6 HD Lace','-','Natural Black 1B',[0,0,1],
   'Extra-full 4-bundle deep wave set with a 13x6 frontal.',
   'For longer lengths and maximum fullness — four deep-wave bundles and a deep-parting 13x6 HD frontal.'],
  // Hair care
  ['Crown Growth Oil — Rosemary & Castor','hair-care',32,27,'-','-','60ml','-','-','-',[0,1,0],
   'Scalp-loving growth oil for edges and length.',
   'A nourishing blend of rosemary, castor and biotin oils to feed your scalp, strengthen edges and support healthy growth. Lightweight, non-greasy.'],
  ['Sulfate-Free Hydrating Shampoo','hair-care',24,null,'-','-','300ml','-','-','-',[0,0,0],
   'Gentle cleanse that protects your install & natural hair.',
   'A sulfate-free, colour-safe shampoo that cleanses without stripping — perfect for wigs, bundles and your natural hair underneath.'],
  ['Deep Repair Conditioning Mask','hair-care',29,24,'-','-','250ml','-','-','-',[0,0,1],
   'Weekly mask that restores softness and shine.',
   'Intensive moisture for virgin and raw hair. Detangles, smooths and brings dull strands back to life in 5 minutes.'],
  ['Edge Control — Strong Hold, No Flake','hair-care',18,null,'-','-','100g','-','-','-',[0,0,0],
   'All-day sleek edges with a non-greasy, flake-free hold.',
   'Lay your edges and keep them laid. Strong, long-lasting hold that never flakes or leaves white residue.'],
  // Accessories
  ['HD Lace Melt Wig Glue','accessories',22,null,'-','-','38g','-','-','-',[0,0,0],
   'Waterproof, skin-safe lace adhesive for a secure melt.',
   'Strong, waterproof, latex-free wig glue for a flawless, long-lasting melt that survives heat and humidity.'],
  ['Pre-Cut Elastic Wig Cap (3-Pack)','accessories',14,null,'-','-','One Size','-','-','-',[0,0,0],
   'Breathable nude wig caps that grip without slipping.',
   'A 3-pack of breathable, stretchy wig caps in a universal nude tone to lay your hair flat for the perfect install.'],
  ['Lace Tint Mousse — Melt Spray','accessories',19,16,'-','-','120ml','-','-','-',[0,0,1],
   'Tint your lace to match any skin tone in seconds.',
   'A buildable tint mousse that customises your HD lace to your exact complexion for a truly undetectable hairline.'],
];

$ps = $pdo->prepare('INSERT INTO products
 (category_id,name,slug,sku,short_desc,description,price,sale_price,stock,origin,texture,length_inches,lace_type,density,color_shade,rating,review_count,is_featured,is_bestseller,is_new,badge,is_active)
 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)');
$imgStmt = $pdo->prepare('INSERT INTO product_images (product_id,image,is_primary,sort_order) VALUES (?,?,?,?)');

$skuN = 1000;
foreach ($P as $p) {
    [$name,$cat,$price,$sale,$origin,$texture,$lengths,$lace,$density,$color,$flags,$short,$desc] = $p;
    $slug = slugify($name);
    $sku  = 'OMB-' . (++$skuN);
    $rating = round(4.6 + (mt_rand(0, 4) / 10), 1); if ($rating > 5) $rating = 5.0;
    $reviews = mt_rand(8, 96);
    $badge = $sale ? 'Sale' : ($flags[2] ? 'New' : ($flags[1] ? 'Best Seller' : null));
    $ps->execute([
        $catId[$cat], $name, $slug, $sku, $short, $desc, $price, $sale, mt_rand(12, 60),
        ($origin === '-' ? null : $origin), ($texture === '-' ? null : $texture),
        ($lengths === '-' ? null : $lengths), ($lace === '-' ? null : $lace),
        ($density === '-' ? null : $density), ($color === '-' ? null : $color),
        $rating, $reviews, $flags[0], $flags[1], $flags[2], $badge,
    ]);
    $id = (int)$pdo->lastInsertId();
    // two images per product
    $imgStmt->execute([$id, $nextImg(), 1, 0]);
    $imgStmt->execute([$id, $nextImg(), 0, 1]);
}
echo "  + " . count($P) . " products\n";

/* -------------------- Reviews -------------------- */
$reviewBank = [
    ['Amara O.', 5, 'Obsessed!', 'The HD lace truly melts. Got so many compliments — felt like a queen 👑'],
    ['Chioma N.', 5, 'Worth every penny', 'Shipped fast to Lagos, hair is thick from top to bottom. No shedding!'],
    ['Tasha B.', 5, 'My new go-to', 'Ordered from Toronto, arrived in 2 days. The quality is unmatched.'],
    ['Funke A.', 4, 'Beautiful hair', 'Lovely and soft. Took one star only because I wanted it longer — ordering 26" next!'],
    ['Destiny M.', 5, 'Beginner friendly', 'First time wearing a wig and I installed it myself. So easy and natural.'],
    ['Blessing E.', 5, 'Luxury for real', 'You can feel the difference. This is raw hair done right.'],
];
$rv = $pdo->prepare('INSERT INTO reviews (product_id,author,rating,title,body,is_approved) VALUES (?,?,?,?,?,1)');
$allProducts = $pdo->query('SELECT id FROM products')->fetchAll(PDO::FETCH_COLUMN);
foreach ($allProducts as $pid) {
    $n = mt_rand(2, 4);
    for ($i = 0; $i < $n; $i++) {
        $r = $reviewBank[array_rand($reviewBank)];
        $rv->execute([$pid, $r[0], $r[1], $r[2], $r[3]]);
    }
}
echo "  + reviews\n";

/* -------------------- Testimonials -------------------- */
$testi = [
    ['Adaeze I.', 'Lagos, Nigeria', 5, 'Omonblaq\'s is the only place I buy hair now. The body wave is soft, full and lasts me over a year. Customer service feels like family.'],
    ['Jasmine R.', 'Brampton, Canada', 5, 'That HD frontal melted so well my coworkers swore it was my real hair. Fast Canadian shipping too. 10/10.'],
    ['Ngozi U.', 'Abuja, Nigeria', 5, 'I was nervous ordering hair online but the AI stylist helped me pick the perfect bundles for my budget. Absolutely gorgeous.'],
];
$ts = $pdo->prepare('INSERT INTO testimonials (author,location,rating,body,sort_order,is_active) VALUES (?,?,?,?,?,1)');
foreach ($testi as $i => $t) $ts->execute([$t[0], $t[1], $t[2], $t[3], $i]);
echo "  + testimonials\n";

/* -------------------- Blog -------------------- */
$posts = [
    ['How to Make Your HD Lace Melt Like Skin','blog-1.jpg',
     'Five styling-room secrets for a truly undetectable hairline — from tinting to laying baby hairs.',
     '<p>A flawless melt is part product, part technique. Start by tinting your HD lace to match your complexion...</p><p>Next, pluck for a natural density, lay your baby hairs in small sections, and seal with a strong-hold edge control.</p>'],
    ['Bundles 101: How Many Do You Really Need?','blog-2.jpg',
     'A simple guide to choosing bundle lengths and counts for the fullness you want.',
     '<p>For lengths up to 18", three bundles usually give a full install. Going 20" and beyond? Add a fourth...</p>'],
    ['Raw vs. Virgin Hair: What\'s the Difference?','blog-3.jpg',
     'Understand the luxury tiers so you can invest in hair that lasts for years.',
     '<p>Virgin hair is unprocessed but collected from multiple donors. Raw hair is single-donor and completely unprocessed — the longest-lasting hair money can buy.</p>'],
];
$bs = $pdo->prepare('INSERT INTO blog_posts (title,slug,excerpt,body,cover_image,author,is_published,published_at) VALUES (?,?,?,?,?,?,1,NOW())');
foreach ($posts as $b) $bs->execute([$b[0], slugify($b[0]), $b[2], $b[3], $b[1], 'Omonblaq Team']);
echo "  + blog posts\n";

/* -------------------- Coupons -------------------- */
$pdo->prepare('INSERT INTO coupons (code,type,value,min_subtotal,is_active) VALUES (?,?,?,?,1)')
    ->execute(['WELCOME10', 'percent', 10, 0]);
$pdo->prepare('INSERT INTO coupons (code,type,value,min_subtotal,is_active) VALUES (?,?,?,?,1)')
    ->execute(['ROYAL20', 'percent', 20, 300]);
echo "  + coupons\n";

/* -------------------- Settings -------------------- */
$settings = [
    'free_shipping_threshold' => '150',
    'shipping_flat_cad'       => '15',
    'hero_eyebrow'            => 'Because you deserve hair that matches your radiance',
    'hero_title'              => 'Home of luxury hairs & more',
    'hero_subtitle'           => 'Luxury wigs, bundles & raw hair for every queen — every skin tone, every texture, every crown.',
    'announcement'            => 'Free shipping over $150',
    'social_instagram'        => SOCIAL_INSTAGRAM,
    'social_facebook'         => SOCIAL_FACEBOOK,
    'social_tiktok'           => SOCIAL_TIKTOK,
];
$set = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
foreach ($settings as $k => $v) $set->execute([$k, $v]);
echo "  + settings\n";

/* -------------------- Admin user --------------------
 * Set the initial admin password via env: ADMIN_EMAIL / ADMIN_PASSWORD.
 * Change it immediately under Admin → Settings after first login. */
$adminEmail = getenv('ADMIN_EMAIL') ?: 'info@omonblaqshair.com';
$adminPass  = getenv('ADMIN_PASSWORD') ?: 'ChangeMe!' . date('Y');
$exists = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
$exists->execute([$adminEmail]);
if (!$exists->fetch()) {
    $pdo->prepare('INSERT INTO admin_users (name,email,password_hash,role) VALUES (?,?,?,?)')
        ->execute(['Omonblaq Admin', $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), 'admin']);
    echo "  + admin user ($adminEmail / $adminPass)  — change this password after first login!\n";
}

echo "Done. Catalog seeded successfully.\n";
