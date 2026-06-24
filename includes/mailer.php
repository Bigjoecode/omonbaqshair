<?php
/**
 * Self-contained SMTP mailer for Omonblaq's Hair (no Composer dependency).
 * Sends HTML email via SSL/TLS SMTP with AUTH LOGIN.
 */
declare(strict_types=1);

/**
 * Low-level SMTP send. Returns true on success; logs and returns false on failure.
 * Never throws — callers can ignore the result so order flow is never blocked.
 *
 * @param array $to  list of recipient emails (or [email => name])
 */
function smtp_send(array $to, string $subject, string $html, ?string $replyTo = null): bool
{
    if (!MAIL_ENABLED) { mail_log('Mail disabled; skipped: ' . $subject); return false; }
    if (!$to) return false;

    $host = SMTP_HOST; $port = SMTP_PORT;
    $transport = (SMTP_SECURE === 'ssl') ? "ssl://$host:$port" : "tcp://$host:$port";

    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $fp = @stream_socket_client($transport, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { mail_log("SMTP connect failed: $errno $errstr"); return false; }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // multiline responses have a '-' as the 4th char; last line has a space
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function (string $c, string $expect) use ($fp, $read): bool {
        fwrite($fp, $c . "\r\n");
        $resp = $read();
        if (strncmp($resp, $expect, strlen($expect)) !== 0) {
            mail_log("SMTP unexpected reply to [" . substr($c, 0, 12) . "]: " . trim($resp));
            return false;
        }
        return true;
    };

    $ok = true;
    $greeting = $read();
    if (strncmp($greeting, '220', 3) !== 0) { mail_log('SMTP no greeting: ' . trim($greeting)); fclose($fp); return false; }

    $ehlo = 'omonblaqshair.com';
    $ok = $ok && $cmd("EHLO $ehlo", '250');

    // STARTTLS upgrade if using tls/587
    if (SMTP_SECURE === 'tls') {
        $ok = $ok && $cmd('STARTTLS', '220');
        if ($ok && !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            mail_log('STARTTLS handshake failed'); fclose($fp); return false;
        }
        $ok = $ok && $cmd("EHLO $ehlo", '250');
    }

    // AUTH LOGIN
    $ok = $ok && $cmd('AUTH LOGIN', '334');
    $ok = $ok && $cmd(base64_encode(SMTP_USER), '334');
    $ok = $ok && $cmd(base64_encode(SMTP_PASS), '235');

    $ok = $ok && $cmd('MAIL FROM:<' . MAIL_FROM_EMAIL . '>', '250');
    foreach ($to as $k => $v) {
        $addr = is_int($k) ? $v : $k;
        $ok = $ok && $cmd('RCPT TO:<' . $addr . '>', '250');
    }
    $ok = $ok && $cmd('DATA', '354');

    if ($ok) {
        $headers  = 'From: ' . mb_encode_mimeheader(MAIL_FROM_NAME) . ' <' . MAIL_FROM_EMAIL . ">\r\n";
        $toHeader = [];
        foreach ($to as $k => $v) { $toHeader[] = is_int($k) ? $v : "$v <$k>"; }
        $headers .= 'To: ' . implode(', ', $toHeader) . "\r\n";
        if ($replyTo) $headers .= 'Reply-To: ' . $replyTo . "\r\n";
        $headers .= 'Subject: ' . mb_encode_mimeheader($subject) . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Date: ' . date('r') . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        // Dot-stuffing handled by chunk_split of base64 (no leading dots possible)
        $body = chunk_split(base64_encode($html));
        fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
        $resp = $read();
        if (strncmp($resp, '250', 3) !== 0) { mail_log('SMTP DATA not accepted: ' . trim($resp)); $ok = false; }
    }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    if ($ok) mail_log('Sent "' . $subject . '" to ' . implode(',', array_map(fn($k, $v) => is_int($k) ? $v : $k, array_keys($to), $to)));
    return $ok;
}

function mail_log(string $msg): void
{
    $dir = ROOT_PATH . '/storage';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @file_put_contents($dir . '/mail.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
}

/* ----------------------------------------------------------------------------
 * Branded HTML email layout
 * ------------------------------------------------------------------------- */
function email_layout(string $heading, string $bodyHtml, ?string $preheader = null): string
{
    $logo = asset('img/logo.png');
    $year = date('Y');
    $pre  = $preheader ? '<span style="display:none;max-height:0;overflow:hidden;opacity:0">' . e($preheader) . '</span>' : '';
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#0a0908;font-family:Georgia,\'Times New Roman\',serif;color:#f4efe3">' . $pre . '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0908">
<tr><td align="center" style="padding:32px 16px">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#141312;border:1px solid rgba(212,175,55,.2);border-radius:12px;overflow:hidden">
    <tr><td align="center" style="background:linear-gradient(135deg,#a9842a,#d4af37,#f6e6a4,#9c7822);padding:26px">
      <img src="' . $logo . '" alt="Omonblaq\'s Hair" width="150" style="display:block;max-width:150px;height:auto">
    </td></tr>
    <tr><td style="padding:34px 36px 12px">
      <h1 style="margin:0 0 6px;font-size:26px;color:#f4efe3;font-weight:600">' . $heading . '</h1>
      <div style="height:2px;width:60px;background:linear-gradient(90deg,#d4af37,#f6e6a4)"></div>
    </td></tr>
    <tr><td style="padding:8px 36px 34px;font-size:15px;line-height:1.7;color:#cfc6b4;font-family:Arial,Helvetica,sans-serif">'
      . $bodyHtml .
    '</td></tr>
    <tr><td style="padding:22px 36px;border-top:1px solid rgba(255,255,255,.06);font-family:Arial,sans-serif;font-size:12px;color:#867d6c" align="center">
      ' . e(SITE_NAME) . ' &bull; ' . e(SITE_ADDRESS) . '<br>
      <a href="tel:' . e(SITE_PHONE_RAW) . '" style="color:#d4af37;text-decoration:none">' . e(SITE_PHONE) . '</a> &bull;
      <a href="mailto:' . e(SITE_EMAIL) . '" style="color:#d4af37;text-decoration:none">' . e(SITE_EMAIL) . '</a><br>
      <span style="color:#5e564a">&copy; ' . $year . ' ' . e(SITE_NAME) . '. Home of luxury hairs &amp; more.</span>
    </td></tr>
  </table>
</td></tr></table></body></html>';
}

/* ----------------------------------------------------------------------------
 * Specific emails
 * ------------------------------------------------------------------------- */
function email_order_confirmation(array $order, array $items): bool
{
    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr>
            <td style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)">' . e($it['name'])
                . ($it['variant'] ? ' <span style="color:#867d6c">(' . e($it['variant']) . ')</span>' : '')
                . ' &times; ' . (int)$it['qty'] . '</td>
            <td align="right" style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08);color:#d4af37">'
                . money((float)$it['line_total']) . '</td></tr>';
    }
    $cur = $order['currency'] ?? BASE_CURRENCY;
    $body = '
      <p>Hi ' . e($order['first_name'] ?: 'Queen') . ' 👑,</p>
      <p>Thank you for your order with <strong style="color:#f4efe3">' . e(SITE_NAME) . '</strong>! We\'ve received it and are preparing your hair with love. Here are your details:</p>
      <p style="margin:18px 0 6px"><strong style="color:#d4af37">Order ' . e($order['order_number']) . '</strong></p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">' . $rows . '
        <tr><td style="padding:10px 0;color:#867d6c">Subtotal</td><td align="right" style="padding:10px 0">' . money((float)$order['subtotal_cad']) . '</td></tr>'
        . (($order['discount_cad'] ?? 0) > 0 ? '<tr><td style="padding:4px 0;color:#867d6c">Discount</td><td align="right" style="padding:4px 0;color:#d4af37">-' . money((float)$order['discount_cad']) . '</td></tr>' : '') .
        '<tr><td style="padding:4px 0;color:#867d6c">Shipping</td><td align="right" style="padding:4px 0">' . ((float)$order['shipping_cad'] == 0 ? 'FREE' : money((float)$order['shipping_cad'])) . '</td></tr>
        <tr><td style="padding:12px 0;font-size:18px;color:#d4af37;border-top:1px solid rgba(212,175,55,.3)"><strong>Total</strong></td>
            <td align="right" style="padding:12px 0;font-size:18px;color:#d4af37;border-top:1px solid rgba(212,175,55,.3)"><strong>' . money((float)$order['total_cad']) . ' ' . e($cur) . '</strong></td></tr>
      </table>
      <p style="margin-top:22px">We\'ll email you again as soon as your order ships. Questions? Just reply to this email or reach us on '
        . '<a href="https://wa.me/' . e(SITE_WHATSAPP) . '" style="color:#d4af37">WhatsApp</a>.</p>
      <p style="margin-top:18px">With love,<br><strong style="color:#f4efe3">The Omonblaq\'s Hair Team</strong></p>';

    return smtp_send([$order['email']], 'Your Omonblaq\'s Hair order ' . $order['order_number'] . ' is confirmed 👑',
        email_layout('Order Confirmed', $body, 'Thank you for your order ' . $order['order_number']));
}

function email_admin_new_order(array $order, array $items): bool
{
    if (!MAIL_ADMIN_NOTIFY) return false;
    $lines = '';
    foreach ($items as $it) {
        $lines .= '<li>' . e($it['name']) . ($it['variant'] ? ' (' . e($it['variant']) . ')' : '') . ' &times; ' . (int)$it['qty']
                . ' — ' . money((float)$it['line_total']) . '</li>';
    }
    $body = '
      <p>A new order has come in 🎉</p>
      <p><strong style="color:#d4af37">' . e($order['order_number']) . '</strong> — '
        . money((float)$order['total_cad']) . ' ' . e($order['currency']) . ' (' . e($order['payment_status']) . ')</p>
      <p><strong>Customer:</strong> ' . e($order['first_name'] . ' ' . $order['last_name']) . '<br>
         <strong>Email:</strong> ' . e($order['email']) . '<br>
         <strong>Phone:</strong> ' . e($order['phone']) . '<br>
         <strong>Ship to:</strong> ' . e($order['address'] . ', ' . $order['city'] . ', ' . $order['country']) . '</p>
      <ul>' . $lines . '</ul>
      <p><a href="' . url('admin/order-view.php?id=' . (int)$order['id']) . '" style="color:#d4af37">View in admin →</a></p>';
    return smtp_send([MAIL_ADMIN_NOTIFY], 'New order ' . $order['order_number'] . ' — ' . money((float)$order['total_cad']),
        email_layout('New Order', $body), $order['email']);
}

/** Email a customer when their order status changes (shipped / delivered / cancelled). */
function email_order_status(array $order): bool
{
    $status = $order['status'];
    $name   = e($order['first_name'] ?: 'Queen');
    $no     = e($order['order_number']);
    $track  = trim((string)($order['tracking_number'] ?? ''));

    $config = [
        'shipped' => [
            'subject'  => "Your Omonblaq's Hair order $no has shipped 📦",
            'heading'  => 'Your Order Is On Its Way 📦',
            'pre'      => "Order $no has shipped",
            'intro'    => "Exciting news, $name — your order is on its way to you! 👑",
            'extra'    => $track ? '<p style="margin-top:14px">Your tracking number is <strong style="color:#d4af37">' . e($track) . '</strong>.</p>' : '',
        ],
        'delivered' => [
            'subject'  => "Your Omonblaq's Hair order $no has been delivered ✅",
            'heading'  => 'Delivered — Enjoy, Queen! 👑',
            'pre'      => "Order $no delivered",
            'intro'    => "$name, your order has been delivered. We hope you absolutely love your new hair! 💛",
            'extra'    => '<p style="margin-top:14px">We\'d be so grateful if you shared a review or tagged us — it means the world. ✨</p>',
        ],
        'cancelled' => [
            'subject'  => "Your Omonblaq's Hair order $no has been cancelled",
            'heading'  => 'Order Cancelled',
            'pre'      => "Order $no cancelled",
            'intro'    => "Hi $name, your order $no has been cancelled. If this is a surprise or you'd like help, just reply to this email.",
            'extra'    => '',
        ],
    ];
    if (!isset($config[$status])) return false;
    $c = $config[$status];

    $body = '<p>' . $c['intro'] . '</p>' . $c['extra']
        . '<p style="margin-top:20px"><a href="' . url('shop') . '" style="display:inline-block;background:linear-gradient(135deg,#a9842a,#d4af37,#f6e6a4);color:#1a1206;text-decoration:none;padding:13px 28px;border-radius:6px;font-family:Arial,sans-serif;font-weight:bold">Shop Again →</a></p>'
        . '<p style="margin-top:18px">With love,<br><strong style="color:#f4efe3">The Omonblaq\'s Hair Team</strong></p>';

    return smtp_send([$order['email']], $c['subject'], email_layout($c['heading'], $body, $c['pre']));
}

/** Notify everyone waiting on a product that it's back in stock. Returns count sent. */
function notify_back_in_stock(int $productId): int
{
    $p = fetch('SELECT * FROM products WHERE id = ?', [$productId]);
    if (!$p) return 0;
    $waiters = fetchAll('SELECT * FROM stock_alerts WHERE product_id = ? AND is_notified = 0', [$productId]);
    $sent = 0;
    foreach ($waiters as $w) {
        if (email_back_in_stock($w['email'], $p)) {
            q('UPDATE stock_alerts SET is_notified = 1 WHERE id = ?', [$w['id']]);
            $sent++;
        }
    }
    return $sent;
}

function email_back_in_stock(string $toEmail, array $product): bool
{
    $img = product_image((int)$product['id']);
    $body = '
      <p>Great news 💛 — the piece you wanted is back in stock!</p>
      <table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0">
        <tr><td style="padding-right:16px"><img src="' . e($img) . '" width="90" style="border-radius:8px;display:block"></td>
        <td style="vertical-align:middle"><strong style="color:#f4efe3;font-size:17px">' . e($product['name']) . '</strong><br>
            <span style="color:#d4af37;font-size:16px">' . money((float)($product['sale_price'] ?: $product['price'])) . '</span></td></tr>
      </table>
      <p>It tends to sell out fast — secure yours now before it\'s gone again.</p>
      <p><a href="' . e(product_url($product)) . '" style="display:inline-block;margin-top:8px;background:linear-gradient(135deg,#a9842a,#d4af37,#f6e6a4);color:#1a1206;text-decoration:none;padding:13px 28px;border-radius:6px;font-family:Arial,sans-serif;font-weight:bold">Shop Now →</a></p>';
    return smtp_send([$toEmail], 'Back in stock: ' . $product['name'] . ' 👑',
        email_layout('It\'s Back In Stock!', $body, $product['name'] . ' is available again'));
}
