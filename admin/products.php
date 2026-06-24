<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();

if (($_GET['action'] ?? '') === 'delete' && ($id = (int)($_GET['id'] ?? 0))) {
    q('DELETE FROM product_images WHERE product_id = ?', [$id]);
    q('DELETE FROM products WHERE id = ?', [$id]);
    a_flash('Product deleted.');
    redirect('admin/products.php');
}
if (($_GET['action'] ?? '') === 'toggle' && ($id = (int)($_GET['id'] ?? 0))) {
    q('UPDATE products SET is_active = 1 - is_active WHERE id = ?', [$id]);
    redirect('admin/products.php');
}

$search = trim($_GET['q'] ?? '');
$params = []; $where = '1';
if ($search) { $where = '(p.name LIKE ? OR p.sku LIKE ?)'; $params = ["%$search%","%$search%"]; }
$products = fetchAll(
    "SELECT p.*, c.name cat, (SELECT image FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC LIMIT 1) img
     FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY p.id DESC", $params);

$title='Products'; $active='products';
require __DIR__ . '/inc/layout.php';
?>
<div class="panel">
  <div class="panel-head">
    <h2><?= count($products) ?> Products</h2>
    <div style="display:flex;gap:10px">
      <form method="get" style="display:flex;gap:8px">
        <input class="fg" name="q" value="<?= e($search) ?>" placeholder="Search..." style="padding:9px 14px;background:rgba(255,255,255,.03);border:1px solid var(--line);color:var(--cream);border-radius:8px">
        <button class="btn btn-outline btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <a href="<?= admin_url('product-edit.php') ?>" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> Add Product</a>
    </div>
  </div>
  <table>
    <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($products as $p):
      $img = $p['img'] ? (str_starts_with($p['img'],'http')?$p['img']:asset('uploads/products/'.$p['img'])) : asset('img/placeholder-product.svg'); ?>
      <tr>
        <td><img class="t-img" src="<?= e($img) ?>" alt=""></td>
        <td><strong><?= e($p['name']) ?></strong><br><span class="muted" style="font-size:.76rem"><?= e($p['sku']) ?></span></td>
        <td class="muted"><?= e($p['cat']) ?></td>
        <td>
          <?php if($p['sale_price']): ?><span style="color:var(--gold)"><?= money((float)$p['sale_price']) ?></span> <span class="muted" style="text-decoration:line-through;font-size:.78rem"><?= money((float)$p['price']) ?></span>
          <?php else: ?><?= money((float)$p['price']) ?><?php endif; ?>
        </td>
        <td><?= (int)$p['stock'] ?></td>
        <td><a href="?action=toggle&id=<?= $p['id'] ?>&<?= csrf_param() ?>"><span class="chip <?= $p['is_active']?'green':'grey' ?>"><?= $p['is_active']?'Active':'Hidden' ?></span></a></td>
        <td class="actions">
          <a href="<?= admin_url('product-edit.php?id='.$p['id']) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
          <a href="?action=delete&id=<?= $p['id'] ?>&<?= csrf_param() ?>" class="btn btn-danger btn-sm" data-confirm="Delete this product?"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
