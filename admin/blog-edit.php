<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$post = $id ? fetch('SELECT * FROM blog_posts WHERE id=?', [$id]) : null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $f=fn($k)=>trim($_POST[$k]??'');
    $slug = $f('slug') ?: slugify($f('title'));
    $cover = handle_upload('cover','blog') ?? ($f('cover_image') ?: ($post['cover_image']??null));
    $pub = isset($_POST['is_published'])?1:0;
    if($id){
        q('UPDATE blog_posts SET title=?,slug=?,excerpt=?,body=?,cover_image=?,author=?,is_published=?,published_at=COALESCE(published_at,NOW()) WHERE id=?',
          [$f('title'),$slug,$f('excerpt'),$_POST['body']??'',$cover,$f('author'),$pub,$id]);
    } else {
        q('INSERT INTO blog_posts (title,slug,excerpt,body,cover_image,author,is_published,published_at) VALUES (?,?,?,?,?,?,?,NOW())',
          [$f('title'),$slug,$f('excerpt'),$_POST['body']??'',$cover,$f('author')?:'Omonblaq Team',$pub]);
        $id=(int)db()->lastInsertId();
    }
    a_flash('Post saved.'); redirect('admin/blog-edit.php?id='.$id);
}
$v=fn($k)=>e($post[$k]??'');
$title=$id?'Edit Post':'New Post'; $active='blog';
require __DIR__ . '/inc/layout.php';
?>
<form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
  <?= csrf_field() ?>
  <div class="panel"><div class="panel-body">
    <div class="fg"><label>Title</label><input name="title" value="<?= $v('title') ?>" required></div>
    <div class="fg"><label>Slug</label><input name="slug" value="<?= $v('slug') ?>" placeholder="auto"></div>
    <div class="fg"><label>Excerpt</label><textarea name="excerpt" style="min-height:70px"><?= $v('excerpt') ?></textarea></div>
    <div class="fg"><label>Body (HTML allowed)</label><textarea name="body" style="min-height:280px"><?= e($post['body']??'') ?></textarea></div>
  </div></div>
  <div>
    <div class="panel"><div class="panel-body">
      <button class="btn btn-gold" style="width:100%;justify-content:center">Save Post</button>
      <a href="<?= admin_url('blog.php') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px">Back</a>
    </div></div>
    <div class="panel"><div class="panel-body">
      <div class="fg"><label>Author</label><input name="author" value="<?= $v('author')?:'Omonblaq Team' ?>"></div>
      <div class="fg"><label>Cover (filename or upload)</label><input name="cover_image" value="<?= $v('cover_image') ?>" placeholder="blog-1.jpg"><input type="file" name="cover" accept="image/*" style="margin-top:8px"></div>
      <div class="toggle"><input type="checkbox" name="is_published" <?= !$post||($post['is_published']??1)?'checked':'' ?>> Published</div>
    </div></div>
  </div>
</form>
<?php require __DIR__ . '/inc/foot.php'; ?>
