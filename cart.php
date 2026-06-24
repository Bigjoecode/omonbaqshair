<?php
require_once __DIR__ . '/includes/header.php';
$pageTitle = 'Your Bag — ' . SITE_NAME;
$detail = cart_detailed();
$threshold = (float)setting('free_shipping_threshold', '150');
$shipFlat  = (float)setting('shipping_flat_cad', '15');
$shipping  = ($detail['subtotal'] >= $threshold || $detail['subtotal'] == 0) ? 0 : $shipFlat;
$total = $detail['subtotal'] + $shipping;
?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Bag</div>
    <h1>Your Bag</h1>
  </div>
</section>

<section class="section" style="padding-top:40px">
  <div class="container">
    <?php if (!$detail['lines']): ?>
      <div class="text-center" style="padding:60px 0">
        <i class="fa-solid fa-bag-shopping" style="font-size:3rem;color:var(--line);margin-bottom:20px"></i>
        <h3>Your bag is empty</h3>
        <p class="muted mt-1">Discover luxury hair made for your crown.</p>
        <a href="<?= url('shop.php') ?>" class="btn btn-gold mt-3">Start Shopping</a>
      </div>
    <?php else: ?>
      <div class="grid-2 stack-mobile" style="grid-template-columns:1.6fr 1fr;align-items:start;gap:40px">
        <div>
          <?php foreach ($detail['lines'] as $l): ?>
            <div class="cart-line" data-key="<?= e($l['key']) ?>" style="padding:22px 0">
              <img src="<?= e($l['image']) ?>" alt="<?= e($l['product']['name']) ?>">
              <div class="info">
                <h5 style="font-size:1.2rem"><?= e($l['product']['name']) ?></h5>
                <?php if ($l['variant']): ?><div class="var"><?= e($l['variant']) ?></div><?php endif; ?>
                <div style="color:var(--gold);margin-top:6px"><?= money($l['price']) ?> each</div>
                <div class="qty" style="margin-top:10px">
                  <button data-act="dec">−</button><span><?= (int)$l['qty'] ?></span><button data-act="inc">+</button>
                </div>
              </div>
              <div style="text-align:right">
                <div style="color:var(--gold);font-size:1.1rem"><?= money($l['line_total']) ?></div>
                <button data-act="rm" style="color:var(--muted-2);font-size:.8rem;margin-top:12px"><i class="fa-solid fa-trash"></i> Remove</button>
              </div>
            </div>
          <?php endforeach; ?>
          <a href="<?= url('shop.php') ?>" class="btn btn-ghost mt-3"><i class="fa-solid fa-arrow-left-long"></i> Continue Shopping</a>
        </div>

        <div class="filters" style="position:sticky;top:110px">
          <h3 style="font-size:1.5rem;margin-bottom:20px">Order Summary</h3>
          <div class="cart-foot" style="border:none;padding:0">
            <div class="row"><span class="muted">Subtotal</span><span><?= money($detail['subtotal']) ?></span></div>
            <div class="row"><span class="muted">Shipping</span><span><?= $shipping == 0 ? '<span style="color:var(--gold)">FREE</span>' : money($shipping) ?></span></div>
            <?php if ($shipping > 0): ?>
              <p class="muted" style="font-size:.82rem;margin-bottom:14px">Add <?= money($threshold - $detail['subtotal']) ?> more for free shipping.</p>
            <?php endif; ?>
            <div class="row total" style="padding-top:14px;border-top:1px solid var(--line)"><span>Total</span><span><?= money($total) ?></span></div>
            <a href="<?= url('checkout.php') ?>" class="btn btn-gold btn-block btn-lg mt-3"><i class="fa-solid fa-lock"></i> Secure Checkout</a>
            <p class="muted text-center" style="font-size:.78rem;margin-top:14px"><i class="fa-solid fa-shield-halved" style="color:var(--gold)"></i> SSL encrypted · Powered by Stripe</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
document.querySelectorAll('.cart-line [data-act]').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const line = btn.closest('.cart-line'); const key = line.dataset.key; const act = btn.dataset.act;
    const map = {inc:'inc',dec:'dec',rm:'remove'};
    await fetch(OMB.base+'/api/cart.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:map[act],key})});
    location.reload();
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
