<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
if(($_GET['read']??0)){ q('UPDATE contact_messages SET is_read=1 WHERE id=?', [(int)$_GET['read']]); redirect('admin/messages.php'); }
if(($_GET['delete']??0)){ q('DELETE FROM contact_messages WHERE id=?', [(int)$_GET['delete']]); a_flash('Message deleted.'); redirect('admin/messages.php'); }
$msgs = fetchAll('SELECT * FROM contact_messages ORDER BY id DESC');
$subs = fetchAll('SELECT * FROM newsletter ORDER BY id DESC LIMIT 50');
$alerts = fetchAll('SELECT s.product_id, p.name, p.stock, COUNT(*) waiting, GROUP_CONCAT(s.email SEPARATOR ", ") emails
                    FROM stock_alerts s LEFT JOIN products p ON p.id = s.product_id
                    WHERE s.is_notified = 0 GROUP BY s.product_id, p.name, p.stock ORDER BY waiting DESC');
$title='Messages'; $active='messages';
require __DIR__ . '/inc/layout.php';
?>
<div class="panel">
  <div class="panel-head"><h2>Contact Messages</h2></div>
  <table>
    <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php if(!$msgs): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:40px">No messages yet.</td></tr><?php endif; ?>
    <?php foreach($msgs as $m): ?>
      <tr style="<?= $m['is_read']?'':'background:rgba(212,175,55,.05)' ?>">
        <td><strong><?= e($m['name']) ?></strong><?= $m['is_read']?'':' <span class="chip gold" style="font-size:.6rem">NEW</span>' ?><br>
          <a href="mailto:<?= e($m['email']) ?>" style="color:var(--gold);font-size:.8rem"><?= e($m['email']) ?></a><br>
          <span class="muted" style="font-size:.78rem"><?= e($m['phone']) ?></span></td>
        <td class="muted"><?= e($m['subject'] ?: '—') ?></td>
        <td class="muted" style="max-width:340px"><?= e($m['message']) ?></td>
        <td class="muted" style="font-size:.8rem"><?= e(date('M j', strtotime($m['created_at']))) ?></td>
        <td class="actions">
          <?php if(!$m['is_read']): ?><a href="?read=<?= $m['id'] ?>&<?= csrf_param() ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-check"></i></a><?php endif; ?>
          <a href="?delete=<?= $m['id'] ?>&<?= csrf_param() ?>" class="btn btn-danger btn-sm" data-confirm="Delete?"><i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <div class="panel-head"><h2><i class="fa-regular fa-bell"></i> Back-in-Stock Requests</h2></div>
  <table>
    <thead><tr><th>Product</th><th>Stock</th><th>Waiting</th><th>Emails</th></tr></thead>
    <tbody>
    <?php if(!$alerts): ?><tr><td colspan="4" class="muted" style="text-align:center;padding:30px">No stock requests.</td></tr><?php endif; ?>
    <?php foreach($alerts as $a): ?>
      <tr>
        <td><strong><?= e($a['name'] ?? 'Product #'.$a['product_id']) ?></strong></td>
        <td><span class="chip <?= (int)$a['stock']>0?'green':'red' ?>"><?= (int)$a['stock'] ?></span></td>
        <td><span class="chip gold"><?= (int)$a['waiting'] ?></span></td>
        <td class="muted" style="font-size:.8rem;max-width:360px"><?= e($a['emails']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <div class="panel-head"><h2>Newsletter Subscribers (<?= count($subs) ?>)</h2></div>
  <div class="panel-body" style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach($subs as $s): ?><span class="chip grey"><?= e($s['email']) ?></span><?php endforeach; ?>
    <?php if(!$subs): ?><span class="muted">No subscribers yet.</span><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
