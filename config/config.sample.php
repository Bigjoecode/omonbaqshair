<?php
/**
 * Omonblaq's Hair — Global Configuration (SAMPLE / TEMPLATE)
 *
 * Copy this file to `config/config.php` and fill in real values for your
 * environment. `config/config.php` is gitignored so secrets never reach git.
 * Every secret can also be supplied via environment variables (getenv).
 */

declare(strict_types=1);

// ---- Environment ----
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // 'local' | 'production'
define('APP_DEBUG', APP_ENV === 'local');

// ---- Database ----
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');          // XAMPP here may be 3307
define('DB_NAME', getenv('DB_NAME') ?: 'your_db_name');
define('DB_USER', getenv('DB_USER') ?: 'your_db_user');
define('DB_PASS', getenv('DB_PASS') ?: 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// ---- Site identity ----
define('SITE_NAME', "Omonblaq's Hair");
define('SITE_TAGLINE', 'Home of luxury hairs and more');
define('SITE_EMAIL', 'info@omonblaqshair.com');
define('SITE_EMAIL_ORDERS', 'Omonblaqhair@gmail.com');
define('SITE_PHONE', '+1 437 453 3541');
define('SITE_PHONE_RAW', '14374533541');
define('SITE_ADDRESS', 'Sandalwood Pkwy & Bovaird Dr, Brampton, Ontario, Canada');
define('SITE_LOCATION_2', 'Sandalwood / Bovaird, Brampton, ON');
define('SITE_WHATSAPP', '14374533541');

// Social links (admin can override in Settings; these are fallbacks)
define('SOCIAL_INSTAGRAM', 'https://instagram.com/omonblaqhair_canada');
define('SOCIAL_INSTAGRAM2', 'https://instagram.com/omonblaq_hair');
define('SOCIAL_FACEBOOK', 'https://facebook.com/omonblaqs_hairs');
define('SOCIAL_TIKTOK', 'https://tiktok.com/@omonblaq_hair');

// ---- URLs / paths ----
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', getenv('BASE_URL') ?: ($scheme . '://' . $host)); // e.g. https://omonblaqshair.com
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');

// ---- Currency (base prices stored in CAD) ----
define('BASE_CURRENCY', 'CAD');
$GLOBALS['CURRENCIES'] = [
    'CAD' => ['symbol' => '$',  'label' => 'CAD', 'rate' => 1.00,    'stripe' => 'cad'],
    'USD' => ['symbol' => '$',  'label' => 'USD', 'rate' => 0.73,    'stripe' => 'usd'],
    'NGN' => ['symbol' => '₦',  'label' => 'NGN', 'rate' => 1180.00, 'stripe' => 'ngn'],
];

// ---- Payments (Stripe) ----
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_live_REPLACE_ME');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_live_REPLACE_ME');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_REPLACE_ME');

// ---- Payments (Paystack — NGN/USD/GHS/ZAR/KES) ----
define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_live_REPLACE_ME');
define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: 'sk_live_REPLACE_ME');

// ---- Email / SMTP ----
define('MAIL_ENABLED', getenv('MAIL_ENABLED') !== '0');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'mail.omonblaqshair.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'ssl');   // 'ssl' (465) or 'tls' (587)
define('SMTP_USER', getenv('SMTP_USER') ?: 'info@omonblaqshair.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'REPLACE_WITH_MAILBOX_PASSWORD');
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'info@omonblaqshair.com');
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_ADMIN_NOTIFY', getenv('MAIL_ADMIN_NOTIFY') ?: 'Omonblaqhair@gmail.com');

// ---- AI chatbot (Anthropic Claude) — optional ----
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');
define('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001');

// ---- Omonblaq Royalty (loyalty points) ----
define('POINTS_PER_CAD', 1);
define('POINTS_VALUE_CAD', 0.05);
define('POINTS_MIN_REDEEM', 100);
define('POINTS_SIGNUP_BONUS', 100);

// ---- Security ----
define('CSRF_TOKEN_NAME', '_token');

// ---- Error handling ----
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

date_default_timezone_set('America/Toronto');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
