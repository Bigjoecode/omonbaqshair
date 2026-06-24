<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/../includes/mailer.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$o = fetch('SELECT * FROM orders WHERE id=?', [$id]);
if (!$o) { a_flash('Order not found.'); redirect('admin/orders.php'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $newStatus = $_POST['status'];
    $tracking  = trim($_POST['tracking_number'] ?? '');
    $statusChanged = $newStatus !== $o['status'];
    q('UPDATE orders SET status=?, payment_status=?, tracking_number=? WHERE id=?',
        [$newStatus, $_POST['payment_status'], $tracking ?: null, $id]);

    $msg = 'Order updated.';
    // Send a status email to the customer when the order is shipped / delivered / cancelled.
    $notify = !empty($_POST['notify_customer']);
    if ($notify && $statusChanged && in_array($newStatus, ['shipped','delivered','cancelled'], true)) {
        $fresh = fetch('SELECT * FROM orders WHERE id=?', [$id]);
        if (email_order_status($fresh)) {
            $msg .= ' Customer notified by email (' . $newStatus . ').';
        } else {
            $msg .= ' (Status email could not be sent — check storage/mail.log.)';
        }
    }
    a_flash($msg);
    redirect('admin/order-view.php?id='.$id);
}
$items = fetchAll('SELECT * FROM order_items WHERE order_id=?', [$id]);
$title='Order '.$o['order_number']; $active='orders';
require __DIR__ . '/inc/layout.php';
?>
<a href="<?= admin_url('orders.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:16px"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <div class="panel">
    <div class="panel-head"><h2><?= e($o['order_number']) ?></h2><span class="muted"><?= e(date('M j, Y · g:ia', strtotime($o['created_at']))) ?></span></div>
    <table>
      <thead><tr><th>Item</th><th>Variant</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach($items as $it): ?>
        <tr><td><?= e($it['name']) ?></td><td class="muted"><?= e($it['variant'] ?: '—') ?></td>
        <td><?= money((float)$it['price_cad']) ?></td><td><?= (int)$it['qty'] ?></td><td><?= money((float)$it['line_total']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="panel-body" style="border-top:1px solid var(--line-soft)">
      <div style="max-width:300px;margin-left:auto">
        <div style="display:flex;justify-content:space-between;padding:5px 0"><span class="muted">Subtotal</span><span><?= money((float)$o['subtotal_cad']) ?></span></div>
        <?php if($o['discount_cad']>0): ?><div style="display:flex;justify-content:space-between;padding:5px 0"><span class="muted">Discount</span><span style="color:var(--gold)">−<?= money((float)$o['discount_cad']) ?></span></div><?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:5px 0"><span class="muted">Shipping</span><span><?= money((float)$o['shipping_cad']) ?></span></div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--line-soft);font-size:1.2rem;color:var(--gold);font-family:var(--serif)"><span>Total</span><span><?= money((float)$o['total_cad']) ?></span></div>
        <div class="muted" style="font-size:.78rem;text-align:right">Charged: <?= e($o['currency']) ?> <?= number_format((float)$o['total_charged'],2) ?></div>
      </div>
    </div>
  </div>

  <div>
    <div class="panel"><div class="panel-head"><h2>Status</h2></div><div class="panel-body">
      <form method="post">
        <?= csrf_field() ?>
        <div class="fg"><label>Order Status</label><select name="status">
          <?php foreach(['new','processing','shipped','delivered','cancelled'] as $s): ?><option <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select></div>
        <div class="fg"><label>Payment Status</label><select name="payment_status">
          <?php foreach(['pending','paid','failed','refunded'] as $s): ?><option <?= $o['payment_status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select></div>
        <div class="fg"><label>Tracking Number</label><input name="tracking_number" value="<?= e($o['tracking_number'] ?? '') ?>" placeholder="Included in the shipped email"></div>
        <div class="toggle" style="margin-bottom:14px"><input type="checkbox" name="notify_customer" id="notifyCust" checked> <label for="notifyCust" style="margin:0;text-transform:none;letter-spacing:0;color:var(--cream);font-size:.86rem">Email customer on status change</label></div>
        <button class="btn btn-gold" style="width:100%;justify-content:center">Update Order</button>
        <div class="hint" style="margin-top:10px">Customer emails are sent for <strong style="color:var(--gold)">shipped</strong>, <strong style="color:var(--gold)">delivered</strong> &amp; <strong style="color:var(--gold)">cancelled</strong>.</div>
      </form>
    </div></div>
    <div class="panel"><div class="panel-head"><h2>Customer</h2></div><div class="panel-body" style="font-size:.9rem;line-height:1.9">
      <strong><?= e($o['first_name'].' '.$o['last_name']) ?></strong><br>
      <a href="mailto:<?= e($o['email']) ?>" style="color:var(--gold)"><?= e($o['email']) ?></a><br>
      <a href="https://wa.me/<?= preg_replace('/\D/','',$o['phone']) ?>" style="color:var(--gold)"><?= e($o['phone']) ?></a><br>
      <span class="muted"><?= e($o['address']) ?><?= $o['city']?', '.e($o['city']):'' ?><?= $o['state_region']?', '.e($o['state_region']):'' ?><br><?= e($o['country']) ?> <?= e($o['postal_code']) ?></span>
      <?php if($o['note']): ?><hr style="border-color:var(--line-soft);margin:12px 0"><span class="muted"><i class="fa-solid fa-note-sticky"></i> <?= e($o['note']) ?></span><?php endif; ?>
    </div></div>
  </div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
