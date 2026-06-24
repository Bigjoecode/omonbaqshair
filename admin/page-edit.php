<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$page = $id ? fetch('SELECT * FROM pages WHERE id = ?', [$id]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = fn($k) => trim($_POST[$k] ?? '');
    $slug = slugify($f('slug') ?: $f('title'));
    $body = $_POST['body'] ?? '';
    $pub  = isset($_POST['is_published']) ? 1 : 0;

    // Cover: a new uploaded cover wins, else the typed/kept value (store filename only)
    $cover = handle_upload('cover', 'pages') ?? ($f('cover_image') ?: ($page['cover_image'] ?? null));

    if ($id) {
        q('UPDATE pages SET slug=?, title=?, eyebrow=?, cover_image=?, body=?, is_published=? WHERE id=?',
            [$slug, $f('title'), $f('eyebrow') ?: null, $cover, $body, $pub, $id]);
    } else {
        // unique slug
        if (fetch('SELECT id FROM pages WHERE slug = ?', [$slug])) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
        q('INSERT INTO pages (slug,title,eyebrow,cover_image,body,is_published) VALUES (?,?,?,?,?,?)',
            [$slug, $f('title'), $f('eyebrow') ?: null, $cover, $body, $pub]);
        $id = (int)db()->lastInsertId();
    }
    a_flash('Page saved.');
    redirect('admin/page-edit.php?id=' . $id);
}

$v = fn($k) => e($page[$k] ?? '');
$title = $id ? 'Edit Page' : 'New Page'; $active = 'pages';
require __DIR__ . '/inc/layout.php';
?>
<form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
  <?= csrf_field() ?>
  <div class="panel"><div class="panel-body">
    <div class="fg"><label>Page Title</label><input name="title" value="<?= $v('title') ?>" required></div>
    <div class="fg"><label>Eyebrow (small line above title)</label><input name="eyebrow" value="<?= $v('eyebrow') ?>"></div>
    <div class="fg"><label>Body (HTML)</label><textarea name="body" id="bodyArea" style="min-height:420px;font-family:monospace;font-size:.85rem"><?= e($page['body'] ?? '') ?></textarea>
      <div class="hint">HTML is allowed: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;a&gt;, &lt;strong&gt;, &lt;img&gt;. For FAQ-style accordions use &lt;details&gt;&lt;summary&gt;Question&lt;/summary&gt;&lt;p&gt;Answer&lt;/p&gt;&lt;/details&gt;.</div>
    </div>
  </div></div>
  <div>
    <div class="panel"><div class="panel-body">
      <button class="btn btn-gold" style="width:100%;justify-content:center"><i class="fa-solid fa-floppy-disk"></i> Save Page</button>
      <a href="<?= admin_url('pages.php') ?>" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px">Back</a>
      <?php if ($page): ?><a href="<?= url($page['slug'] . '.php') ?>" target="_blank" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:8px"><i class="fa-solid fa-eye"></i> View Live</a><?php endif; ?>
    </div></div>
    <div class="panel"><div class="panel-body">
      <div class="fg"><label>URL Slug</label><input name="slug" value="<?= $v('slug') ?>" placeholder="auto from title"><div class="hint">Page will live at <code style="color:var(--gold)">/<?= $page ? e($page['slug']) : 'slug' ?></code></div></div>
      <div class="fg"><label>Cover Image (filename or upload)</label>
        <?php if ($cu = media_url($page['cover_image'] ?? null)): ?>
          <img src="<?= e($cu) ?>" alt="current cover" style="width:100%;height:130px;object-fit:cover;border-radius:8px;border:1px solid var(--line-soft);margin-bottom:8px">
        <?php endif; ?>
        <input name="cover_image" id="coverField" value="<?= $v('cover_image') ?>" placeholder="about.jpg">
        <input type="file" name="cover" accept="image/*" style="margin-top:8px">
        <div class="hint"><?= ($page['slug'] ?? '') === 'index' ? 'On the <strong>Home</strong> page this image is the <strong>"Our Promise" section photo</strong>.' : 'This image shows as the page banner.' ?> Upload a file, then <strong>Save Page</strong>.</div>
      </div>
      <div class="toggle"><input type="checkbox" name="is_published" <?= !$page || $page['is_published'] ? 'checked' : '' ?>> Published</div>
    </div></div>
  </div>
</form>
<?php require __DIR__ . '/inc/foot.php'; ?>
