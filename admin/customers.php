<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();
// Build customer list from orders (guest checkout) + customers table
$rows = fetchAll("SELECT email, MAX(CONCAT(first_name,' ',last_name)) name, MAX(phone) phone, MAX(country) country,
                  COUNT(*) orders, SUM(total_cad) spent, MAX(created_at) last_order
                  FROM orders GROUP BY email ORDER BY spent DESC");
$title='Customers'; $active='customers';
require __DIR__ . '/inc/layout.php';
?>
<div class="panel">
  <div class="panel-head"><h2><?= count($rows) ?> Customers</h2></div>
  <table>
    <thead><tr><th>Customer</th><th>Contact</th><th>Country</th><th>Orders</th><th>Total Spent</th><th>Last Order</th></tr></thead>
    <tbody>
    <?php if(!$rows): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:40px">No customers yet.</td></tr><?php endif; ?>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><strong><?= e(trim($r['name']) ?: 'Guest') ?></strong></td>
        <td><a href="mailto:<?= e($r['email']) ?>" style="color:var(--gold)"><?= e($r['email']) ?></a><br><span class="muted" style="font-size:.78rem"><?= e($r['phone']) ?></span></td>
        <td class="muted"><?= e($r['country']) ?></td>
        <td><span class="chip gold"><?= (int)$r['orders'] ?></span></td>
        <td><?= money((float)$r['spent']) ?></td>
        <td class="muted" style="font-size:.82rem"><?= e(date('M j, Y', strtotime($r['last_order']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
