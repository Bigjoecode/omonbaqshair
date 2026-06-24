<?php
/**
 * Lightweight Stripe REST helper (no Composer dependency).
 * Uses cURL against api.stripe.com. Supports Checkout Sessions.
 */
declare(strict_types=1);

function stripe_enabled(): bool
{
    return STRIPE_SECRET_KEY && !str_contains(STRIPE_SECRET_KEY, 'REPLACE_ME');
}

/**
 * POST form-encoded params to a Stripe endpoint. Supports nested arrays
 * via PHP's http_build_query bracket notation (Stripe accepts this).
 */
function stripe_request(string $endpoint, array $params, string $method = 'POST'): array
{
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($endpoint, '/'));
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER     => ['Stripe-Version: 2024-06-20'],
        CURLOPT_TIMEOUT        => 30,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['__error' => $err ?: 'Network error contacting Stripe'];
    }
    $json = json_decode($body, true) ?: [];
    if ($code >= 400) {
        $json['__error'] = $json['error']['message'] ?? 'Stripe error';
    }
    return $json;
}

/**
 * Create a Stripe Checkout Session for an order.
 * $lines: [['name'=>, 'amount'=>cents(int), 'qty'=>int], ...]
 */
function stripe_create_checkout(array $order, array $lines, string $stripeCurrency, string $successUrl, string $cancelUrl): array
{
    $params = [
        'mode'                 => 'payment',
        'success_url'          => $successUrl,
        'cancel_url'           => $cancelUrl,
        'client_reference_id'  => $order['order_number'],
        'customer_email'       => $order['email'],
        'metadata'             => ['order_number' => $order['order_number'], 'order_id' => (string)$order['id']],
    ];
    $i = 0;
    foreach ($lines as $l) {
        $params["line_items[$i][price_data][currency]"]            = $stripeCurrency;
        $params["line_items[$i][price_data][product_data][name]"]  = $l['name'];
        $params["line_items[$i][price_data][unit_amount]"]         = (int)$l['amount'];
        $params["line_items[$i][quantity]"]                        = (int)$l['qty'];
        $i++;
    }
    return stripe_request('checkout/sessions', $params);
}
