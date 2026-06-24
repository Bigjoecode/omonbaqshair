<?php
/**
 * Omon — AI Stylist endpoint.
 * Uses Anthropic Claude if ANTHROPIC_API_KEY is set; otherwise a smart,
 * product-aware rule-based stylist that recommends real catalogue items.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$message = trim($body['message'] ?? '');
if ($message === '') { echo json_encode(['reply' => 'Tell me what look you\'re going for 💛']); exit; }

$sid = $_SESSION['chat_sid'] ?? ($_SESSION['chat_sid'] = bin2hex(random_bytes(8)));
try { q('INSERT INTO chat_logs (session_id, role, message) VALUES (?,?,?)', [$sid, 'user', $message]); } catch (Throwable $e) {}

/* --- Build product context --- */
$catalog = fetchAll(
    "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.texture, p.origin, p.stock, c.name AS cat
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 ORDER BY p.is_bestseller DESC, p.rating DESC"
);

function product_link(array $p): string {
    $price = money((float)($p['sale_price'] ?: $p['price']));
    return '<a href="' . url('product.php?slug=' . urlencode($p['slug'])) . '" style="color:var(--gold);text-decoration:underline">'
         . e($p['name']) . '</a> — ' . $price;
}

// ---- Product matching (powers in-chat "Add to Bag" suggestions for both Claude & fallback) ----
$m = strtolower($message);
$budget = null;
if (preg_match('/(\d[\d,]{1,7})/', str_replace([' ', '$', '₦'], '', $m), $mm)) {
    $budget = (float)str_replace(',', '', $mm[1]);
    $rate = $GLOBALS['CURRENCIES'][current_currency()]['rate'] ?? 1;
    if ($rate > 0) $budget = $budget / $rate;
}
$catMap = ['wig'=>'Wigs','bundle deal'=>'Bundle Deals','bundle'=>'Bundles','frontal'=>'Frontals','closure'=>'Closures','raw'=>'Raw Hair','care'=>'Hair Care','oil'=>'Hair Care','shampoo'=>'Hair Care'];
$wantCat = null;
foreach ($catMap as $kw => $cat) { if (str_contains($m, $kw)) { $wantCat = $cat; break; } }
$texturesKw = ['straight','body wave','deep wave','kinky','curly','wavy'];
$wantTex = null;
foreach ($texturesKw as $t) { if (str_contains($m, $t)) { $wantTex = $t; break; } }
$matches = array_filter($catalog, function ($p) use ($wantCat, $wantTex, $budget) {
    if ($wantCat && $p['cat'] !== $wantCat) return false;
    if ($wantTex && stripos((string)$p['texture'], $wantTex) === false) return false;
    if ($budget && (float)($p['sale_price'] ?: $p['price']) > $budget) return false;
    return true;
});
if (!$matches) $matches = $catalog;
$matches = array_slice(array_values($matches), 0, 3);

$isGreeting = ($m === 'hi' || str_contains($m, 'hello') || str_contains($m, 'hey'));
$isShipping = (str_contains($m, 'ship') || str_contains($m, 'deliver'));
$productIntent = !$isGreeting && !$isShipping &&
    ($budget !== null || $wantCat || $wantTex ||
     (bool)preg_match('/recommend|show|best|under|look|suggest|budget|cheap|afford|need|want|buy|hair|wig|bundle|frontal|closure|raw|curl|wave|straight/', $m));
$suggested = $productIntent ? $matches : [];

$reply = '';

if (ANTHROPIC_API_KEY) {
    // ---- Claude-powered ----
    $lines = array_map(fn($p) => "- {$p['name']} ({$p['cat']}, {$p['texture']}, {$p['origin']}): " . money((float)($p['sale_price'] ?: $p['price'])) . " [/product.php?slug={$p['slug']}]", array_slice($catalog, 0, 40));
    $system = "You are Omon, the warm, expert AI hair stylist for Omonblaq's Hair (luxury wigs, bundles, frontals, closures, raw hair). "
        . "Help customers choose hair for their budget, occasion and texture. Be concise (2-4 sentences), friendly and a little glam (occasional emoji). "
        . "Recommend specific products from this catalogue and include their link as an HTML anchor when relevant. Prices shown in " . current_currency() . ".\n\nCATALOGUE:\n" . implode("\n", $lines);

    $payload = json_encode([
        'model' => ANTHROPIC_MODEL,
        'max_tokens' => 400,
        'system' => $system,
        'messages' => [['role' => 'user', 'content' => $message]],
    ]);
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true) ?: [];
    $reply = $data['content'][0]['text'] ?? '';
}

if ($reply === '') {
    // ---- Smart rule-based fallback (detection done above) ----
    if ($isGreeting) {
        $reply = "Hey gorgeous 👑 I'd love to help you find your perfect hair. Are you after a <b>wig</b>, <b>bundles</b>, a <b>frontal</b> or <b>raw hair</b> — and what's your budget?";
    } elseif ($isShipping) {
        $reply = "We ship worldwide 🌍 — fast tracked delivery to Canada, Nigeria, the US and beyond. Orders over " . money(150) . " ship free! Where are you based?";
    } elseif (str_contains($m, 'beginner') || str_contains($m, 'first time') || str_contains($m, 'easy')) {
        $glueless = array_values(array_filter($catalog, fn($p) => stripos($p['name'], 'glueless') !== false || stripos($p['name'], 'closure wig') !== false));
        $pick = $glueless[0] ?? $matches[0];
        $suggested = [$pick];
        $reply = "Perfect for a first-timer 💛 I'd go for our " . product_link($pick) . ". It's glueless and installs in minutes — no stress, just a flawless, natural finish.";
    } else {
        $intro = $budget ? "Here's what I love within your budget:" : ($wantCat ? "Beautiful choice — here are my top {$wantCat} picks:" : "Here are a few stunning options I'd recommend:");
        $list = implode('<br>• ', array_map('product_link', $matches));

        // Vary the follow-up so Omon doesn't repeat itself, and keep it contextual.
        $followups = [];
        if ($wantCat === 'Wigs' || $wantCat === null) {
            $followups[] = "Prefer it glueless or are you comfortable installing? I can narrow it down. 💛";
        }
        $followups[] = "What length and texture are you leaning toward? 👑";
        $followups[] = "Tell me your budget and I'll find the perfect match. ✨";
        $followups[] = "Shall I help you check out, or show you a few more? 💁🏾‍♀️";
        $followup = $followups[array_rand($followups)];

        $reply = $intro . "<br><br>• " . $list . "<br><br>" . $followup;
    }
}

try { q('INSERT INTO chat_logs (session_id, role, message) VALUES (?,?,?)', [$sid, 'bot', $reply]); } catch (Throwable $e) {}

$products = array_map(function ($p) {
    return [
        'id'    => (int)$p['id'],
        'name'  => $p['name'],
        'price' => money((float)($p['sale_price'] ?: $p['price'])),
        'url'   => url('product.php?slug=' . urlencode($p['slug'])),
        'image' => product_image((int)$p['id']),
        'in_stock' => (int)($p['stock'] ?? 1) > 0,
    ];
}, $suggested ?? []);

echo json_encode(['reply' => $reply, 'products' => $products]);
