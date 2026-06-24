<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();

// ---- Manually award / adjust points ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award'])) {
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $points = (int)($_POST['points'] ?? 0);
    $reason = trim($_POST['reason'] ?? '') ?: 'Manual adjustment by admin';
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && $points !== 0) {
        royalty_add($email, $points, $reason);
        a_flash(($points > 0 ? 'Awarded ' : 'Deducted ') . abs($points) . ' points ' . ($points > 0 ? 'to ' : 'from ') . $email . '.');
    } else {
        a_flash('Enter a valid email and a non-zero point amount.');
    }
    redirect('admin/royalty.php' . (!empty($_POST['return']) ? '?view=' . urlencode($email) : ''));
}

$totalMembers = (int)(fetch('SELECT COUNT(*) c FROM royalty_accounts')['c'] ?? 0);
$totalPoints  = (int)(fetch('SELECT COALESCE(SUM(points),0) s FROM royalty_accounts')['s'] ?? 0);
$pointsValue  = $totalPoints * POINTS_VALUE_CAD;

$members = fetchAll('SELECT * FROM royalty_accounts ORDER BY points DESC, created_at DESC');

// Viewing one member's ledger
$viewEmail = strtolower(trim($_GET['view'] ?? ''));
$ledger = $viewEmail ? fetchAll('SELECT * FROM royalty_ledger WHERE email = ? ORDER BY id DESC', [$viewEmail]) : [];
$viewMember = $viewEmail ? fetch('SELECT * FROM royalty_accounts WHERE email = ?', [$viewEmail]) : null;

$title = 'Royalty'; $active = 'royalty';
require __DIR__ . '/inc/layout.php';
?>
<div class="stats">
  <div class="stat"><div class="ic"><i class="fa-solid fa-crown"></i></div><div class="num"><?= number_format($totalMembers) ?></div><div class="lbl">Royalty Members</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-star"></i></div><div class="num"><?= number_format($totalPoints) ?></div><div class="lbl">Points Outstanding</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="num"><?= money($pointsValue) ?></div><div class="lbl">Liability (redeem value)</div></div>
  <div class="stat"><div class="ic"><i class="fa-solid fa-gift"></i></div><div class="num"><?= POINTS_PER_CAD ?>×</div><div class="lbl">Points per <?= money(1) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <!-- Members / ledger -->
  <div class="panel">
    <?php if ($viewMember || $viewEmail): ?>
      <div class="panel-head">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> <?= e($viewMember['name'] ?? $viewEmail) ?></h2>
        <a href="<?= admin_url('royalty.php') ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> All Members</a>
      </div>
      <div class="panel-body" style="border-bottom:1px solid var(--line-soft)">
        <span class="muted"><?= e($viewEmail) ?></span> ·
        <span class="chip gold"><?= number_format((int)($viewMember['points'] ?? 0)) ?> pts</span> ·
        worth <?= money(((int)($viewMember['points'] ?? 0)) * POINTS_VALUE_CAD) ?>
      </div>
      <table>
        <thead><tr><th>Date</th><th>Reason</th><th style="text-align:right">Points</th></tr></thead>
        <tbody>
        <?php if(!$ledger): ?><tr><td colspan="3" class="muted" style="text-align:center;padding:30px">No history for this member.</td></tr><?php endif; ?>
        <?php foreach($ledger as $row): ?>
          <tr>
            <td class="muted" style="font-size:.82rem"><?= e(date('M j, Y g:ia', strtotime($row['created_at']))) ?></td>
            <td><?= e($row['reason']) ?></td>
            <td style="text-align:right;color:<?= $row['points']>=0?'var(--green)':'var(--red)' ?>;font-weight:500"><?= $row['points']>=0?'+':'' ?><?= (int)$row['points'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="panel-head"><h2>Members — Top by Points</h2></div>
      <table>
        <thead><tr><th>#</th><th>Member</th><th>Email</th><th>Points</th><th>Value</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php if(!$members): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:40px">No royalty members yet. Points are earned automatically on paid orders.</td></tr><?php endif; ?>
        <?php foreach($members as $i => $m): ?>
          <tr>
            <td class="muted"><?= $i+1 ?></td>
            <td><strong><?= e($m['name'] ?: '—') ?></strong></td>
            <td><a href="mailto:<?= e($m['email']) ?>" style="color:var(--gold)"><?= e($m['email']) ?></a></td>
            <td><span class="chip gold"><?= number_format((int)$m['points']) ?></span></td>
            <td><?= money(((int)$m['points']) * POINTS_VALUE_CAD) ?></td>
            <td class="muted" style="font-size:.82rem"><?= e(date('M j, Y', strtotime($m['created_at']))) ?></td>
            <td><a href="?view=<?= urlencode($m['email']) ?>" class="btn btn-outline btn-sm">History</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Award points -->
  <div class="panel">
    <div class="panel-head"><h2><i class="fa-solid fa-gift"></i> Award / Adjust Points</h2></div>
    <div class="panel-body">
      <form method="post">
        <?= csrf_field() ?>
        <div class="fg"><label>Customer Email</label><input name="email" type="email" value="<?= e($viewEmail) ?>" required placeholder="customer@email.com"></div>
        <div class="fg"><label>Points (use a negative number to deduct)</label><input name="points" type="number" required placeholder="e.g. 250 or -100"></div>
        <div class="fg"><label>Reason</label><input name="reason" placeholder="Birthday gift, goodwill, correction…"></div>
        <?php if($viewEmail): ?><input type="hidden" name="return" value="1"><?php endif; ?>
        <button class="btn btn-gold" name="award" value="1" style="width:100%;justify-content:center"><i class="fa-solid fa-crown"></i> Apply Points</button>
      </form>
      <div class="muted" style="font-size:.8rem;margin-top:16px;line-height:1.7">
        Members earn <strong style="color:var(--gold)"><?= POINTS_PER_CAD ?></strong> point per <?= money(1) ?> on paid orders, plus a <strong style="color:var(--gold)"><?= POINTS_SIGNUP_BONUS ?></strong>-point welcome bonus. Each point redeems for <?= money(POINTS_VALUE_CAD) ?> (min <?= POINTS_MIN_REDEEM ?>). Tune these in <code style="color:var(--gold)">config/config.php</code>.
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
