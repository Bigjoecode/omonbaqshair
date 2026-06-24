</main>

<!-- Newsletter -->
<section class="section newsletter">
  <div class="container inner reveal">
    <span class="eyebrow">Join the Royalty</span>
    <h2 class="mt-2">Get 10% off your first order</h2>
    <p class="muted mt-1">Be first to access new arrivals, restocks &amp; private sales. No spam — just luxury.</p>
    <form class="news-form" method="post" action="<?= url('subscribe.php') ?>">
      <?= csrf_field() ?>
      <input type="email" name="email" placeholder="Your email address" required>
      <button type="submit" class="btn btn-gold">Subscribe</button>
    </form>
  </div>
</section>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col footer-about">
        <img src="<?= asset('img/logo.png') ?>" alt="<?= e(SITE_NAME) ?> logo" width="160" height="64" loading="lazy" decoding="async">
        <p>Home of luxury hairs and more. Premium wigs, bundles, frontals, closures &amp; raw hair for every skin tone and every crown. Proudly serving Canada, Nigeria &amp; the world.</p>
        <div class="socials">
          <a href="<?= e(social_link('instagram', SOCIAL_INSTAGRAM)) ?>" target="_blank" rel="noopener" aria-label="Instagram @omonblaqhair_canada"><i class="fa-brands fa-instagram"></i></a>
          <a href="<?= e(social_link('instagram2', SOCIAL_INSTAGRAM2)) ?>" target="_blank" rel="noopener" aria-label="Instagram @omonblaq_hair"><i class="fa-brands fa-square-instagram"></i></a>
          <a href="<?= e(social_link('facebook', SOCIAL_FACEBOOK)) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="<?= e(social_link('tiktok', SOCIAL_TIKTOK)) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          <a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Shop</h4>
        <a href="<?= url('shop.php?category=wigs') ?>">Wigs</a>
        <a href="<?= url('shop.php?category=bundles') ?>">Bundles</a>
        <a href="<?= url('shop.php?category=frontals') ?>">Frontals</a>
        <a href="<?= url('shop.php?category=closures') ?>">Closures</a>
        <a href="<?= url('shop.php?category=raw-hair') ?>">Raw Hair</a>
        <a href="<?= url('build-bundle.php') ?>">Build a Bundle</a>
        <a href="<?= url('shop.php?filter=bestseller') ?>">Best Sellers</a>
      </div>

      <div class="footer-col">
        <h4>Company</h4>
        <a href="<?= url('about.php') ?>">Our Story</a>
        <a href="<?= url('rewards.php') ?>">Omonblaq Royalty</a>
        <a href="<?= url('wishlist.php') ?>">My Wishlist</a>
        <a href="<?= url('blog.php') ?>">Hair Journal</a>
        <a href="<?= url('contact.php') ?>">Contact Us</a>
        <a href="<?= url('shipping.php') ?>">Shipping &amp; Returns</a>
        <a href="<?= url('faq.php') ?>">FAQ</a>
        <a href="<?= url('privacy.php') ?>">Privacy Policy</a>
        <a href="<?= url('terms.php') ?>">Terms &amp; Conditions</a>
      </div>

      <div class="footer-col">
        <h4>Get in Touch</h4>
        <ul class="footer-contact">
          <li><i class="fa-solid fa-location-dot"></i><span><?= e(setting('store_address', SITE_ADDRESS)) ?></span></li>
          <?php if ($loc2 = setting('store_location_2', SITE_LOCATION_2)): ?>
          <li><i class="fa-solid fa-store"></i><span><?= e($loc2) ?></span></li>
          <?php endif; ?>
          <li><i class="fa-solid fa-phone"></i><a href="tel:<?= e(SITE_PHONE_RAW) ?>"><?= e(SITE_PHONE) ?></a></li>
          <li><i class="fa-solid fa-envelope"></i><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></li>
          <li><i class="fa-brands fa-whatsapp"></i><a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <div class="pays">
        <i class="fa-brands fa-cc-visa fa-lg"></i>
        <i class="fa-brands fa-cc-mastercard fa-lg"></i>
        <i class="fa-brands fa-cc-amex fa-lg"></i>
        <i class="fa-brands fa-stripe fa-2x"></i>
        <span style="font-size:.74rem;display:flex;align-items:center;gap:6px"><i class="fa-solid fa-lock" style="color:var(--gold)"></i> Secure SSL Checkout</span>
      </div>
    </div>
  </div>
</footer>

<!-- Floating action -->
<div class="float-stack">
  <button class="fab fab-chat" id="chatToggle" aria-label="AI Stylist"><i class="fa-solid fa-crown"></i></button>
</div>

<!-- AI Stylist chat -->
<div class="chat-panel" id="chatPanel">
  <div class="chat-head">
    <div class="av"><i class="fa-solid fa-crown"></i></div>
    <div>
      <h4>Omon · AI Stylist</h4>
      <small>Online — here to help</small>
    </div>
    <button class="close" id="chatClose"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="chat-body" id="chatBody">
    <div class="chat-msg bot">Hi gorgeous 👑 I'm Omon, your personal hair stylist at Omonblaq's. Tell me your budget, the look you want, or the occasion — and I'll find your perfect hair.</div>
  </div>
  <div class="chat-chips" id="chatChips">
    <button class="chat-chip" data-q="What wig suits a beginner?">Beginner-friendly wig</button>
    <button class="chat-chip" data-q="Best bundles under $200">Bundles under <?= money(200) ?></button>
    <button class="chat-chip" data-q="Help me choose a frontal">Choose a frontal</button>
  </div>
  <form class="chat-input" id="chatForm">
    <input type="text" id="chatText" placeholder="Ask Omon anything..." autocomplete="off">
    <button type="submit" aria-label="Send"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</div>

<script src="<?= asset('js/main.js') ?>?v=8" defer></script>
</body>
</html>
