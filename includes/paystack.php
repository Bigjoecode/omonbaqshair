<?php
/**
 * Lightweight Paystack REST helper (no Composer dependency).
 * Uses cURL against api.paystack.co. Supports Initialize + Verify.
 * Paystack settles in NGN, USD, GHS, ZAR, KES — we charge in the closest
 * supported currency (CAD orders are charged in NGN).
 */
declare(strict_types=1);

function paystack_enabled(): bool
{
    return PAYSTACK_SECRET_KEY && !str_contains(PAYSTACK_SECRET_KEY, 'REPLACE_ME');
}

/** Map the shopper's display currency to a Paystack-supported currency. */
function paystack_currency(string $siteCurrency): string
{
    $supported = ['NGN', 'USD', 'GHS', 'ZAR', 'KES'];
    return in_array($siteCurrency, $supported, true) ? $siteCurrency : 'NGN';
}

/** Convert a CAD base amount to the integer subunit (kobo/cents) Paystack expects. */
function paystack_amount(float $cadAmount, string $paystackCurrency): int
{
    return (int) round(convert_price($cadAmount, $paystackCurrency) * 100);
}

function paystack_request(string $endpoint, array $params = [], string $method = 'POST'): array
{
    $url = 'https://api.paystack.co/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
            'Cache-Control: no-cache',
        ],
        CURLOPT_TIMEOUT        => 30,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($params);
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['status' => false, 'message' => $err ?: 'Network error contacting Paystack'];
    }
    $json = json_decode($body, true) ?: [];
    if ($code >= 400 && !isset($json['message'])) {
        $json['message'] = 'Paystack error (HTTP ' . $code . ')';
    }
    return $json;
}

/**
 * Initialize a transaction. Returns the decoded response;
 * on success $res['data']['authorization_url'] is the redirect target.
 */
function paystack_init(array $args): array
{
    return paystack_request('transaction/initialize', $args, 'POST');
}

/** Verify a transaction by reference. Success when $res['data']['status'] === 'success'. */
function paystack_verify(string $reference): array
{
    return paystack_request('transaction/verify/' . urlencode($reference), [], 'GET');
}
