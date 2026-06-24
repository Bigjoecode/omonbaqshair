<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/../includes/mailer.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$product = $id ? fetch('SELECT * FROM products WHERE id = ?', [$id]) : null;
$oldStock = $product ? (int)$product['stock'] : null;
$cats = fetchAll('SELECT * FROM categories ORDER BY sort_order, name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = fn($k) => trim($_POST[$k] ?? '');
    $name = $f('name');
    $slug = $f('slug') ?: slugify($name);
    // ensure unique slug
    $dupe = fetch('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $id]);
    if ($dupe) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);

    $data = [
        $f('category_id') ?: null, $name, $slug, $f('sku'), $f('short_desc'), $f('description'),
        (float)$f('price'), $f('sale_price') !== '' ? (float)$f('sale_price') : null, (int)$f('stock'),
        $f('origin') ?: null, $f('texture') ?: null, $f('length_inches') ?: null, $f('lace_type') ?: null,
        $f('density') ?: null, $f('color_shade') ?: null,
        isset($_POST['is_featured'])?1:0, isset($_POST['is_bestseller'])?1:0, isset($_POST['is_new'])?1:0,
        isset($_POST['is_active'])?1:0, $f('badge') ?: null,
    ];

    if ($id) {
        q('UPDATE products SET category_id=?,name=?,slug=?,sku=?,short_desc=?,description=?,price=?,sale_price=?,stock=?,
           origin=?,texture=?,length_inches=?,lace_type=?,density=?,color_shade=?,is_featured=?,is_bestseller=?,is_new=?,is_active=?,badge=?
           WHERE id=?', array_merge($data, [$id]));
    } else {
        q('INSERT INTO products (category_id,name,slug,sku,short_desc,description,price,sale_price,stock,origin,texture,length_inches,lace_type,density,color_shade,is_featured,is_bestseller,is_new,is_active,badge)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', $data);
        $id = (int)db()->lastInsertId();
    }

    // Image uploads (up to 4)
    $order = (int)(fetch('SELECT COALESCE(MAX(sort_order),-1) m FROM product_images WHERE product_id=?', [$id])['m'] ?? -1);
    $hasPrimary = (bool)fetch('SELECT id FROM product_images WHERE product_id=? AND is_primary=1', [$id]);
    foreach (['image1','image2','image3','image4'] as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $fn = handle_upload($field, 'products');
            if ($fn) { $order++; q('INSERT INTO product_images (product_id,image,is_primary,sort_order) VALUES (?,?,?,?)', [$id, $fn, $hasPrimary?0:1, $order]); $hasPrimary = true; }
        }
    }
    // image URL (optional)
    if ($f('image_url')) {
        $order++; q('INSERT INTO product_images (product_id,image,is_primary,sort_order) VALUES (?,?,?,?)', [$id, $f('image_url'), $hasPrimary?0:1, $order]);
    }

    // Back-in-stock alerts: if this product just went from 0 → in stock, notify waiters.
    $newStock = (int)$f('stock');
    $notifyMsg = '';
    if ($oldStock !== null && $oldStock <= 0 && $newStock > 0) {
        $sent = notify_back_in_stock($id);
        if ($sent > 0) $notifyMsg = " {$sent} back-in-stock email" . ($sent === 1 ? '' : 's') . ' sent.';
    }
    a_flash('Product saved.' . $notifyMsg);
    redirect('admin/product-edit.php?id=' . $id);
}

// image delete
if (($_GET['delimg'] ?? 0) && $id) {
    q('DELETE FROM product_images WHERE id=? AND product_id=?', [(int)$_GET['delimg'], $id]);
    redirect('admin/product-edit.php?id=' . $id);
}

$images = $id ? fetchAll('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order', [$id]) : [];
$v = fn($k, $d='') => e($product[$k] ?? $d);
$title = $id ? 'Edit Product' : 'Add Product'; $active='products';
require __DIR__ . '/inc/layout.php';
?>
<form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <?= csrf_field() ?>
  <div>
    <div class="panel"><div class="panel-body">
      <div class="fg"><label>Product Name</label><input name="name" value="<?= $v('name') ?>" required></div>
      <div class="form-row">
        <div class="fg"><label>Slug (URL)</label><input name="slug" value="<?= $v('slug') ?>" placeholder="auto from name"></div>
        <div class="fg"><label>SKU</label><input name="sku" value="<?= $v('sku') ?>"></div>
      </div>
      <div class="fg"><label>Short Description</label><input name="short_desc" value="<?= $v('short_desc') ?>"></div>
      <div class="fg"><label>Full Description</label><textarea name="description"><?= $v('description') ?></textarea></div>
    </div></div>

    <div class="panel"><div class="panel-head"><h2>Hair Details</h2></div><div class="panel-body">
      <div class="form-row">
        <div class="fg"><label>Origin</label><input name="origin" value="<?= $v('origin') ?>" placeholder="Brazilian, Raw Indian..."></div>
        <div class="fg"><label>Texture</label><input name="texture" value="<?= $v('texture') ?>" placeholder="Body Wave, Straight..."></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Lengths (comma list)</label><input name="length_inches" value="<?= $v('length_inches') ?>" placeholder='14",16",18"'></div>
        <div class="fg"><label>Lace Type</label><input name="lace_type" value="<?= $v('lace_type') ?>" placeholder="13x4 HD Lace"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Density</label><input name="density" value="<?= $v('density') ?>" placeholder="180%"></div>
        <div class="fg"><label>Colour Shade</label><input name="color_shade" value="<?= $v('color_shade') ?>" placeholder="Natural Black 1B"></div>
      </div>
    </div></div>

    <div class="panel"><div class="panel-head"><h2>Images</h2></div><div class="panel-body">
      <?php if($images): ?>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px">
        <?php foreach($images as $im):
          $src = str_starts_with($im['image'],'http')?$im['image']:asset('uploads/products/'.$im['image']); ?>
          <div style="position:relative">
            <img src="<?= e($src) ?>" style="width:90px;height:110px;object-fit:cover;border-radius:8px;border:1px solid <?= $im['is_primary']?'var(--gold)':'var(--line-soft)' ?>">
            <a href="?id=<?= $id ?>&delimg=<?= $im['id'] ?>&<?= csrf_param() ?>" data-confirm="Remove image?" style="position:absolute;top:-8px;right:-8px;background:var(--red);width:22px;height:22px;border-radius:50%;display:grid;place-items:center;color:#fff;font-size:.7rem"><i class="fa-solid fa-xmark"></i></a>
            <?php if($im['is_primary']): ?><span style="position:absolute;bottom:4px;left:4px;font-size:.6rem;background:var(--gold-grad);color:#1a1206;padding:1px 6px;border-radius:8px">Main</span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="form-row">
        <div class="fg"><label>Upload Image 1</label><input type="file" name="image1" accept="image/*"></div>
        <div class="fg"><label>Upload Image 2</label><input type="file" name="image2" accept="image/*"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Upload Image 3</label><input type="file" name="image3" accept="image/*"></div>
        <div class="fg"><label>Upload Image 4</label><input type="file" name="image4" accept="image/*"></div>
      </div>
      <div class="fg"><label>...or Image URL</label><input name="image_url" placeholder="https://..."><div class="hint">First image added becomes the main image.</div></div>
    </div></div>
  </div>

  <div>
    <div class="panel"><div class="panel-body">
      <button class="btn btn-gold" style="width:100%;justify-content:center"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
      <a href="<?= admin_url('products.php') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:10px">Cancel</a>
    </div></div>
    <div class="panel"><div class="panel-head"><h2>Organize</h2></div><div class="panel-body">
      <div class="fg"><label>Category</label><select name="category_id">
        <option value="">— None —</option>
        <?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($product['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="form-row">
        <div class="fg"><label>Price (CAD)</label><input name="price" type="number" step="0.01" value="<?= $v('price','0') ?>" required></div>
        <div class="fg"><label>Sale Price</label><input name="sale_price" type="number" step="0.01" value="<?= $v('sale_price') ?>"></div>
      </div>
      <div class="form-row">
        <div class="fg"><label>Stock</label><input name="stock" type="number" value="<?= $v('stock','0') ?>"></div>
        <div class="fg"><label>Badge</label><input name="badge" value="<?= $v('badge') ?>" placeholder="Sale, New..."></div>
      </div>
      <div class="fg"><label>Visibility</label>
        <div class="toggle"><input type="checkbox" name="is_active" <?= !$product||$product['is_active']?'checked':'' ?>> <span>Active (visible in store)</span></div>
        <div class="toggle" style="margin-top:8px"><input type="checkbox" name="is_featured" <?= ($product['is_featured']??0)?'checked':'' ?>> <span>Featured on homepage</span></div>
        <div class="toggle" style="margin-top:8px"><input type="checkbox" name="is_bestseller" <?= ($product['is_bestseller']??0)?'checked':'' ?>> <span>Best seller</span></div>
        <div class="toggle" style="margin-top:8px"><input type="checkbox" name="is_new" <?= ($product['is_new']??0)?'checked':'' ?>> <span>New arrival</span></div>
      </div>
    </div></div>
  </div>
</form>
<?php require __DIR__ . '/inc/foot.php'; ?>
