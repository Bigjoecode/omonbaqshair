<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/stripe.php';
require_once __DIR__ . '/includes/paystack.php';
require_once __DIR__ . '/includes/mailer.php';
$pageTitle = 'Thank You — ' . SITE_NAME;

$orderNo = $_GET['order'] ?? '';
$order = $orderNo ? fetch('SELECT * FROM orders WHERE order_number = ?', [$orderNo]) : null;

/** Mark an order paid exactly once: decrement stock, award points, email. */
function finalize_paid_order(array &$order, string $intentRef = ''): void
{
    $upd = q('UPDATE orders SET payment_status = ?, status = ?, stripe_intent = ? WHERE id = ? AND payment_status <> ?',
        ['paid', 'processing', $intentRef, $order['id'], 'paid']);
    if ($upd->rowCount() > 0) {
        $order['payment_status'] = 'paid';
        decrement_order_stock((int)$order['id']);
        royalty_award_for_order((int)$order['id']);
        $mailItems = fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
        email_order_confirmation($order, $mailItems);
        email_admin_new_order($order, $mailItems);
        unset($_SESSION['coupon']);
        cart_clear();
    }
    $order['payment_status'] = 'paid';
}

// Confirm Stripe payment if returning from Checkout
if ($order && !empty($_GET['session_id']) && stripe_enabled() && $order['payment_status'] !== 'paid') {
    $sess = stripe_request('checkout/sessions/' . urlencode($_GET['session_id']), [], 'GET');
    if (($sess['payment_status'] ?? '') === 'paid') {
        finalize_paid_order($order, $sess['payment_intent'] ?? '');
    }
}

// Confirm Paystack payment if returning from its checkout
if ($order && !empty($_GET['paystack']) && paystack_enabled() && $order['payment_status'] !== 'paid') {
    $ref = $_GET['reference'] ?? ($_GET['trxref'] ?? $order['order_number']);
    $v = paystack_verify((string)$ref);
    if (($v['data']['status'] ?? '') === 'success') {
        finalize_paid_order($order, (string)($v['data']['reference'] ?? $ref));
    }
}
$items = $order ? fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]) : [];
$manual = !empty($_GET['manual']);
?>
<section class="section" style="padding-top:160px;text-align:center">
  <div class="container" style="max-width:680px">
    <div style="width:90px;height:90px;border-radius:50%;background:var(--gold-grad);display:grid;place-items:center;margin:0 auto 24px;font-size:2.4rem;color:#1a1206">
      <i class="fa-solid fa-crown"></i>
    </div>
    <span class="eyebrow">Order Confirmed</span>
    <h1 style="font-size:clamp(2.2rem,5vw,3.4rem);margin:14px 0">Thank You, Queen 👑</h1>
    <div class="divider-gold"></div>
    <?php if ($order): ?>
      <p class="muted">Your order <strong style="color:var(--gold)"><?= e($order['order_number']) ?></strong> has been received.</p>
      <?php if ($manual): ?>
        <div class="alert" style="background:rgba(212,175,55,.1);border:1px solid var(--gold);color:var(--cream);text-align:left;margin-top:24px">
          Our team will contact you at <strong><?= e($order['email']) ?></strong> shortly to confirm payment and delivery. You can also reach us now on
          <a href="https://wa.me/<?= e(SITE_WHATSAPP) ?>?text=<?= urlencode('Hi, my order is ' . $order['order_number']) ?>" style="color:var(--gold)">WhatsApp</a>.
        </div>
      <?php elseif ($order['payment_status'] === 'paid'): ?>
        <p class="mt-2" style="color:var(--gold)"><i class="fa-solid fa-check-circle"></i> Payment received — we're preparing your hair with love.</p>
      <?php endif; ?>

      <div class="filters" style="text-align:left;margin-top:30px">
        <h3 style="font-size:1.3rem;margin-bottom:16px">Order Summary</h3>
        <?php foreach ($items as $it): ?>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line-soft)">
            <span><?= e($it['name']) ?> <span class="muted">× <?= (int)$it['qty'] ?></span></span>
            <span style="color:var(--gold)"><?= money((float)$it['line_total']) ?></span>
          </div>
        <?php endforeach; ?>
        <div class="row total" style="display:flex;justify-content:space-between;padding-top:14px;margin-top:8px;font-size:1.2rem;color:var(--gold);font-family:var(--serif)">
          <span>Total</span><span><?= money((float)$order['total_cad']) ?></span>
        </div>
      </div>
    <?php else: ?>
      <p class="muted">We couldn't find that order, but your payment may still be processing.</p>
    <?php endif; ?>
    <a href="<?= url('shop.php') ?>" class="btn btn-gold mt-4">Continue Shopping</a>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
