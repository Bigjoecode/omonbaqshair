<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $f=fn($k)=>trim($_POST[$k]??'');
    q('INSERT INTO coupons (code,type,value,min_subtotal,expires_at,usage_limit,is_active) VALUES (?,?,?,?,?,?,1)
       ON DUPLICATE KEY UPDATE type=VALUES(type),value=VALUES(value),min_subtotal=VALUES(min_subtotal),expires_at=VALUES(expires_at),usage_limit=VALUES(usage_limit)',
       [strtoupper($f('code')),$f('type'),(float)$f('value'),(float)$f('min_subtotal'),$f('expires_at')?:null,$f('usage_limit')!==''?(int)$f('usage_limit'):null]);
    a_flash('Coupon saved.'); redirect('admin/coupons.php');
}
if(($_GET['delete']??0)){ q('DELETE FROM coupons WHERE id=?', [(int)$_GET['delete']]); a_flash('Deleted.'); redirect('admin/coupons.php'); }
if(($_GET['toggle']??0)){ q('UPDATE coupons SET is_active=1-is_active WHERE id=?', [(int)$_GET['toggle']]); redirect('admin/coupons.php'); }
$coupons = fetchAll('SELECT * FROM coupons ORDER BY id DESC');
$title='Promotions'; $active='coupons';
require __DIR__ . '/inc/layout.php';
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <div class="panel">
    <div class="panel-head"><h2>Coupons</h2></div>
    <table>
      <thead><tr><th>Code</th><th>Discount</th><th>Min Spend</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($coupons as $c): ?>
        <tr>
          <td><strong style="color:var(--gold)"><?= e($c['code']) ?></strong></td>
          <td><?= $c['type']==='percent'? (int)$c['value'].'%' : money((float)$c['value']) ?></td>
          <td><?= money((float)$c['min_subtotal']) ?></td>
          <td><?= (int)$c['used_count'] ?><?= $c['usage_limit']?' / '.$c['usage_limit']:'' ?></td>
          <td class="muted"><?= e($c['expires_at'] ?: '—') ?></td>
          <td><a href="?toggle=<?= $c['id'] ?>&<?= csrf_param() ?>"><span class="chip <?= $c['is_active']?'green':'grey' ?>"><?= $c['is_active']?'Active':'Off' ?></span></a></td>
          <td><a href="?delete=<?= $c['id'] ?>&<?= csrf_param() ?>" class="btn btn-danger btn-sm" data-confirm="Delete coupon?"><i class="fa-solid fa-trash"></i></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="panel"><div class="panel-head"><h2>Add / Update Coupon</h2></div><div class="panel-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="fg"><label>Code</label><input name="code" required placeholder="WELCOME10"></div>
      <div class="form-row">
        <div class="fg"><label>Type</label><select name="type"><option value="percent">Percent %</option><option value="fixed">Fixed (CAD)</option></select></div>
        <div class="fg"><label>Value</label><input name="value" type="number" step="0.01" required></div>
      </div>
      <div class="fg"><label>Minimum Spend (CAD)</label><input name="min_subtotal" type="number" step="0.01" value="0"></div>
      <div class="form-row">
        <div class="fg"><label>Expires</label><input name="expires_at" type="date"></div>
        <div class="fg"><label>Usage Limit</label><input name="usage_limit" type="number" placeholder="∞"></div>
      </div>
      <button class="btn btn-gold" style="width:100%;justify-content:center">Save Coupon</button>
    </form>
  </div></div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
