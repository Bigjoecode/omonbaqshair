<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
$statusFilter = $_GET['status'] ?? '';
$where='1'; $params=[];
if ($statusFilter){ $where='status=?'; $params=[$statusFilter]; }
$orders = fetchAll("SELECT * FROM orders WHERE $where ORDER BY id DESC", $params);
$statusChip = fn($s) => match($s){'new'=>'gold','processing'=>'blue','shipped'=>'blue','delivered'=>'green','cancelled'=>'red',default=>'grey'};
$payChip = fn($s) => match($s){'paid'=>'green','pending'=>'gold','failed'=>'red',default=>'grey'};
$title='Orders'; $active='orders';
require __DIR__ . '/inc/layout.php';
?>
<div class="panel">
  <div class="panel-head">
    <h2><?= count($orders) ?> Orders</h2>
    <div style="display:flex;gap:8px">
      <?php foreach(['' =>'All','new'=>'New','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered'] as $k=>$lbl): ?>
        <a href="?status=<?= $k ?>" class="btn btn-sm <?= $statusFilter===$k?'btn-gold':'btn-ghost' ?>"><?= $lbl ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <table>
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if(!$orders): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:40px">No orders found.</td></tr><?php endif; ?>
    <?php foreach($orders as $o): ?>
      <tr>
        <td><a href="<?= admin_url('order-view.php?id='.$o['id']) ?>" style="color:var(--gold)"><?= e($o['order_number']) ?></a></td>
        <td class="muted" style="font-size:.82rem"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
        <td><?= e($o['first_name'].' '.$o['last_name']) ?><br><span class="muted" style="font-size:.76rem"><?= e($o['country']) ?></span></td>
        <td><?= money((float)$o['total_cad']) ?></td>
        <td><span class="chip <?= $payChip($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></td>
        <td><span class="chip <?= $statusChip($o['status']) ?>"><?= e($o['status']) ?></span></td>
        <td><a href="<?= admin_url('order-view.php?id='.$o['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
