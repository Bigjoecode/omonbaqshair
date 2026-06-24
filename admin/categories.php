<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $f = fn($k)=>trim($_POST[$k]??'');
    $cid = (int)($_POST['id'] ?? 0);
    $slug = $f('slug') ?: slugify($f('name'));
    $img = handle_upload('image','categories') ?? ($f('image') ?: null);
    if ($cid) {
        $sql = 'UPDATE categories SET name=?,slug=?,description=?,sort_order=?,is_featured=?,is_active=?'.($img?',image=?':'').' WHERE id=?';
        $p = [$f('name'),$slug,$f('description'),(int)$f('sort_order'),isset($_POST['is_featured'])?1:0,isset($_POST['is_active'])?1:0];
        if($img)$p[]=$img; $p[]=$cid; q($sql,$p);
    } else {
        q('INSERT INTO categories (name,slug,description,image,sort_order,is_featured,is_active) VALUES (?,?,?,?,?,?,?)',
          [$f('name'),$slug,$f('description'),$img,(int)$f('sort_order'),isset($_POST['is_featured'])?1:0,isset($_POST['is_active'])?1:0]);
    }
    a_flash('Category saved.');
    redirect('admin/categories.php');
}
if (($_GET['delete']??0)) { q('DELETE FROM categories WHERE id=?', [(int)$_GET['delete']]); a_flash('Category deleted.'); redirect('admin/categories.php'); }

$edit = ($_GET['edit']??0) ? fetch('SELECT * FROM categories WHERE id=?', [(int)$_GET['edit']]) : null;
$cats = fetchAll('SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) pc FROM categories c ORDER BY sort_order, name');
$title='Categories'; $active='categories';
require __DIR__ . '/inc/layout.php';
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <div class="panel">
    <div class="panel-head"><h2><?= count($cats) ?> Categories</h2></div>
    <table>
      <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Featured</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($cats as $c): ?>
        <tr>
          <td><strong><?= e($c['name']) ?></strong></td>
          <td class="muted"><?= e($c['slug']) ?></td>
          <td><span class="chip gold"><?= (int)$c['pc'] ?></span></td>
          <td><?= $c['is_featured']?'<span class="chip green">Yes</span>':'<span class="chip grey">No</span>' ?></td>
          <td><?= $c['is_active']?'<span class="chip green">Active</span>':'<span class="chip grey">Hidden</span>' ?></td>
          <td class="actions">
            <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
            <a href="?delete=<?= $c['id'] ?>&<?= csrf_param() ?>" class="btn btn-danger btn-sm" data-confirm="Delete category?"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="panel"><div class="panel-head"><h2><?= $edit?'Edit':'Add' ?> Category</h2></div><div class="panel-body">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
      <div class="fg"><label>Name</label><input name="name" value="<?= e($edit['name']??'') ?>" required></div>
      <div class="fg"><label>Slug</label><input name="slug" value="<?= e($edit['slug']??'') ?>" placeholder="auto"></div>
      <div class="fg"><label>Description</label><textarea name="description" style="min-height:70px"><?= e($edit['description']??'') ?></textarea></div>
      <div class="fg"><label>Tile Image (filename in assets/img or upload)</label><input name="image" value="<?= e($edit['image']??'') ?>" placeholder="cat-wigs.jpg"><input type="file" name="image" accept="image/*" style="margin-top:8px"></div>
      <div class="fg"><label>Sort Order</label><input name="sort_order" type="number" value="<?= (int)($edit['sort_order']??0) ?>"></div>
      <div class="toggle"><input type="checkbox" name="is_featured" <?= ($edit['is_featured']??0)?'checked':'' ?>> Featured on homepage</div>
      <div class="toggle" style="margin:8px 0 16px"><input type="checkbox" name="is_active" <?= !$edit||($edit['is_active']??1)?'checked':'' ?>> Active</div>
      <button class="btn btn-gold" style="width:100%;justify-content:center">Save Category</button>
      <?php if($edit): ?><a href="<?= admin_url('categories.php') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px">New Category</a><?php endif; ?>
    </form>
  </div></div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
