<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
if(($_GET['delete']??0)){ q('DELETE FROM blog_posts WHERE id=?', [(int)$_GET['delete']]); a_flash('Post deleted.'); redirect('admin/blog.php'); }
$posts = fetchAll('SELECT * FROM blog_posts ORDER BY id DESC');
$title='Blog'; $active='blog';
require __DIR__ . '/inc/layout.php';
?>
<div class="panel">
  <div class="panel-head"><h2><?= count($posts) ?> Posts</h2><a href="<?= admin_url('blog-edit.php') ?>" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> New Post</a></div>
  <table>
    <thead><tr><th>Title</th><th>Author</th><th>Published</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if(!$posts): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:40px">No posts yet.</td></tr><?php endif; ?>
    <?php foreach($posts as $p): ?>
      <tr>
        <td><strong><?= e($p['title']) ?></strong></td>
        <td class="muted"><?= e($p['author']) ?></td>
        <td class="muted" style="font-size:.82rem"><?= e(date('M j, Y', strtotime($p['published_at'] ?: $p['created_at']))) ?></td>
        <td><?= $p['is_published']?'<span class="chip green">Live</span>':'<span class="chip grey">Draft</span>' ?></td>
        <td class="actions">
          <a href="<?= admin_url('blog-edit.php?id='.$p['id']) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i></a>
          <a href="?delete=<?= $p['id'] ?>&<?= csrf_param() ?>" class="btn btn-danger btn-sm" data-confirm="Delete post?"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
