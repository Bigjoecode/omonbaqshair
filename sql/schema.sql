-- Omonblaq's Hair — Database schema
-- Engine: MariaDB / MySQL, utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(120) NOT NULL,
    slug         VARCHAR(140) NOT NULL UNIQUE,
    description  TEXT NULL,
    image        VARCHAR(255) NULL,
    parent_id    INT NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    is_featured  TINYINT(1) NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Products  (base price stored in CAD)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT NULL,
    name          VARCHAR(200) NOT NULL,
    slug          VARCHAR(220) NOT NULL UNIQUE,
    sku           VARCHAR(60) NULL,
    short_desc    VARCHAR(300) NULL,
    description   TEXT NULL,
    price         DECIMAL(10,2) NOT NULL DEFAULT 0,
    sale_price    DECIMAL(10,2) NULL,
    stock         INT NOT NULL DEFAULT 0,
    origin        VARCHAR(60) NULL,      -- Brazilian, Peruvian, Raw Indian, Vietnamese...
    texture       VARCHAR(60) NULL,      -- Straight, Body Wave, Deep Wave, Kinky Curly...
    length_inches VARCHAR(120) NULL,     -- comma list of available lengths
    lace_type     VARCHAR(60) NULL,      -- HD Lace, Transparent, 13x4, 13x6, 4x4...
    density       VARCHAR(40) NULL,      -- 150%, 180%, 250%
    color_shade   VARCHAR(60) NULL,      -- Natural Black 1B, #613, Ombre...
    rating        DECIMAL(3,2) NOT NULL DEFAULT 5.0,
    review_count  INT NOT NULL DEFAULT 0,
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
    is_new        TINYINT(1) NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    badge         VARCHAR(40) NULL,      -- e.g. "Best Seller", "Limited"
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (category_id),
    INDEX (is_featured),
    INDEX (is_bestseller)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Product images
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    image       VARCHAR(500) NOT NULL,
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Customers
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(80) NULL,
    last_name     VARCHAR(80) NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    phone         VARCHAR(40) NULL,
    password_hash VARCHAR(255) NULL,
    country       VARCHAR(80) NULL,
    city          VARCHAR(80) NULL,
    address       VARCHAR(255) NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(40) NOT NULL UNIQUE,
    customer_id     INT NULL,
    email           VARCHAR(180) NOT NULL,
    first_name      VARCHAR(80) NULL,
    last_name       VARCHAR(80) NULL,
    phone           VARCHAR(40) NULL,
    country         VARCHAR(80) NULL,
    city            VARCHAR(80) NULL,
    state_region    VARCHAR(80) NULL,
    address         VARCHAR(255) NULL,
    postal_code     VARCHAR(30) NULL,
    note            TEXT NULL,
    currency        VARCHAR(8) NOT NULL DEFAULT 'CAD',
    subtotal_cad    DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_cad    DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_cad    DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_cad       DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_charged   DECIMAL(12,2) NOT NULL DEFAULT 0, -- in chosen currency
    coupon_code     VARCHAR(40) NULL,
    payment_method  VARCHAR(40) NOT NULL DEFAULT 'stripe',
    payment_status  VARCHAR(30) NOT NULL DEFAULT 'pending', -- pending, paid, failed, refunded
    status          VARCHAR(30) NOT NULL DEFAULT 'new',      -- new, processing, shipped, delivered, cancelled
    stock_adjusted  TINYINT(1) NOT NULL DEFAULT 0,
    tracking_number VARCHAR(120) NULL,
    stripe_session  VARCHAR(255) NULL,
    stripe_intent   VARCHAR(255) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    product_id  INT NULL,
    name        VARCHAR(200) NOT NULL,
    variant     VARCHAR(120) NULL,
    price_cad   DECIMAL(10,2) NOT NULL,
    qty         INT NOT NULL,
    line_total  DECIMAL(10,2) NOT NULL,
    INDEX (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Coupons / promotions
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(40) NOT NULL UNIQUE,
    type         VARCHAR(20) NOT NULL DEFAULT 'percent', -- percent | fixed
    value        DECIMAL(10,2) NOT NULL,
    min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    expires_at   DATE NULL,
    usage_limit  INT NULL,
    used_count   INT NOT NULL DEFAULT 0,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Reviews
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    author      VARCHAR(120) NOT NULL,
    rating      TINYINT NOT NULL DEFAULT 5,
    title       VARCHAR(160) NULL,
    body        TEXT NULL,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Testimonials (homepage)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonials (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    author      VARCHAR(120) NOT NULL,
    location    VARCHAR(120) NULL,
    rating      TINYINT NOT NULL DEFAULT 5,
    body        TEXT NOT NULL,
    avatar      VARCHAR(255) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Blog
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blog_posts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    slug         VARCHAR(220) NOT NULL UNIQUE,
    excerpt      VARCHAR(400) NULL,
    body         MEDIUMTEXT NULL,
    cover_image  VARCHAR(255) NULL,
    author       VARCHAR(120) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATETIME NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Contact messages
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(180) NOT NULL,
    phone      VARCHAR(40) NULL,
    subject    VARCHAR(200) NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Newsletter
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletter (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(180) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Admin users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(30) NOT NULL DEFAULT 'admin',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Settings (key/value)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(80) PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Chat logs (AI stylist)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    session_id  VARCHAR(64) NOT NULL,
    role        VARCHAR(20) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- CMS pages (About, FAQ, Shipping, Privacy, Terms)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(120) NOT NULL UNIQUE,
    title        VARCHAR(200) NOT NULL,
    eyebrow      VARCHAR(200) NULL,
    cover_image  VARCHAR(255) NULL,
    body         MEDIUMTEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Back-in-stock alerts
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_alerts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    email       VARCHAR(180) NOT NULL,
    is_notified TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_alert (product_id, email),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Omonblaq Royalty — loyalty points
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS royalty_accounts (
    email      VARCHAR(180) PRIMARY KEY,
    name       VARCHAR(160) NULL,
    points     INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS royalty_ledger (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(180) NOT NULL,
    points     INT NOT NULL,
    reason     VARCHAR(160) NOT NULL,
    order_id   INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
