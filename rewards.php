<?php
require_once __DIR__ . '/includes/header.php';
$pageTitle = 'Omonblaq Royalty — ' . SITE_NAME;
$email = strtolower(trim($_GET['email'] ?? ''));
$account = null; $ledger = [];
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $account = fetch('SELECT * FROM royalty_accounts WHERE email = ?', [$email]);
    $ledger = fetchAll('SELECT * FROM royalty_ledger WHERE email = ? ORDER BY id DESC LIMIT 30', [$email]);
}
?>
<section class="page-hero"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Rewards</div>
  <span class="eyebrow" style="margin-top:14px">Loyalty That Feels Like Royalty</span>
  <h1><i class="fa-solid fa-crown" style="color:var(--gold)"></i> Omonblaq Royalty</h1>
</div></section>

<section class="section" style="padding-top:50px"><div class="container" style="max-width:900px">

  <div class="tiles" style="grid-template-columns:repeat(3,1fr);margin-bottom:50px">
    <?php
    $how = [
      ['fa-bag-shopping','Shop &amp; Earn','Earn '.POINTS_PER_CAD.' point for every '.money(1).' you spend — plus '.POINTS_SIGNUP_BONUS.' welcome points on your first order.'],
      ['fa-gift','Redeem','Every point is worth '.money(POINTS_VALUE_CAD).'. Redeem from '.POINTS_MIN_REDEEM.' points at checkout for instant savings.'],
      ['fa-crown','Feel Like Royalty','The more you shop, the more you save — exclusive perks for our loyal queens.'],
    ];
    foreach ($how as $i => $h): ?>
      <div class="testi-card reveal d<?= $i+1 ?>" style="text-align:center">
        <div style="font-size:2.2rem;color:var(--gold);margin-bottom:14px"><i class="fa-solid <?= $h[0] ?>"></i></div>
        <h3 style="margin-bottom:8px"><?= $h[1] ?></h3>
        <p class="muted" style="font-family:var(--sans);font-style:normal;font-size:.92rem"><?= $h[2] ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="filters reveal" style="max-width:560px;margin:0 auto">
    <h2 style="font-size:1.6rem;text-align:center;margin-bottom:8px">Check Your Points</h2>
    <p class="muted text-center" style="margin-bottom:20px">Enter the email you shop with to see your balance.</p>
    <form method="get" style="display:flex;gap:8px">
      <input class="form-control" type="email" name="email" placeholder="you@email.com" value="<?= e($email) ?>" required>
      <button class="btn btn-gold" style="padding:0 22px">Check</button>
    </form>

    <?php if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)): ?>
      <div style="text-align:center;margin-top:28px;padding:28px;border-radius:12px;background:var(--gold-grad);color:#1a1206">
        <div style="font-size:.78rem;letter-spacing:.2em;text-transform:uppercase;opacity:.8">Your Balance</div>
        <div style="font-family:var(--serif);font-size:3rem;line-height:1;margin:6px 0"><?= (int)($account['points'] ?? 0) ?></div>
        <div style="font-size:.9rem">points · worth <?= money(((int)($account['points'] ?? 0)) * POINTS_VALUE_CAD) ?></div>
      </div>
      <?php if ($ledger): ?>
        <h3 style="font-size:1.2rem;margin:28px 0 12px">History</h3>
        <?php foreach ($ledger as $row): ?>
          <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line-soft);font-size:.9rem">
            <span class="muted"><?= e($row['reason']) ?><br><small style="color:var(--muted-2)"><?= e(date('M j, Y', strtotime($row['created_at']))) ?></small></span>
            <span style="color:<?= $row['points']>=0?'var(--gold)':'var(--muted)' ?>;font-weight:500"><?= $row['points']>=0?'+':'' ?><?= (int)$row['points'] ?></span>
          </div>
        <?php endforeach; ?>
      <?php elseif (!$account): ?>
        <p class="muted text-center" style="margin-top:20px">No points yet — place your first order to start earning <?= POINTS_SIGNUP_BONUS ?> welcome points! 👑</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="text-center mt-4"><a href="<?= url('shop.php') ?>" class="btn btn-gold btn-lg">Shop &amp; Earn Points</a></div>
</div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
