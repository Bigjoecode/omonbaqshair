<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();

$revenue   = (float)(fetch("SELECT COALESCE(SUM(total_cad),0) s FROM orders WHERE payment_status='paid'")['s'] ?? 0);
$orderCnt  = (int)(fetch("SELECT COUNT(*) c FROM orders")['c'] ?? 0);
$custCnt   = (int)(fetch("SELECT COUNT(*) c FROM customers")['c'] ?? 0);
$prodCnt   = (int)(fetch("SELECT COUNT(*) c FROM products WHERE is_active=1")['c'] ?? 0);
$pending   = (int)(fetch("SELECT COUNT(*) c FROM orders WHERE status='new'")['c'] ?? 0);
$subs      = (int)(fetch("SELECT COUNT(*) c FROM newsletter")['c'] ?? 0);
$recent    = fetchAll("SELECT * FROM orders ORDER BY id DESC LIMIT 8");
$topProds  = fetchAll("SELECT p.name, p.review_count, c.name cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_bestseller=1 ORDER BY p.review_count DESC LIMIT 5");

$statusChip = fn($s) => match($s){'new'=>'gold','processing'=>'blue','shipped'=>'blue','delivered'=>'green','cancelled'=>'red',default=>'grey'};
$payChip = fn($s) => match($s){'paid'=>'green','pending'=>'gold','failed'=>'red','refunded'=>'grey',default=>'grey'};

$title='Dashboard'; $active='dashboard';
require __DIR__ . '/inc/layout.php';
?>
<div class="stats">
  <div class="stat"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="num"><?= money($revenue) ?></div><div class="lbl">Revenue (paid)</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-bag-shopping"></i></div><div class="num"><?= $orderCnt ?></div><div class="lbl">Total Orders</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="num"><?= $custCnt ?></div><div class="lbl">Customers</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-gem"></i></div><div class="num"><?= $prodCnt ?></div><div class="lbl">Active Products</div></div>
</div>
<div class="stats" style="grid-template-columns:repeat(2,1fr)">
  <div class="stat"><div class="ic"><i class="fa-solid fa-clock"></i></div><div class="num"><?= $pending ?></div><div class="lbl">Orders Awaiting Action</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-envelope-open-text"></i></div><div class="num"><?= $subs ?></div><div class="lbl">Newsletter Subscribers</div></div>
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:24px">
  <div class="panel">
    <div class="panel-head"><h2>Recent Orders</h2><a href="<?= admin_url('orders.php') ?>" class="btn btn-outline btn-sm">View All</a></div>
    <table>
      <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(!$recent): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:40px">No orders yet.</td></tr><?php endif; ?>
      <?php foreach($recent as $o): ?>
        <tr>
          <td><a href="<?= admin_url('order-view.php?id='.$o['id']) ?>" style="color:var(--gold)"><?= e($o['order_number']) ?></a></td>
          <td><?= e($o['first_name'].' '.$o['last_name']) ?><br><span class="muted" style="font-size:.78rem"><?= e($o['email']) ?></span></td>
          <td><?= money((float)$o['total_cad']) ?></td>
          <td><span class="chip <?= $payChip($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></td>
          <td><span class="chip <?= $statusChip($o['status']) ?>"><?= e($o['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Best Sellers</h2></div>
    <div class="panel-body">
      <?php foreach($topProds as $p): ?>
        <div style="display:flex;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--line-soft)">
          <div><div style="font-family:var(--serif);font-size:1.05rem"><?= e($p['name']) ?></div><span class="muted" style="font-size:.76rem"><?= e($p['cat']) ?></span></div>
          <span class="chip gold"><?= (int)$p['review_count'] ?> ★</span>
        </div>
      <?php endforeach; ?>
      <a href="<?= admin_url('product-edit.php') ?>" class="btn btn-gold btn-sm" style="margin-top:16px;width:100%;justify-content:center"><i class="fa-solid fa-plus"></i> Add Product</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
