<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
$pages = fetchAll('SELECT * FROM pages ORDER BY slug');
$title = 'Pages'; $active = 'pages';
require __DIR__ . '/inc/layout.php';
?>
<div class="panel">
  <div class="panel-head">
    <h2>Content Pages</h2>
    <a href="<?= admin_url('page-edit.php') ?>" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus"></i> New Page</a>
  </div>
  <table>
    <thead><tr><th>Title</th><th>URL</th><th>Status</th><th>Updated</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td><strong><?= strip_tags($p['title']) ?></strong></td>
        <td><a href="<?= url($p['slug'] . '.php') ?>" target="_blank" style="color:var(--gold)">/<?= e($p['slug']) ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.7em"></i></a></td>
        <td><?= $p['is_published'] ? '<span class="chip green">Live</span>' : '<span class="chip grey">Draft</span>' ?></td>
        <td class="muted" style="font-size:.82rem"><?= e(date('M j, Y', strtotime($p['updated_at']))) ?></td>
        <td><a href="<?= admin_url('page-edit.php?id=' . $p['id']) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i> Edit</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
