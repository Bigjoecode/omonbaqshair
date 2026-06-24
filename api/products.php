<?php
/** Products API — JSON. For wishlist (by ids) and bundle builder (by filter). */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

function pack_product(array $p): array {
    $onSale = !empty($p['sale_price']) && $p['sale_price'] > 0;
    $price = (float)($p['sale_price'] ?: $p['price']);
    return [
        'id'            => (int)$p['id'],
        'name'          => $p['name'],
        'slug'          => $p['slug'],
        'url'           => product_url($p),
        'category'      => $p['category_name'] ?? '',
        'texture'       => $p['texture'],
        'image'         => product_image((int)$p['id']),
        'price'         => round(convert_price((float)$p['price']), 2),
        'sale'          => $onSale ? round(convert_price((float)$p['sale_price']), 2) : null,
        'price_display' => money($price),
        'price_cad'     => $price,
        'review_count'  => (int)$p['review_count'],
        'in_stock'      => (int)$p['stock'] > 0,
    ];
}

$action = $_GET['action'] ?? 'byids';

if ($action === 'byids') {
    $ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
    if (!$ids) { echo json_encode(['products' => []]); exit; }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $rows = fetchAll("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id
                      WHERE p.id IN ($in) AND p.is_active=1", array_values($ids));
    echo json_encode(['products' => array_map('pack_product', $rows)]);
    exit;
}

if ($action === 'bundle') {
    // products in a category-slug, optionally a texture
    $cat = $_GET['cat'] ?? '';
    $texture = $_GET['texture'] ?? '';
    $where = ['p.is_active=1']; $params = [];
    if ($cat)     { $where[] = 'c.slug = ?';   $params[] = $cat; }
    if ($texture) { $where[] = 'p.texture = ?'; $params[] = $texture; }
    $rows = fetchAll("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id
                      WHERE " . implode(' AND ', $where) . " ORDER BY p.price", $params);
    echo json_encode(['products' => array_map('pack_product', $rows)]);
    exit;
}

echo json_encode(['products' => []]);
