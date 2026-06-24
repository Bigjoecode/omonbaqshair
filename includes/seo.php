<?php
/**
 * SEO helpers — canonical/OG/Twitter meta + JSON-LD structured data.
 * Pages set $pageTitle / $pageDesc / $pageImage / $ogType / $jsonLd BEFORE
 * including header.php; header.php renders everything.
 */
declare(strict_types=1);

/** Absolute URL of the current request. */
function current_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? (parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost');
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
}

/** Canonical URL: current URL without fragment or volatile tracking params. */
function canonical_url(): string
{
    $url   = strtok(current_url(), '#');
    $parts = explode('?', $url, 2);
    if (count($parts) === 1) return $url;
    parse_str($parts[1], $qs);
    foreach (['session_id','trxref','reference','paystack','manual','utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid','_t'] as $drop) {
        unset($qs[$drop]);
    }
    return $qs ? $parts[0] . '?' . http_build_query($qs) : $parts[0];
}

/** Render a JSON-LD <script> tag from an array. */
function jsonld_tag(array $data): string
{
    return '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}

function site_base(): string { return rtrim(BASE_URL, '/'); }

/** All configured social profile URLs (for schema sameAs). */
function seo_socials(): array
{
    return array_values(array_filter([
        social_link('instagram',  SOCIAL_INSTAGRAM),
        social_link('instagram2', SOCIAL_INSTAGRAM2),
        social_link('facebook',   SOCIAL_FACEBOOK),
        social_link('tiktok',     SOCIAL_TIKTOK),
    ]));
}

/* ---- Schema.org node builders (return arrays) ---- */

function ld_organization(): array
{
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        '@id'         => site_base() . '/#organization',
        'name'        => SITE_NAME,
        'url'         => site_base() . '/',
        'logo'        => asset('img/logo.png'),
        'image'       => asset('img/hero-main.jpg'),
        'description' => 'Luxury wigs, bundles, frontals, closures and raw human hair. Serving Canada, Nigeria, the United States, the United Kingdom and across Africa.',
        'email'       => SITE_EMAIL,
        'telephone'   => SITE_PHONE,
        'sameAs'      => seo_socials(),
        'areaServed'  => ['Canada', 'Nigeria', 'United States', 'United Kingdom', 'Africa'],
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => setting('store_address', SITE_ADDRESS),
            'addressLocality' => 'Brampton',
            'addressRegion'   => 'ON',
            'addressCountry'  => 'CA',
        ],
        'contactPoint' => [
            '@type'             => 'ContactPoint',
            'telephone'         => SITE_PHONE,
            'contactType'       => 'customer service',
            'email'             => SITE_EMAIL,
            'areaServed'        => ['CA', 'NG', 'US', 'GB'],
            'availableLanguage' => ['English'],
        ],
    ];
}

function ld_website(): array
{
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => site_base() . '/#website',
        'url'             => site_base() . '/',
        'name'            => SITE_NAME,
        'publisher'       => ['@id' => site_base() . '/#organization'],
        'inLanguage'      => 'en',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => url('shop.php') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function ld_localbusiness(): array
{
    $node = [
        '@context'    => 'https://schema.org',
        '@type'       => 'HealthAndBeautyBusiness',
        '@id'         => site_base() . '/#localbusiness',
        'name'        => SITE_NAME,
        'image'       => asset('img/hero-main.jpg'),
        'logo'        => asset('img/logo.png'),
        'url'         => site_base() . '/',
        'telephone'   => SITE_PHONE,
        'email'       => SITE_EMAIL,
        'priceRange'  => '$$',
        'currenciesAccepted' => 'CAD, USD, NGN',
        'paymentAccepted'    => 'Credit Card, Stripe, Paystack, Bank Transfer',
        'areaServed'  => ['Canada', 'Nigeria', 'United States', 'United Kingdom', 'Africa'],
        'sameAs'      => seo_socials(),
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => setting('store_address', SITE_ADDRESS),
            'addressLocality' => 'Brampton',
            'addressRegion'   => 'ON',
            'addressCountry'  => 'CA',
        ],
    ];
    if ($loc2 = setting('store_location_2', SITE_LOCATION_2)) {
        $node['department'] = [[
            '@type'   => 'HealthAndBeautyBusiness',
            'name'    => SITE_NAME . ' — ' . $loc2,
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $loc2, 'addressLocality' => 'Brampton', 'addressRegion' => 'ON', 'addressCountry' => 'CA'],
        ]];
    }
    return $node;
}

/**
 * Product schema. $images = absolute image URLs.
 */
function ld_product(array $product, array $images): array
{
    $price = (float)($product['sale_price'] ?: $product['price']);
    $node = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product['name'],
        'image'       => array_values($images),
        'description' => $product['short_desc'] ?: mb_substr(strip_tags((string)$product['description']), 0, 280),
        'brand'       => ['@type' => 'Brand', 'name' => SITE_NAME],
        'offers'      => [
            '@type'         => 'Offer',
            'url'           => product_url($product),
            'priceCurrency' => current_currency(),
            'price'         => number_format(convert_price($price), 2, '.', ''),
            'availability'  => ((int)$product['stock'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller'        => ['@id' => site_base() . '/#organization'],
        ],
    ];
    if (!empty($product['sku']))           $node['sku'] = $product['sku'];
    if (!empty($product['category_name'])) $node['category'] = $product['category_name'];
    if ((int)($product['review_count'] ?? 0) > 0) {
        $node['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format((float)$product['rating'], 1),
            'reviewCount' => (int)$product['review_count'],
            'bestRating'  => 5,
        ];
    }
    return $node;
}

/** BreadcrumbList. $items = [['name'=>..,'url'=>..], ...] */
function ld_breadcrumb(array $items): array
{
    $list = [];
    foreach ($items as $i => $it) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $it['name'],
            'item'     => $it['url'],
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

/** BlogPosting / Article schema. */
function ld_article(array $post, string $image): array
{
    $published = date('c', strtotime($post['published_at'] ?: $post['created_at']));
    return [
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => $post['title'],
        'image'            => [$image],
        'datePublished'    => $published,
        'dateModified'     => date('c', strtotime($post['updated_at'] ?? ($post['published_at'] ?: $post['created_at']))),
        'author'           => ['@type' => 'Person', 'name' => $post['author'] ?: SITE_NAME],
        'publisher'        => [
            '@type' => 'Organization',
            'name'  => SITE_NAME,
            'logo'  => ['@type' => 'ImageObject', 'url' => asset('img/logo.png')],
        ],
        'description'      => $post['excerpt'] ?? '',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url('post.php?slug=' . urlencode($post['slug']))],
    ];
}
