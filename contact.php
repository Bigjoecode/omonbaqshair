<?php
require_once __DIR__ . '/includes/header.php';
$pageTitle = 'Contact Us — ' . SITE_NAME;
$sent = false; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $err = 'Session expired, please try again.'; }
    else {
        $name = trim($_POST['name'] ?? ''); $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $msg = trim($_POST['message'] ?? '');
        if ($name && $email && $msg) {
            q('INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)',
              [$name, $email, trim($_POST['phone'] ?? ''), trim($_POST['subject'] ?? ''), $msg]);
            $sent = true;
        } else { $err = 'Please fill in your name, a valid email and a message.'; }
    }
}
?>
<section class="page-hero"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Contact</div>
  <span class="eyebrow" style="margin-top:14px">We'd Love To Hear From You</span>
  <h1>Get In Touch</h1>
</div></section>

<section class="section" style="padding-top:50px"><div class="container grid-2" style="gap:50px;align-items:start">
  <div class="reveal">
    <h2 style="font-size:2rem;margin-bottom:10px">Talk to a Stylist</h2>
    <div class="divider-gold" style="margin-left:0"></div>
    <p class="muted">Questions about a unit, an order or custom hair? Our team is here for you — in Canada, Nigeria and beyond.</p>
    <ul class="footer-contact" style="margin-top:24px;font-size:1rem">
      <li><i class="fa-solid fa-location-dot"></i><span><?= e(setting('store_address', SITE_ADDRESS)) ?></span></li>
      <?php if ($loc2 = setting('store_location_2', SITE_LOCATION_2)): ?>
      <li><i class="fa-solid fa-store"></i><span><?= e($loc2) ?></span></li>
      <?php endif; ?>
      <li><i class="fa-solid fa-phone"></i><a href="tel:<?= e(SITE_PHONE_RAW) ?>"><?= e(SITE_PHONE) ?></a></li>
      <li><i class="fa-solid fa-envelope"></i><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></li>
      <li><i class="fa-brands fa-whatsapp"></i><a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" target="_blank" rel="noopener">WhatsApp us directly</a></li>
    </ul>
    <div class="socials" style="margin-top:24px">
      <a href="<?= e(social_link('instagram', SOCIAL_INSTAGRAM)) ?>" target="_blank" rel="noopener" aria-label="Instagram @omonblaqhair_canada"><i class="fa-brands fa-instagram"></i></a>
      <a href="<?= e(social_link('instagram2', SOCIAL_INSTAGRAM2)) ?>" target="_blank" rel="noopener" aria-label="Instagram @omonblaq_hair"><i class="fa-brands fa-square-instagram"></i></a>
      <a href="<?= e(social_link('facebook', SOCIAL_FACEBOOK)) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="<?= e(social_link('tiktok', SOCIAL_TIKTOK)) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
      <a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
    </div>
  </div>

  <div class="filters reveal d1">
    <?php if ($sent): ?>
      <div class="alert alert-success">Thank you 💛 Your message is on its way — we'll reply shortly.</div>
    <?php elseif ($err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="form-group"><label>Name</label><input class="form-control" name="name" required></div>
        <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div>
        <div class="form-group"><label>Subject</label><input class="form-control" name="subject"></div>
      </div>
      <div class="form-group"><label>Message</label><textarea class="form-control" name="message" required></textarea></div>
      <button class="btn btn-gold btn-block btn-lg">Send Message</button>
    </form>
  </div>
</div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
