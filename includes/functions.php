<?php
/**
 * Shared helpers for Omonblaq's Hair.
 */
declare(strict_types=1);

/* ----------------------------------------------------------------------------
 * Output / escaping
 * ------------------------------------------------------------------------- */
function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if ($path !== '') {
        // Preserve any query string / fragment, strip the .php extension for clean URLs.
        $suffix = '';
        if (($pos = strcspn($path, '?#')) < strlen($path)) {
            $suffix = substr($path, $pos);
            $path   = substr($path, 0, $pos);
        }
        if (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }
        if ($path === 'index') {
            $path = '';
        }
        $path .= $suffix;
    }
    return rtrim(BASE_URL, '/') . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Resolve a stored image reference (cover image, body image, etc.) to a URL.
 * Accepts: a full http(s) URL, a path like "img/x.jpg" / "uploads/pages/x.jpg",
 * or a bare filename (searched across the upload dirs and img/).
 */
function media_url(?string $ref): ?string
{
    $ref = trim((string)$ref);
    if ($ref === '') return null;
    if (preg_match('~^https?://~i', $ref)) return $ref;
    $ref = ltrim($ref, '/');
    if (str_contains($ref, '/')) {
        $rel = str_starts_with($ref, 'assets/') ? $ref : 'assets/' . $ref;
        return url($rel);
    }
    // bare filename — find where it actually lives
    foreach (['assets/uploads/pages/', 'assets/uploads/categories/', 'assets/uploads/products/', 'assets/img/'] as $dir) {
        if (is_file(ROOT_PATH . '/' . $dir . $ref)) return url($dir . $ref);
    }
    return url('assets/img/' . $ref);
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    // Transliterate to ASCII when the (optional) iconv extension is available.
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) $text = $converted;
    }
    $text = strtolower(preg_replace('~[^-\w]+~', '', $text));
    return $text ?: 'item';
}

/* ----------------------------------------------------------------------------
 * Currency
 * ------------------------------------------------------------------------- */
function current_currency(): string
{
    $cur = $_COOKIE['currency'] ?? ($_SESSION['currency'] ?? BASE_CURRENCY);
    return isset($GLOBALS['CURRENCIES'][$cur]) ? $cur : BASE_CURRENCY;
}

function convert_price(float $cadAmount, ?string $currency = null): float
{
    $currency = $currency ?: current_currency();
    $rate = $GLOBALS['CURRENCIES'][$currency]['rate'] ?? 1.0;
    return $cadAmount * $rate;
}

function money(float $cadAmount, ?string $currency = null): string
{
    $currency = $currency ?: current_currency();
    $c = $GLOBALS['CURRENCIES'][$currency];
    $value = convert_price($cadAmount, $currency);
    // NGN displayed without decimals; others with 2.
    if ($currency === 'NGN') {
        return $c['symbol'] . number_format(round($value), 0);
    }
    return $c['symbol'] . number_format($value, 2);
}

/* ----------------------------------------------------------------------------
 * Database convenience
 * ------------------------------------------------------------------------- */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetch(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row ?: null;
}

function fetchAll(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/* ----------------------------------------------------------------------------
 * Settings (key/value table)
 * ------------------------------------------------------------------------- */
/** Social URL from settings, falling back to the config constant. */
function social_link(string $key, string $default = ''): string
{
    $val = setting('social_' . $key, '');
    return $val !== '' ? $val : $default;
}

function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (fetchAll('SELECT `key`, `value` FROM settings') as $r) {
                $cache[$r['key']] = $r['value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

/* ----------------------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

/* ----------------------------------------------------------------------------
 * Cart (session based)
 * ------------------------------------------------------------------------- */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart(), 'qty'));
}

function cart_add(int $productId, int $qty = 1, ?string $variant = null): void
{
    $key = $productId . ':' . ($variant ?? '');
    $items = cart();
    if (isset($items[$key])) {
        $items[$key]['qty'] += $qty;
    } else {
        $items[$key] = ['product_id' => $productId, 'qty' => $qty, 'variant' => $variant];
    }
    $_SESSION['cart'] = $items;
}

function cart_set(string $key, int $qty): void
{
    $items = cart();
    if ($qty <= 0) {
        unset($items[$key]);
    } elseif (isset($items[$key])) {
        $items[$key]['qty'] = $qty;
    }
    $_SESSION['cart'] = $items;
}

function cart_remove(string $key): void
{
    $items = cart();
    unset($items[$key]);
    $_SESSION['cart'] = $items;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/**
 * Returns detailed cart line items joined with products + totals (in CAD base).
 */
function cart_detailed(): array
{
    $items = cart();
    if (!$items) {
        return ['lines' => [], 'subtotal' => 0.0, 'count' => 0];
    }
    $ids = array_column($items, 'product_id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $products = [];
    foreach (fetchAll("SELECT * FROM products WHERE id IN ($in)", array_values($ids)) as $p) {
        $products[$p['id']] = $p;
    }
    $lines = [];
    $subtotal = 0.0;
    foreach ($items as $key => $item) {
        $p = $products[$item['product_id']] ?? null;
        if (!$p) continue;
        $price = (float)($p['sale_price'] ?: $p['price']);
        $lineTotal = $price * $item['qty'];
        $subtotal += $lineTotal;
        $lines[] = [
            'key' => $key,
            'product' => $p,
            'qty' => $item['qty'],
            'variant' => $item['variant'],
            'price' => $price,
            'line_total' => $lineTotal,
            'image' => product_image($p['id']),
        ];
    }
    return ['lines' => $lines, 'subtotal' => $subtotal, 'count' => cart_count()];
}

/* ----------------------------------------------------------------------------
 * Products
 * ------------------------------------------------------------------------- */
function product_image(int $productId, bool $primaryOnly = true): string
{
    $row = fetch(
        'SELECT image FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1',
        [$productId]
    );
    if ($row && $row['image']) {
        $img = $row['image'];
        return str_starts_with($img, 'http') ? $img : asset('uploads/products/' . $img);
    }
    return asset('img/placeholder-product.svg');
}

function product_images(int $productId): array
{
    $rows = fetchAll(
        'SELECT image FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC',
        [$productId]
    );
    $out = [];
    foreach ($rows as $r) {
        $out[] = str_starts_with($r['image'], 'http') ? $r['image'] : asset('uploads/products/' . $r['image']);
    }
    return $out ?: [asset('img/placeholder-product.svg')];
}

function product_url(array $product): string
{
    return url('product.php?slug=' . urlencode($product['slug']));
}

function category_url(array $category): string
{
    return url('shop.php?category=' . urlencode($category['slug']));
}

/* ----------------------------------------------------------------------------
 * Misc
 * ------------------------------------------------------------------------- */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function order_number(): string
{
    return 'OMB-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

/* ----------------------------------------------------------------------------
 * Inventory
 * ------------------------------------------------------------------------- */
/**
 * Check the current cart against available stock.
 * Returns an array of human-readable problems (empty = all good).
 */
function cart_stock_problems(): array
{
    $problems = [];
    foreach (cart_detailed()['lines'] as $l) {
        $stock = (int)$l['product']['stock'];
        if ($stock <= 0) {
            $problems[] = $l['product']['name'] . ' is sold out.';
        } elseif ($l['qty'] > $stock) {
            $problems[] = 'Only ' . $stock . ' left of ' . $l['product']['name'] . ' (you have ' . $l['qty'] . ' in your bag).';
        }
    }
    return $problems;
}

/** Decrement product stock for a paid/confirmed order. Idempotent per order. */
function decrement_order_stock(int $orderId): void
{
    $order = fetch('SELECT id, stock_adjusted FROM orders WHERE id = ?', [$orderId]);
    if (!$order || (int)$order['stock_adjusted'] === 1) return;
    foreach (fetchAll('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$orderId]) as $it) {
        if ($it['product_id']) {
            q('UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?', [(int)$it['qty'], (int)$it['product_id']]);
        }
    }
    q('UPDATE orders SET stock_adjusted = 1 WHERE id = ?', [$orderId]);
}

/* ----------------------------------------------------------------------------
 * Omonblaq Royalty — loyalty points
 * ------------------------------------------------------------------------- */
function royalty_points(string $email): int
{
    $email = strtolower(trim($email));
    if (!$email) return 0;
    $row = fetch('SELECT points FROM royalty_accounts WHERE email = ?', [$email]);
    return (int)($row['points'] ?? 0);
}

function royalty_add(string $email, int $points, string $reason, ?int $orderId = null, ?string $name = null): void
{
    $email = strtolower(trim($email));
    if (!$email || $points === 0) return;
    q('INSERT INTO royalty_accounts (email, name, points) VALUES (?,?,?)
       ON DUPLICATE KEY UPDATE points = points + VALUES(points), name = COALESCE(name, VALUES(name))',
       [$email, $name, $points]);
    q('INSERT INTO royalty_ledger (email, points, reason, order_id) VALUES (?,?,?,?)',
       [$email, $points, $reason, $orderId]);
}

/** Award points for a completed order (idempotent per order). */
function royalty_award_for_order(int $orderId): void
{
    $o = fetch('SELECT * FROM orders WHERE id = ?', [$orderId]);
    if (!$o) return;
    $already = fetch("SELECT id FROM royalty_ledger WHERE order_id = ? AND reason LIKE 'Order%'", [$orderId]);
    if ($already) return;
    $email = strtolower($o['email']);
    // Welcome bonus on first ever order
    $isFirst = !fetch('SELECT email FROM royalty_accounts WHERE email = ?', [$email]);
    if ($isFirst && POINTS_SIGNUP_BONUS > 0) {
        royalty_add($email, POINTS_SIGNUP_BONUS, 'Welcome bonus', $orderId, trim($o['first_name'].' '.$o['last_name']));
    }
    $earned = (int)floor((float)$o['total_cad'] * POINTS_PER_CAD);
    if ($earned > 0) {
        royalty_add($email, $earned, 'Order ' . $o['order_number'], $orderId, trim($o['first_name'].' '.$o['last_name']));
    }
}

function star_rating(float $rating): string
{
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) $html .= '★';
        elseif ($i === $full + 1 && $half) $html .= '⯨';
        else $html .= '☆';
    }
    return $html;
}
