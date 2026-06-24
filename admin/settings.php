<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_settings'])) {
    $keys = ['hero_eyebrow','hero_title','hero_subtitle','announcement','free_shipping_threshold','shipping_flat_cad',
             'store_address','store_location_2',
             'social_instagram','social_instagram2','social_facebook','social_tiktok'];
    $set = db()->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    foreach ($keys as $k) $set->execute([$k, trim($_POST[$k] ?? '')]);
    a_flash('Settings saved.'); redirect('admin/settings.php');
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
    $cur=$_POST['current']??''; $new=$_POST['new']??'';
    $u=fetch('SELECT * FROM admin_users WHERE id=?', [admin_user()['id']]);
    if ($u && password_verify($cur,$u['password_hash']) && strlen($new)>=6) {
        q('UPDATE admin_users SET password_hash=? WHERE id=?', [password_hash($new,PASSWORD_DEFAULT), $u['id']]);
        a_flash('Password updated.');
    } else { a_flash('Password change failed — check current password (min 6 chars).'); }
    redirect('admin/settings.php');
}
$title='Settings'; $active='settings';
require __DIR__ . '/inc/layout.php';
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
  <div>
    <div class="panel"><div class="panel-head"><h2>Store Settings</h2></div><div class="panel-body">
      <form method="post">
        <?= csrf_field() ?>
        <div class="fg"><label>Hero Eyebrow (small line above title)</label><input name="hero_eyebrow" value="<?= e(setting('hero_eyebrow','Because you deserve hair that matches your radiance')) ?>"></div>
        <div class="fg"><label>Homepage Hero Title</label><input name="hero_title" value="<?= e(setting('hero_title','Home of luxury hairs & more')) ?>"><div class="hint">The last few words show in gold.</div></div>
        <div class="fg"><label>Homepage Hero Subtitle</label><textarea name="hero_subtitle" style="min-height:70px"><?= e(setting('hero_subtitle')) ?></textarea></div>
        <div class="fg"><label>Announcement Bar</label><input name="announcement" value="<?= e(setting('announcement')) ?>"></div>
        <div class="form-row">
          <div class="fg"><label>Free Shipping Threshold (CAD)</label><input name="free_shipping_threshold" type="number" step="0.01" value="<?= e(setting('free_shipping_threshold','150')) ?>"></div>
          <div class="fg"><label>Flat Shipping (CAD)</label><input name="shipping_flat_cad" type="number" step="0.01" value="<?= e(setting('shipping_flat_cad','15')) ?>"></div>
        </div>
        <h2 style="font-family:var(--serif);font-size:1.2rem;margin:8px 0 14px;color:var(--gold)">Store Locations</h2>
        <div class="fg"><label><i class="fa-solid fa-location-dot"></i> Primary Address</label><input name="store_address" value="<?= e(setting('store_address', SITE_ADDRESS)) ?>" placeholder="Street, City, Province, Country"></div>
        <div class="fg"><label><i class="fa-solid fa-store"></i> Second Location</label><input name="store_location_2" value="<?= e(setting('store_location_2', SITE_LOCATION_2)) ?>" placeholder="e.g. Sandalwood / Bovaird, Brampton, ON"><div class="hint">Leave blank to hide.</div></div>

        <h2 style="font-family:var(--serif);font-size:1.2rem;margin:8px 0 14px;color:var(--gold)">Social Links</h2>
        <div class="fg"><label><i class="fa-brands fa-instagram"></i> Instagram URL (Canada)</label><input name="social_instagram" value="<?= e(setting('social_instagram', SOCIAL_INSTAGRAM)) ?>" placeholder="https://instagram.com/omonblaqhair_canada"></div>
        <div class="fg"><label><i class="fa-brands fa-square-instagram"></i> Instagram URL (2nd)</label><input name="social_instagram2" value="<?= e(setting('social_instagram2', SOCIAL_INSTAGRAM2)) ?>" placeholder="https://instagram.com/omonblaq_hair"></div>
        <div class="fg"><label><i class="fa-brands fa-facebook"></i> Facebook URL</label><input name="social_facebook" value="<?= e(setting('social_facebook', SOCIAL_FACEBOOK)) ?>" placeholder="https://facebook.com/omonblaqs_hairs"></div>
        <div class="fg"><label><i class="fa-brands fa-tiktok"></i> TikTok URL</label><input name="social_tiktok" value="<?= e(setting('social_tiktok', SOCIAL_TIKTOK)) ?>" placeholder="https://tiktok.com/@omonblaq_hair"></div>
        <button class="btn btn-gold" name="save_settings" value="1"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
      </form>
    </div></div>

    <div class="panel"><div class="panel-head"><h2>Payments &amp; Currency</h2></div><div class="panel-body" style="font-size:.9rem;line-height:1.8;color:var(--muted)">
      <p><strong style="color:var(--cream)">Stripe:</strong> add your live keys in <code style="color:var(--gold)">config/config.php</code> (STRIPE_PUBLISHABLE_KEY, STRIPE_SECRET_KEY, STRIPE_WEBHOOK_SECRET). Best for Canada/US/UK cards.</p>
      <p style="margin-top:8px"><strong style="color:var(--cream)">Paystack:</strong> add PAYSTACK_PUBLIC_KEY &amp; PAYSTACK_SECRET_KEY in <code style="color:var(--gold)">config/config.php</code> — ideal for Nigeria &amp; Africa (cards, bank transfer, USSD). Set the webhook to <code style="color:var(--gold)">/api/paystack-webhook.php</code>. When neither gateway is configured, checkout saves orders as <em>manual</em> for you to confirm.</p>
      <p style="margin-top:8px"><strong style="color:var(--cream)">Currencies:</strong> CAD (base), USD, NGN. Adjust exchange rates in <code style="color:var(--gold)">config/config.php</code> → <code>$GLOBALS['CURRENCIES']</code>.</p>
      <p style="margin-top:8px"><strong style="color:var(--cream)">AI Stylist:</strong> set <code style="color:var(--gold)">ANTHROPIC_API_KEY</code> for full Claude-powered chat. A smart product-aware fallback runs without it.</p>
      <p style="margin-top:8px"><strong style="color:var(--cream)">Email:</strong> live via SMTP at <code style="color:var(--gold)"><?= e(SMTP_HOST) ?>:<?= (int)SMTP_PORT ?></code> — order confirmations go to customers, new-order alerts to <code style="color:var(--gold)"><?= e(MAIL_ADMIN_NOTIFY) ?></code>, and back-in-stock emails fire when you restock a sold-out product. Sending log: <code style="color:var(--gold)">storage/mail.log</code>.</p>
    </div></div>
  </div>

  <div class="panel"><div class="panel-head"><h2>Change Password</h2></div><div class="panel-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="fg"><label>Current Password</label><input type="password" name="current" required></div>
      <div class="fg"><label>New Password</label><input type="password" name="new" required minlength="6"></div>
      <button class="btn btn-outline" name="change_password" value="1" style="width:100%;justify-content:center">Update Password</button>
    </form>
    <hr style="border-color:var(--line-soft);margin:18px 0">
    <div style="font-size:.85rem;color:var(--muted)">
      Signed in as <strong style="color:var(--gold)"><?= e(admin_user()['email']) ?></strong>
    </div>
  </div></div>
</div>
<?php require __DIR__ . '/inc/foot.php'; ?>
