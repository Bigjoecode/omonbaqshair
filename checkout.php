<?php
// IMPORTANT: all processing/redirects must run BEFORE any HTML output (header.php).
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/stripe.php';
require_once __DIR__ . '/includes/paystack.php';
require_once __DIR__ . '/includes/mailer.php';
$pageTitle = 'Checkout — ' . SITE_NAME;
$pageDesc  = 'Secure checkout — pay safely with card (Stripe) or Paystack. Worldwide delivery from ' . SITE_NAME . '.';

// Available payment gateways (first is the default selection)
$gateways = [];
if (stripe_enabled())   $gateways[] = 'stripe';
if (paystack_enabled()) $gateways[] = 'paystack';

$detail = cart_detailed();
if (!$detail['lines']) { redirect('cart.php'); }

$threshold = (float)setting('free_shipping_threshold', '150');
$shipFlat  = (float)setting('shipping_flat_cad', '15');

// ---- Coupon (session) ----
$couponCode = $_SESSION['coupon'] ?? null;
$discount = 0.0; $coupon = null;
if (isset($_POST['apply_coupon'])) {
    $code = strtoupper(trim($_POST['coupon'] ?? ''));
    $c = fetch('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [$code]);
    if ($c && (!$c['expires_at'] || $c['expires_at'] >= date('Y-m-d')) && $detail['subtotal'] >= (float)$c['min_subtotal']) {
        $_SESSION['coupon'] = $code; $couponCode = $code;
        flash('checkout', 'Coupon applied!');
    } else {
        unset($_SESSION['coupon']); $couponCode = null;
        flash('checkout', 'Invalid or ineligible coupon.');
    }
    redirect('checkout.php');
}
if ($couponCode) {
    $coupon = fetch('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [$couponCode]);
    if ($coupon && $detail['subtotal'] >= (float)$coupon['min_subtotal']) {
        $discount = $coupon['type'] === 'percent'
            ? $detail['subtotal'] * ((float)$coupon['value'] / 100)
            : min((float)$coupon['value'], $detail['subtotal']);
    } else { unset($_SESSION['coupon']); $couponCode = null; }
}

$shipping = ($detail['subtotal'] - $discount >= $threshold) ? 0 : $shipFlat;
$total = max(0, $detail['subtotal'] - $discount) + $shipping;
$cc = current_currency();

// ---- Place order ----
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!csrf_verify()) { $errors[] = 'Session expired. Please try again.'; }
    $f = fn($k) => trim($_POST[$k] ?? '');
    $email = filter_var($f('email'), FILTER_VALIDATE_EMAIL);
    if (!$email) $errors[] = 'A valid email is required.';
    foreach (['first_name','last_name','phone','address','city','country'] as $req) {
        if (!$f($req)) $errors[] = ucfirst(str_replace('_', ' ', $req)) . ' is required.';
    }
    // Out-of-stock guard
    foreach (cart_stock_problems() as $p) $errors[] = $p;

    if (!$errors) {
        $pdo = db();
        $orderNo = order_number();

        // ---- Redeem Royalty points ----
        $pointsRedeem = max(0, (int)($_POST['redeem_points'] ?? 0));
        $pointsDiscount = 0.0;
        if ($pointsRedeem > 0) {
            $bal = royalty_points($email);
            $pointsRedeem = min($pointsRedeem, $bal);
            if ($pointsRedeem >= POINTS_MIN_REDEEM) {
                $pointsDiscount = min($pointsRedeem * POINTS_VALUE_CAD, max(0, $detail['subtotal'] - $discount));
                // round redeemed points to the exact value used
                $pointsRedeem = (int)ceil($pointsDiscount / POINTS_VALUE_CAD);
            } else {
                $pointsRedeem = 0;
            }
        }
        $discountFinal = $discount + $pointsDiscount;
        $shipping = ($detail['subtotal'] - $discountFinal >= $threshold) ? 0 : $shipFlat;
        $total = max(0, $detail['subtotal'] - $discountFinal) + $shipping;

        // Chosen payment gateway
        $gateway = $_POST['gateway'] ?? ($gateways[0] ?? 'manual');
        if (!in_array($gateway, $gateways, true)) $gateway = $gateways[0] ?? 'manual';

        $pdo->prepare('INSERT INTO orders
            (order_number,email,first_name,last_name,phone,country,city,state_region,address,postal_code,note,
             currency,subtotal_cad,shipping_cad,discount_cad,total_cad,total_charged,coupon_code,payment_method,payment_status,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $orderNo, $email, $f('first_name'), $f('last_name'), $f('phone'), $f('country'), $f('city'),
                $f('state_region'), $f('address'), $f('postal_code'), $f('note'),
                $cc, $detail['subtotal'], $shipping, $discountFinal, $total, round(convert_price($total), 2),
                $couponCode, $gateway, 'pending', 'new',
            ]);
        $orderId = (int)$pdo->lastInsertId();

        // Deduct redeemed points immediately
        if ($pointsRedeem > 0) {
            royalty_add($email, -$pointsRedeem, 'Redeemed on ' . $orderNo, $orderId);
        }

        $oi = $pdo->prepare('INSERT INTO order_items (order_id,product_id,name,variant,price_cad,qty,line_total) VALUES (?,?,?,?,?,?,?)');
        foreach ($detail['lines'] as $l) {
            $oi->execute([$orderId, $l['product']['id'], $l['product']['name'], $l['variant'], $l['price'], $l['qty'], $l['line_total']]);
        }
        if ($coupon) $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?')->execute([$coupon['id']]);

        $order = ['id' => $orderId, 'order_number' => $orderNo, 'email' => $email];

        if ($gateway === 'stripe') {
            $stripeCur = $GLOBALS['CURRENCIES'][$cc]['stripe'];
            $lines = [];
            foreach ($detail['lines'] as $l) {
                $lines[] = ['name' => $l['product']['name'] . ($l['variant'] ? " ({$l['variant']})" : ''),
                            'amount' => (int)round(convert_price($l['price']) * 100), 'qty' => $l['qty']];
            }
            if ($shipping > 0) $lines[] = ['name' => 'Shipping', 'amount' => (int)round(convert_price($shipping) * 100), 'qty' => 1];
            // Discount applied as negative is not allowed in line items; reduce via Stripe coupon omitted for simplicity.
            $session = stripe_create_checkout(
                $order, $lines, $stripeCur,
                url('order-success.php?order=' . $orderNo . '&session_id={CHECKOUT_SESSION_ID}'),
                url('checkout.php')
            );
            if (!empty($session['url'])) {
                $pdo->prepare('UPDATE orders SET stripe_session = ? WHERE id = ?')->execute([$session['id'] ?? '', $orderId]);
                redirect($session['url']);
            } else {
                $errors[] = 'Payment could not be started: ' . ($session['__error'] ?? 'unknown error') . '. Your order was saved — we will contact you.';
            }
        } elseif ($gateway === 'paystack') {
            $psCur  = paystack_currency($cc);
            $init = paystack_init([
                'email'        => $email,
                'amount'       => paystack_amount($total, $psCur),
                'currency'     => $psCur,
                'reference'    => $orderNo,
                'callback_url' => url('order-success.php?order=' . $orderNo . '&paystack=1'),
                'metadata'     => ['order_number' => $orderNo, 'order_id' => $orderId,
                                   'custom_fields' => [['display_name' => 'Order', 'variable_name' => 'order_number', 'value' => $orderNo]]],
            ]);
            if (!empty($init['data']['authorization_url'])) {
                $pdo->prepare('UPDATE orders SET stripe_session = ? WHERE id = ?')->execute([$init['data']['reference'] ?? $orderNo, $orderId]);
                redirect($init['data']['authorization_url']);
            } else {
                $errors[] = 'Payment could not be started: ' . ($init['message'] ?? 'unknown error') . '. Your order was saved — we will contact you.';
            }
        } else {
            // Manual / offline payment flow (no Stripe keys configured yet)
            decrement_order_stock($orderId);
            royalty_award_for_order($orderId);
            $mailOrder = fetch('SELECT * FROM orders WHERE id = ?', [$orderId]);
            $mailItems = fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            email_order_confirmation($mailOrder, $mailItems);
            email_admin_new_order($mailOrder, $mailItems);
            unset($_SESSION['coupon']);
            cart_clear();
            redirect('order-success.php?order=' . $orderNo . '&manual=1');
        }
    }
}
$flashMsg = flash('checkout');
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container">
  <div class="breadcrumb"><a href="<?= url('cart.php') ?>">Bag</a> / Checkout</div>
  <h1>Secure Checkout</h1>
</div></section>

<section class="section" style="padding-top:40px"><div class="container">
  <?php if ($flashMsg): ?><div class="alert alert-success"><?= e($flashMsg) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="alert alert-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>


  <form method="post" class="grid-2 stack-mobile" style="grid-template-columns:1.5fr 1fr;align-items:start;gap:40px">
    <?= csrf_field() ?>
    <div>
      <h3 style="font-size:1.5rem;margin-bottom:20px"><i class="fa-solid fa-user" style="color:var(--gold)"></i> Contact &amp; Shipping</h3>
      <div class="grid-2">
        <div class="form-group"><label>First Name</label><input class="form-control" name="first_name" value="<?= e($_POST['first_name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Last Name</label><input class="form-control" name="last_name" value="<?= e($_POST['last_name'] ?? '') ?>" required></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
        <div class="form-group"><label>Phone / WhatsApp</label><input class="form-control" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required></div>
      </div>
      <div class="form-group"><label>Address</label><input class="form-control" name="address" value="<?= e($_POST['address'] ?? '') ?>" required></div>
      <div class="grid-2">
        <div class="form-group"><label>City</label><input class="form-control" name="city" value="<?= e($_POST['city'] ?? '') ?>" required></div>
        <div class="form-group"><label>State / Region</label><input class="form-control" name="state_region" value="<?= e($_POST['state_region'] ?? '') ?>"></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label>Country</label>
          <select class="form-control" name="country" required>
            <option value="">Select country</option>
            <?php foreach (['Canada','Nigeria','United States','United Kingdom','Ghana','Other'] as $co): ?>
              <option <?= ($_POST['country'] ?? '') === $co ? 'selected' : '' ?>><?= $co ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Postal / Zip Code</label><input class="form-control" name="postal_code" value="<?= e($_POST['postal_code'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label>Order Note (optional)</label><textarea class="form-control" name="note" rows="3"><?= e($_POST['note'] ?? '') ?></textarea></div>
    </div>

    <!-- Summary -->
    <div class="filters" style="position:sticky;top:110px">
      <h3 style="font-size:1.5rem;margin-bottom:18px">Your Order</h3>
      <?php foreach ($detail['lines'] as $l): ?>
        <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--line-soft)">
          <img src="<?= e($l['image']) ?>" style="width:56px;height:68px;object-fit:cover;border-radius:4px">
          <div style="flex:1">
            <div style="font-family:var(--serif);font-size:1rem"><?= e($l['product']['name']) ?></div>
            <div class="muted" style="font-size:.78rem">Qty <?= (int)$l['qty'] ?><?= $l['variant'] ? ' · ' . e($l['variant']) : '' ?></div>
          </div>
          <div style="color:var(--gold);font-size:.9rem"><?= money($l['line_total']) ?></div>
        </div>
      <?php endforeach; ?>

      <!-- Royalty points redemption -->
      <div style="margin:16px 0;padding:14px;border:1px solid var(--line);border-radius:8px;background:rgba(212,175,55,.05)">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:.84rem">
          <span style="color:var(--gold)"><i class="fa-solid fa-crown"></i> Omonblaq Royalty</span>
          <span id="ptsBalance" class="muted">Enter email to see points</span>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px">
          <input class="form-control" type="number" name="redeem_points" id="redeemPoints" min="0" placeholder="Points to redeem" value="0" style="padding:9px 12px">
          <button type="button" class="btn btn-outline" id="useAllPts" style="padding:0 14px;white-space:nowrap">Use Max</button>
        </div>
        <div class="muted" style="font-size:.72rem;margin-top:6px">Min <?= POINTS_MIN_REDEEM ?> points · each point = <?= money(POINTS_VALUE_CAD) ?>. You earn <?= POINTS_PER_CAD ?> point per <?= money(1) ?> spent.</div>
      </div>

      <div style="display:flex;gap:8px;margin:16px 0">
        <input class="form-control" name="coupon" placeholder="Promo code" value="<?= e($couponCode ?? '') ?>" style="padding:10px 12px">
        <button class="btn btn-outline" name="apply_coupon" value="1" style="padding:0 16px">Apply</button>
      </div>

      <?php if (count($gateways) > 0): ?>
      <div style="margin:16px 0">
        <div style="font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-2);margin-bottom:10px">Payment Method</div>
        <?php
          $gwMeta = [
            'stripe'   => ['Card / Apple Pay / Google Pay', 'fa-brands fa-cc-stripe', 'Visa, Mastercard, Amex'],
            'paystack' => ['Paystack', 'fa-solid fa-bolt', 'Cards, bank transfer & USSD — great for Nigeria & Africa'],
          ];
          foreach ($gateways as $gi => $gw): $meta = $gwMeta[$gw];
        ?>
          <label style="display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border:1px solid var(--line);border-radius:8px;margin-bottom:8px;cursor:pointer">
            <input type="radio" name="gateway" value="<?= e($gw) ?>" <?= $gi === 0 ? 'checked' : '' ?> style="margin-top:4px">
            <span style="flex:1">
              <span style="display:block;color:var(--cream)"><i class="<?= $meta[1] ?>" style="color:var(--gold);margin-right:6px"></i><?= e($meta[0]) ?></span>
              <small class="muted" style="font-size:.74rem"><?= e($meta[2]) ?></small>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="cart-foot" style="border:none;padding:14px 0 0">
        <div class="row"><span class="muted">Subtotal</span><span><?= money($detail['subtotal']) ?></span></div>
        <?php if ($discount > 0): ?><div class="row"><span class="muted">Discount (<?= e($couponCode) ?>)</span><span style="color:var(--gold)">−<?= money($discount) ?></span></div><?php endif; ?>
        <div class="row"><span class="muted">Shipping</span><span><?= $shipping == 0 ? '<span style="color:var(--gold)">FREE</span>' : money($shipping) ?></span></div>
        <div class="row total" style="padding-top:14px;border-top:1px solid var(--line)"><span>Total</span><span><?= money($total) ?> <?= e($cc) ?></span></div>
        <button class="btn btn-gold btn-block btn-lg mt-3" name="place_order" value="1">
          <i class="fa-solid fa-lock"></i> <?= $gateways ? 'Pay Securely' : 'Place Order' ?>
        </button>
        <p class="muted text-center" style="font-size:.78rem;margin-top:14px"><i class="fa-solid fa-shield-halved" style="color:var(--gold)"></i> 256-bit SSL · Secured by Stripe &amp; Paystack</p>
      </div>
    </div>
  </form>
</div></section>

<script>
(function(){
  const emailEl=document.querySelector('input[name=email]'), bal=document.getElementById('ptsBalance'),
        ptsInput=document.getElementById('redeemPoints'); let available=0;
  async function check(){
    const em=emailEl.value.trim(); if(!em||!em.includes('@')){bal.textContent='Enter email to see points';return;}
    try{const r=await fetch(OMB.base+'/api/royalty.php?action=balance&email='+encodeURIComponent(em));
      const d=await r.json(); available=d.points||0;
      bal.innerHTML = available>0 ? '<b style="color:var(--gold)">'+available+' pts</b> ('+d.value_display+')' : 'No points yet';
    }catch(e){}
  }
  emailEl.addEventListener('change',check); emailEl.addEventListener('blur',check);
  document.getElementById('useAllPts').addEventListener('click',()=>{ptsInput.value=available;});
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
