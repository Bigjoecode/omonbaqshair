<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $f=fn($k)=>trim($_POST[$k]??'');
    $tid=(int)($_POST['id']??0);
    if($tid) q('UPDATE testimonials SET author=?,location=?,rating=?,body=?,sort_order=?,is_active=? WHERE id=?',
        [$f('author'),$f('location'),(int)$f('rating'),$f('body'),(int)$f('sort_order'),isset($_POST['is_active'])?1:0,$tid]);
    else q('INSERT INTO testimonials (author,location,rating,body,sort_order,is_active) VALUES (?,?,?,?,?,?)',
        [$f('author'),$f('location'),(int)$f('rating'),$f('body'),(int)$f('sort_order'),isset($_POST['is_active'])?1:0]);
    a_flash('Testimonial saved.'); redirect('admin/testimonials.php');
}
if(($_GET['delete']??0)){ q('DELETE FROM testimonials WHERE id=?', [(int)$_GET['delete']]); a_flash('Deleted.'); redirect('admin/testimonials.php'); }
$edit=($_GET['edit']??0)?fetch('SELECT * FROM testimonials WHERE id=?',[(int)$_GET['edit']]):null;
$rows=fetchAll('SELECT * FROM testimonials ORDER BY sort_order');
$title='Testimonials'; $active='testimonials';
require __DIR__ . '/inc/layout.php';
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <div class="panel"><div class="panel-head"><h2><?= count($rows) ?> Testimonials</h2></div>
    <table><thead><tr><th>Author</th><th>Location</th><th>Rating</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach($rows as $t): ?>
      <tr><td><strong><?= e($t['author']) ?></strong><br><span class="muted" style="font-size:.78rem"><?= e(mb_strimwidth($t['body'],0,50,'…')) ?></span></td>
      <td class="muted"><?= e($t['location']) ?></td><td style="color:var(--gold)"><?= str_repeat('★',(int)$t['rating']) ?></td>
      <td><?= $t['is_active']?'<span class="chip green">Live</span>':'<span class="chip grey">Off</span>' ?></td>
      <td class="actions"><a href="?edit=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
      <a href="?delete=<?= $t['id'] ?>&<?= csrf_param() ?>" class="btn btn-danger btn-sm" data-confirm="Delete?"><i class="fa-solid fa-trash"></i></a></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="panel"><div class="panel-head"><h2><?= $edit?'Edit':'Add' ?></h2></div><div class="panel-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
      <div class="fg"><label>Author</label><input name="author" value="<?= e($edit['author']??'') ?>" required></div>
      <div class="fg"><label>Location</label><input name="location" value="<?= e($edit['location']??'') ?>" placeholder="Lagos, Nigeria"></div>
      <div class="form-row">
        <div class="fg"><label>Rating</label><input name="rating" type="number" min="1" max="5" value="<?= (int)($edit['rating']??5) ?>"></div>
        <div class="fg"><label>Sort</label><input name="sort_order" type="number" value="<?= (int)($edit['sort_order']??0) ?>"></div>
      </div>
      <div class="fg"><label>Quote</label><textarea name="body" required><?= e($edit['body']??'') ?></textarea></div>
      <div class="toggle" style="margin-bottom:14px"><input type="checkbox" name="is_active" <?= !$edit||($edit['is_active']??1)?'checked':'' ?>> Active</div>
      <button class="btn btn-gold" style="width:100%;justify-content:center">Save</button>
    </form>
  </div></div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
