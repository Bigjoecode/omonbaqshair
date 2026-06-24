# Omonblaq's Hair — Luxury Hair E-Commerce

> *Home of luxury hairs and more.* A cinematic black & gold online store for wigs, bundles, frontals, closures and raw hair. Built with **PHP 8 + MariaDB/MySQL** — runs on XAMPP locally and any cPanel/shared host.

---

## ✨ Features

**Storefront**
- Cinematic black & gold design — slow-zoom hero, gold-dust particles, scroll reveals, magnetic CTAs
- Shop with filters (category, texture, origin), search, sort
- Product pages: gallery, length/quantity options, reviews, related products, WhatsApp order button
- AJAX cart drawer + full cart page
- Multi-currency: **CAD · USD · NGN** (auto-converts from a CAD base)
- Secure checkout with **Stripe** *and* **Paystack** (choose a gateway at checkout; graceful manual-order fallback until keys are added)
- Coupons/promo codes, free-shipping threshold
- **Omon** — AI Stylist chatbot (Claude-powered, with a smart product-aware fallback)
- Floating WhatsApp + chat, newsletter, testimonials, blog/journal
- Fully responsive & mobile-friendly
- **Length visualizer + virtual try-on** on product pages (upload a selfie, preview the hair — all client-side)
- **Wishlist** (heart any product; saved on the device) at `/wishlist.php`
- **Back-in-stock alerts** — "Notify me" form on out-of-stock products (admin sees the waitlist)
- **Build-Your-Bundle** configurator at `/build-bundle.php` — pick texture, bundles & lace; auto **10% off** a full set
- **Omonblaq Royalty** loyalty program at `/rewards.php` — earn points per order, redeem at checkout

### Loyalty settings (`config/config.php`)
`POINTS_PER_CAD` (earn rate) · `POINTS_VALUE_CAD` (redeem value) · `POINTS_MIN_REDEEM` · `POINTS_SIGNUP_BONUS`

### Email notifications (SMTP — `config/config.php`)
Self-contained SMTP mailer (no Composer). Configured for cPanel `mail.omonblaqshair.com:465 (SSL)`.
- **Order confirmation** → customer (on manual order placement, or on Stripe `paid`)
- **New-order alert** → `MAIL_ADMIN_NOTIFY`
- **Back-in-stock** → everyone on the waitlist, automatically when you set a sold-out product's stock above 0 in admin
- Keys: `SMTP_HOST/PORT/SECURE/USER/PASS`, `MAIL_FROM_EMAIL`, `MAIL_ADMIN_NOTIFY`, `MAIL_ENABLED`. Send log: `storage/mail.log` (web-blocked).

**Production hardening**
- **Stock decrement** on confirmed/paid orders (idempotent) + **out-of-stock guard** at checkout; sold-out cards show "Sold Out — Notify Me"
- **CSRF protection** across all admin forms (POST tokens) and destructive GET links
- **Static-page CMS** — About, FAQ, Shipping, Privacy & Terms are editable in admin (`pages` table)
- **Privacy Policy & Terms** pages included
- **In-chat add-to-cart** — Omon suggests products with Add-to-Bag buttons
- **Social links** (2× Instagram, Facebook, TikTok) + **store locations** managed in Admin → Settings

**SEO & Performance**
- Per-page **meta titles/descriptions**, canonical URLs, **Open Graph** + **Twitter** cards
- **JSON-LD structured data**: Organization, WebSite (+ Sitelinks SearchBox), LocalBusiness (multi-location), Product (price/availability/rating), BreadcrumbList, BlogPosting
- **XML sitemap** at `/sitemap.xml` (auto-includes products, categories, posts, pages) + dynamic `/robots.txt`
- Clean, **SEO-friendly URLs** (no `.php`), `index,follow` with `noindex` on filtered/search views
- Optimised for **Canada, Nigeria, USA, UK & Africa** (areaServed + locale alternates)
- **Performance**: lazy-loaded/`decoding=async` images, LCP hero `fetchpriority=high`, deferred JS, gzip + far-future asset caching, font/CDN preconnects

### Payment gateways (`config/config.php`)
- **Stripe** — `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` (webhook → `/api/stripe-webhook.php`)
- **Paystack** — `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY` (webhook → `/api/paystack-webhook.php`). Charges in NGN/USD/GHS/ZAR/KES (CAD orders settle in NGN). Ideal for Nigeria & Africa.
- Both verify server-side (return-URL verify **and** signed webhook) and mark orders paid idempotently.

**Admin** (`/admin`)
- Dashboard with revenue, orders, customers, best sellers
- Products (full CRUD + multi-image upload), Categories, Coupons
- Orders (view + update status/payment), Customers
- Blog, Testimonials, Contact messages, Newsletter subscribers
- Store settings (hero text, shipping, password change)

---

## 🚀 Local setup (XAMPP)

1. **Database** is already created (`omonblaqshair`) on MariaDB. To recreate from scratch:
   ```sh
   "c:/xamppnew/mysql/bin/mysql.exe" -u root --port=3307 -e "CREATE DATABASE omonblaqshair CHARACTER SET utf8mb4"
   "c:/xamppnew/mysql/bin/mysql.exe" -u root --port=3307 omonblaqshair < sql/schema.sql
   "c:/xamppnew/php/php.exe" scripts/seed.php
   ```
2. Start **Apache** + **MySQL** in XAMPP.
3. Visit **http://localhost/omonblaqshair.com/**

> Note: this XAMPP's MySQL runs on **port 3307** (set in `config/config.php`).

### Admin login
- URL: `/omonblaqshair.com/admin/`
- Email: `info@omonblaqshair.com` (or `ADMIN_EMAIL`)
- Password: set via `ADMIN_PASSWORD` when running `scripts/seed.php` (printed on seed). **Change it under Settings immediately after first login.**

---

## ⚙️ Configuration — `config/config.php`

| Setting | Purpose |
|---|---|
| `DB_*` | Database credentials (change on cPanel) |
| `BASE_URL` | Auto-detected; override via env if needed |
| `$GLOBALS['CURRENCIES']` | Currency symbols & exchange rates (CAD base) |
| `STRIPE_*` | Add your live/test Stripe keys to enable card payments |
| `ANTHROPIC_API_KEY` | Optional — enables full Claude-powered AI stylist |
| `SITE_*`, `SOCIAL_*` | Brand contact info & social links |

### Enabling Stripe
1. Add `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`.
2. In the Stripe dashboard, add a webhook to `…/api/stripe-webhook.php` for `checkout.session.completed`.
3. Stripe supports CAD, USD and NGN charges (NGN availability depends on your Stripe account).

---

## 🌍 Deploying to cPanel
1. Upload all files to `public_html` (or a subfolder).
2. Create a MySQL DB + user, import `sql/schema.sql`, run the seed (or add products via admin).
3. Update `DB_*` and set `APP_ENV=production` in `config/config.php`.
4. Point the mail/SMTP and Stripe keys to live values.
5. Uncomment the HTTPS redirect block in `.htaccess`.

---

## 📁 Structure
```
config/      DB + global config
includes/    header, footer, helpers, product card, stripe helper
assets/      css, js, img, uploads/products
api/         cart.php, chatbot.php, stripe-webhook.php
admin/       full admin panel (inc/ = layout & auth)
sql/         schema.sql
scripts/     seed.php · seed-pages.php · import-photos.php · import-about.php
*.php        storefront pages (index, shop, product, cart, checkout, …)
```

## 🎨 Brand
- **Colours:** matte black `#0a0908` · gold `#d4af37` → `#f6e6a4` · cream `#f4efe3`
- **Type:** Cormorant Garamond (display) · Jost (UI) · Italianno (script accents)

---
*Built with care for Omonblaq's Hair — Brampton, Ontario 🇨🇦 · serving Nigeria 🇳🇬 & the world.*
