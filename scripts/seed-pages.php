<?php
/** Seed CMS pages. Run: php scripts/seed-pages.php  (idempotent — inserts only if missing) */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pages = [
  [
    'slug' => 'about', 'title' => 'Crowned In Luxury', 'eyebrow' => 'The Omonblaq Story',
    'cover_image' => 'about.jpg',
    'body' => <<<HTML
<p><strong>Omonblaq's Hair</strong> was founded on one belief: every woman deserves to feel like royalty. From our home in Brampton, Ontario, we curate the finest wigs, bundles, frontals, closures and raw hair — and ship them with love to queens across Canada, Nigeria and the world.</p>
<p>Our brand is for everyone. Our hair is for everyone — every skin tone, every texture, every crown. Whether you want a melt-skin HD lace unit, soft virgin bundles or single-donor raw hair that lasts for years, we have something made for you.</p>
<h2>The Omonblaq Standard</h2>
<ul>
  <li><strong>Uncompromising quality</strong> — only premium virgin &amp; single-donor raw hair makes it into our collection.</li>
  <li><strong>For every queen</strong> — hair and shades for every skin tone, texture and budget.</li>
  <li><strong>Worldwide family</strong> — trusted in Canada &amp; Nigeria, shipping luxury everywhere.</li>
</ul>
<p>Thank you for letting us be part of your glow-up. Welcome to the family. 👑</p>
HTML,
  ],
  [
    'slug' => 'shipping', 'title' => 'Shipping &amp; Returns', 'eyebrow' => 'Fast, Tracked, Worldwide', 'cover_image' => null,
    'body' => <<<HTML
<h2>Worldwide Shipping</h2>
<p>We ship to Canada, Nigeria, the US, UK and beyond with tracked delivery. Canadian orders typically arrive in 2–5 business days; international orders in 5–12 business days.</p>
<h2>Free Shipping</h2>
<p>Enjoy free shipping on all orders over the free-shipping threshold shown at checkout. Orders below that ship at a flat rate.</p>
<h2>Order Processing</h2>
<p>Orders are processed within 1–2 business days. You'll receive a tracking number by email as soon as your hair ships.</p>
<h2>Returns &amp; Exchanges</h2>
<p>Unopened, unworn hair in its original packaging can be returned within 14 days. For hygiene reasons, installed or altered hair cannot be returned. Contact us and we'll make it right.</p>
<h2>Secure Payments</h2>
<p>All payments are processed securely via Stripe with SSL encryption. We never store your card details.</p>
HTML,
  ],
  [
    'slug' => 'faq', 'title' => 'Frequently Asked Questions', 'eyebrow' => 'Everything You Need To Know', 'cover_image' => null,
    'body' => <<<HTML
<details><summary>What is the difference between virgin and raw hair?</summary><p>Virgin hair is unprocessed hair collected from multiple donors. Raw hair is single-donor and completely unprocessed — the most durable, natural and luxurious hair available, lasting 3+ years with care.</p></details>
<details><summary>How many bundles do I need for a full install?</summary><p>For lengths up to 18", three bundles are usually enough. For 20" and longer, we recommend four bundles for full, even fullness. Pair with a matching frontal or closure.</p></details>
<details><summary>Can I dye or bleach the hair?</summary><p>Yes! Our virgin and raw hair can be coloured and lightened by a professional stylist. Natural Black 1B units take colour beautifully up to honey blonde #27.</p></details>
<details><summary>Is the lace really undetectable?</summary><p>Our HD lace melts into every skin tone. Tint the lace to match your complexion and pluck lightly for the most natural, invisible hairline.</p></details>
<details><summary>Do you ship to Nigeria?</summary><p>Absolutely. We ship across Nigeria and Canada (and worldwide) with tracked delivery, and you can shop in NGN, CAD or USD.</p></details>
<details><summary>How do I care for my hair?</summary><p>Wash gently with sulfate-free shampoo, deep condition weekly, air-dry when possible and store on a wig stand. Our Hair Care collection has everything you need.</p></details>
<details><summary>What payment methods do you accept?</summary><p>We accept all major cards securely through Stripe. You can also order via WhatsApp for assisted checkout.</p></details>
HTML,
  ],
  [
    'slug' => 'privacy', 'title' => 'Privacy Policy', 'eyebrow' => 'Your Trust Matters', 'cover_image' => null,
    'body' => <<<HTML
<p><em>Last updated: June 2026.</em></p>
<p>Omonblaq's Hair ("we", "us") respects your privacy. This policy explains what we collect and how we use it.</p>
<h2>Information We Collect</h2>
<p>When you place an order or contact us, we collect your name, email, phone number, shipping address and order details. Payment card details are processed securely by Stripe and are never stored on our servers.</p>
<h2>How We Use Your Information</h2>
<ul>
  <li>To process and deliver your orders and send order updates.</li>
  <li>To respond to your enquiries and provide customer support.</li>
  <li>To send marketing emails <em>only</em> if you opt in (you can unsubscribe anytime).</li>
</ul>
<h2>Sharing</h2>
<p>We share information only with the partners needed to fulfil your order (e.g. payment processor, shipping carriers). We never sell your data.</p>
<h2>Your Rights</h2>
<p>You may request access to, correction of, or deletion of your personal data by emailing <a href="mailto:info@omonblaqshair.com">info@omonblaqshair.com</a>.</p>
<h2>Cookies</h2>
<p>We use essential cookies to keep your cart and preferences (such as currency) working. Disabling them may affect site functionality.</p>
<h2>Contact</h2>
<p>Questions about this policy? Email <a href="mailto:info@omonblaqshair.com">info@omonblaqshair.com</a>.</p>
HTML,
  ],
  [
    'slug' => 'terms', 'title' => 'Terms &amp; Conditions', 'eyebrow' => 'The Fine Print', 'cover_image' => null,
    'body' => <<<HTML
<p><em>Last updated: June 2026.</em></p>
<p>By using this website and placing an order with Omonblaq's Hair, you agree to the following terms.</p>
<h2>Products</h2>
<p>We make every effort to display our products and colours accurately. Because hair is a natural product, slight variations in texture and shade may occur.</p>
<h2>Pricing &amp; Payment</h2>
<p>Prices are shown in your selected currency (CAD, USD or NGN) and may be converted at checkout. All orders are subject to availability and payment confirmation.</p>
<h2>Shipping</h2>
<p>Delivery times are estimates and not guaranteed. We are not responsible for carrier delays or customs charges, which are the responsibility of the customer.</p>
<h2>Returns</h2>
<p>Please review our <a href="/shipping">Shipping &amp; Returns</a> policy. For hygiene reasons, installed or altered hair cannot be returned.</p>
<h2>Limitation of Liability</h2>
<p>Our liability is limited to the value of the products purchased. We are not liable for indirect or consequential losses.</p>
<h2>Contact</h2>
<p>For any questions about these terms, email <a href="mailto:info@omonblaqshair.com">info@omonblaqshair.com</a>.</p>
HTML,
  ],
];

$ins = db()->prepare('INSERT INTO pages (slug,title,eyebrow,cover_image,body,is_published) VALUES (?,?,?,?,?,1)
                      ON DUPLICATE KEY UPDATE title=VALUES(title)');
$exists = db()->prepare('SELECT id FROM pages WHERE slug = ?');
foreach ($pages as $p) {
    $exists->execute([$p['slug']]);
    if ($exists->fetch()) { echo "  = {$p['slug']} (exists, skipped)\n"; continue; }
    $ins->execute([$p['slug'], $p['title'], $p['eyebrow'], $p['cover_image'], $p['body']]);
    echo "  + {$p['slug']}\n";
}
echo "Done seeding pages.\n";
