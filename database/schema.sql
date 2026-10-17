-- MethoFresh e-commerce database schema (MySQL 8+ / MariaDB 10.4+)
-- Bilingual products/categories: both English and Bengali stored per row.

CREATE DATABASE IF NOT EXISTS methofresh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE methofresh;

-- ---------------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(30)  DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    city        VARCHAR(120) DEFAULT NULL,
    postal_code VARCHAR(20)  DEFAULT NULL,
    is_admin    TINYINT(1) NOT NULL DEFAULT 0,
    language    VARCHAR(5) NOT NULL DEFAULT 'en',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Categories (bilingual names/descriptions)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_categories (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug           VARCHAR(120) NOT NULL UNIQUE,
    name_en        VARCHAR(120) NOT NULL,
    name_bn        VARCHAR(190) NOT NULL,
    description_en TEXT DEFAULT NULL,
    description_bn TEXT DEFAULT NULL,
    image          VARCHAR(255) DEFAULT NULL,
    active         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Products (bilingual names/descriptions)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_products (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id    INT UNSIGNED NOT NULL,
    slug           VARCHAR(140) NOT NULL UNIQUE,
    sku            VARCHAR(60)  NOT NULL,
    name_en        VARCHAR(160) NOT NULL,
    name_bn        VARCHAR(200) NOT NULL,
    description_en TEXT DEFAULT NULL,
    description_bn TEXT DEFAULT NULL,
    price          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sale_price     DECIMAL(10,2) DEFAULT NULL,
    stock          INT NOT NULL DEFAULT 0,
    weight         DECIMAL(8,3) NOT NULL DEFAULT 0.000,
    image          VARCHAR(255) DEFAULT NULL,
    active         TINYINT(1) NOT NULL DEFAULT 1,
    featured       TINYINT(1) NOT NULL DEFAULT 0,
    new_arrival    TINYINT(1) NOT NULL DEFAULT 0,
    sold           INT NOT NULL DEFAULT 0,
    search_key     TEXT DEFAULT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
      FOREIGN KEY (category_id) REFERENCES wsit_categories(id) ON DELETE CASCADE,
    INDEX idx_products_category_active (category_id, active),
    INDEX idx_products_active (active),
    INDEX idx_products_featured (featured, active)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED DEFAULT NULL,
    order_number    VARCHAR(30) NOT NULL UNIQUE,
    status          ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method  VARCHAR(30) NOT NULL DEFAULT 'cod',
    shipping_name   VARCHAR(120) NOT NULL,
    shipping_email  VARCHAR(190) DEFAULT NULL,
    shipping_phone  VARCHAR(30)  NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city   VARCHAR(120) NOT NULL,
    shipping_postal VARCHAR(20)  DEFAULT NULL,
    shipping_method VARCHAR(120) DEFAULT NULL,
    notes           TEXT DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Order items
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED DEFAULT NULL,
    product_name    VARCHAR(200) NOT NULL,
    product_name_bn VARCHAR(240) DEFAULT NULL,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity        INT NOT NULL DEFAULT 1,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_items_order
      FOREIGN KEY (order_id) REFERENCES wsit_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Site-wide settings (key/value store)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_settings (
    `key`   VARCHAR(60)  NOT NULL PRIMARY KEY,
    `value` TEXT NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- CMS pages (header menu + footer pages, bilingual)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_pages (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug           VARCHAR(140) NOT NULL UNIQUE,
    title_en       VARCHAR(160) NOT NULL,
    title_bn       VARCHAR(200) NOT NULL,
    content_en     TEXT DEFAULT NULL,
    content_bn     TEXT DEFAULT NULL,
    image          VARCHAR(255) DEFAULT NULL,
    menu_order     INT NOT NULL DEFAULT 0,
    active         TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pages_active (active, menu_order)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Contact messages
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_contact_messages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    phone           VARCHAR(30) DEFAULT NULL,
    subject         VARCHAR(200) DEFAULT NULL,
    message         TEXT NOT NULL,
    captcha_answer  VARCHAR(20) NOT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_read (is_read)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Newsletter subscribers (homepage footer "subscribe" form)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_newsletter_subscribers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(190) NOT NULL UNIQUE,
    locale      VARCHAR(5)   NOT NULL DEFAULT 'en',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ns_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Storefront search log (drives the admin dashboard "most searched" widget)
-- One row per search performed on the catalog results page.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_search_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query       VARCHAR(255) NOT NULL,
    query_norm  VARCHAR(255) NOT NULL DEFAULT '',
    locale      VARCHAR(5)  NOT NULL DEFAULT 'en',
    hits        INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_logs_created (created_at),
    INDEX idx_search_logs_query (query)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Checkout funnel (drives the admin dashboard "Checkout funnel" widget)
-- One row per visitor session per 30-minute window (refreshes merge).
-- user_id NULL = unregistered guest; converted = the session later ordered.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wsit_checkout_visits (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED  NULL,
    session_hash  CHAR(64)      NOT NULL,
    ip            VARCHAR(45)   NOT NULL DEFAULT '',
    referrer      VARCHAR(512)  NOT NULL DEFAULT '',
    cart_value    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cart_items    TEXT          NULL,   -- JSON snapshot: [{id,en,bn,qty}, ...]
    converted     TINYINT(1)    NOT NULL DEFAULT 0,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cv_created (created_at),
    INDEX idx_cv_session (session_hash, created_at)
) ENGINE=InnoDB;

-- ===========================================================================
-- SEED DATA
-- ===========================================================================

-- Default admin: admin@methofresh.com / password: admin123 (hash below)
INSERT INTO wsit_users (name, email, password, phone, is_admin) VALUES
('মেঠোফ্রেশ অ্যাডমিন', 'admin@methofresh.com', '$2y$10$mFWTQvPJX4SxW4B3E1nHUeR9UdRRtNOF2iK6nZ2n8OxF6M0jXUqiW', '+8801711111111', 1);

-- Default site settings
INSERT INTO wsit_settings (`key`, `value`) VALUES ('default_locale', 'en')
  ON DUPLICATE KEY UPDATE `key` = `key`;

-- Editable footer / copyright defaults (admin -> Footer page)
INSERT INTO wsit_settings (`key`, `value`) VALUES
('footer_about_en',   'MethoFresh brings pure date palm gur, patali, cold-pressed mustard oil and village spices straight from the producers to your home.'),
('footer_about_bn',   'মেঠোফ্রেশ উৎপাদকের কাছ থেকে সরাসরি আপনার বাড়িতে পৌঁছে দেয় খাঁটি খেজুরের গুড়, পাটালি, ঘানিভাঙা সরিষার তেল ও গ্রামীণ মসলা।'),
('footer_email',      'support@methofresh.com'),
('footer_phone',      '+880 1711-111111'),
('footer_address_en', 'House 12, Road 5, Dhanmondi, Dhaka — Bangladesh'),
('footer_address_bn', 'বাড়ি ১২, রোড ৫, ধানমন্ডি, ঢাকা — বাংলাদেশ'),
('copyright_en',      '© {year} {site_name}. All rights reserved.'),
('copyright_bn',      '© {year} {site_name}। সর্বস্বত্ব সংরক্ষিত।')
  ON DUPLICATE KEY UPDATE `key` = `key`;

-- CMS pages shown in the header menu and footer
INSERT INTO wsit_pages (slug, title_en, title_bn, content_en, content_bn, image, menu_order, active) VALUES
('about', 'About Us', 'আমাদের সম্পর্কে',
 '<p>MethoFresh is a village-to-home delivery service for pure date palm gur, patali, cold-pressed mustard oil and ground spices.</p><p>Every bottle and box travels straight from the gachhi\'s clay pot and the village ghani to your doorstep — no middlemen, no adulteration, just the taste of the winter grove.</p>',
 '<p>মেঠোফ্রেশ একটি গ্রাম-থেকে-ঘরে ডেলিভারি পরিষেবা — খাঁটি খেজুরের গুড়, পাটালি, ঘানিভাঙা সরিষার তেল ও গুঁড়া মসলা তুলে আনে উৎপাদকের কাছ থেকে।</p><p>প্রতিটি বোতল ও বাক্স গাছির মাটির হাঁড়ি আর গ্রামের ঘানি থেকে সোজা পৌঁছে যায় আপনার দরজায় — মাঝখানে কোনো ফড়িয়া নেই, নেই ভেজাল, শুধু শীতের গাছতলার স্বাদ।</p>',
 NULL, 1, 1),
('contact', 'Contact Us', 'যোগাযোগ',
 '<p>Have questions about an order, availability, or delivery? We are here to help.</p><p>Email: support@methofresh.com<br>Phone: +880 1711-111111</p>',
 '<p>অর্ডার, প্রাপ্যতা বা ডেলিভারি নিয়ে কোনো প্রশ্ন আছে? আমরা সাহায্য করতে এখানে আছি।</p><p>ইমেইল: support@methofresh.com<br>ফোন: +৮৮০ ১৭১১-১১১১১১</p>',
 NULL, 2, 1);

INSERT INTO wsit_categories (slug, name_en, name_bn, description_en, description_bn, image) VALUES
('gur',          'Date Palm Gur', 'খেজুরের গুড়', 'Unrefined date palm gur — liquid, dense jhola and premium nolen gur.', 'ভেজালবিহীন খেজুরের গুড় — তরল, ঝোলা ও প্রিমিয়াম নলেন গুড়।', 'gur.svg'),
('patali',       'Patali',        'পাটালি',      'Pressed date palm jaggery cakes, sweetened by the winter dew.', 'শীতের শিশিরে মধুর হয়ে ওঠা, চাপে বসানো খেজুরের পাটালি গুড়।', 'patali.svg'),
('mustard-oil',  'Mustard Oil',   'সরিষার তেল',  'Cold-pressed kachi ghani mustard oil from village ghanis.', 'গ্রামের ঘানিভাঙা খাঁটি সরিষার তেল।', 'mustard-oil.svg'),
('spices',       'Spices',        'মসলা',        'Stone-ground pure spice powders, packed right at the source.', 'উৎপাদকের কাছ থেকে চাক্কিভাঙা খাঁটি গুঁড়া মসলা।', 'spices.svg');

INSERT INTO wsit_products (category_id, slug, sku, name_en, name_bn, description_en, description_bn, price, sale_price, stock, image, active, featured, new_arrival, sold) VALUES
(1, 'liquid-gur-1kg',      'GUR-001', 'Liquid Date Palm Gur (1kg)',  'খেজুরের তরল গুড় (১ কেজি)',  'Smooth liquid nolen gur straight from the clay pot — aged for a deep, earthy sweetness.', 'মাটির হাঁড়ি থেকে সোজা নামিয়ে আনা মসৃণ তরল নলেন গুড় — গভীর, মাটির মিষ্টিতে পাকা।', 450.00, 420.00, 60, 'gur-liquid.svg', 1, 1, 1, 22),
(1, 'jholar-gur-1kg',      'GUR-002', 'Jhola Gur (1kg)',            'ঝোলা গুড় (১ কেজি)',        'Dense, slow-boiled jhola gur — the classic winter breakfast companion for pati-shapta.', 'আস্তে আরেকটু ঘন করে ফোটানো ঝোলা গুড় — পাটিসাপটার অকৃত্রিম সঙ্গী।', 480.00, NULL, 45, 'gur-jhola.svg', 1, 1, 0, 14),
(1, 'nolen-gur-500g',      'GUR-003', 'Nolen Gur (500g)',           'নলেন গুড় (৫০০ গ্রাম)',     'Premium nolen gur, the first boiling of the season — rich, smoky and rare.', 'মৌসুমের প্রথম ফুটের প্রিমিয়াম নলেন গুড় — ঘন, ধোঁয়াটে ও দুর্লভ।', 320.00, 300.00, 40, 'gur-nolen.svg', 1, 1, 1, 18),
(1, 'khajurer-gur-1kg',    'GUR-004', 'Khajurer Gur (1kg)',         'খেজুরের গুড় (১ কেজি)',     'Pure date palm gur in a food-grade jar — no sugar, no lye, nothing added.', 'ফুড-গ্রেড বয়ামে ভেজালবিহীন খেজুরের গুড় — নেই চিনি, নেই চুন, নেই কোনো মেশানো।', 430.00, NULL, 55, 'gur-khajurer.svg', 1, 0, 0, 9),
(2, 'patali-gur-1kg',      'PAT-001', 'Patali Gur (1kg)',           'পাটালি গুড় (১ কেজি)',     'Pressed date palm patali — solid slabs that crack with a clean, honeyed bite.', 'চাপে বসানো খেজুরের পাটালি — শক্ত চকে ভাঙলে ভেতর থেকে ঝরে মধুর স্বাদ।', 420.00, 395.00, 50, 'patali-gur.svg', 1, 1, 1, 20),
(2, 'nolen-patali-500g',   'PAT-002', 'Nolen Patali (500g)',        'নলেন পাটালি (৫০০ গ্রাম)',  'The prized nolen patali — the milder, creamier sister of regular patali.', 'বেশি দামি নলেন পাটালি — সাধারণ পাটালির থেকে নরম ও ক্রিমি।', 285.00, NULL, 35, 'patali-nolen.svg', 1, 0, 0, 7),
(2, 'coconut-patali-500g', 'PAT-003', 'Coconut Patali (500g)',      'নারকেল পাটালি (৫০০ গ্রাম)', 'Patali folded with fresh grated coconut for a festive finish.', 'তাজা নারকেল কোড়ানো মেশানো পাটালি — উৎসবের মুকুট।', 260.00, 240.00, 38, 'patali-coconut.svg', 1, 0, 0, 11),
(3, 'mustard-oil-1l',      'OIL-001', 'Cold-Pressed Mustard Oil (1L)', 'ঘানিভাঙা সরিষার তেল (১ লিটার)', 'Kachi ghani cold-pressed mustard oil, 1 litre — deep colour, bold aroma.', 'কাঁচা ঘানি থেকে ঠান্ডা চাপা খাঁটি সরিষার তেল, ১ লিটার — গাঢ় রং, তীব্র ঘ্রাণ।', 380.00, NULL, 70, 'mustard-oil-1l.svg', 1, 1, 0, 12),
(3, 'mustard-oil-500ml',   'OIL-002', 'Mustard Oil (500ml)',        'সরিষার তেল (৫০০ মি.লি.)', '100% raw mustard oil, pressed in a village ghani — pungent, golden, unrefined.', 'গ্রামের ঘানিতে চাপা ১০০% কাঁচা সরিষার তেল — তীব্র, সোনালি, পরিশোধিত নয়।', 195.00, 185.00, 80, 'mustard-oil-500ml.svg', 1, 0, 1, 16),
(3, 'mustard-oil-2l',      'OIL-003', 'Mustard Oil (2L)',           'সরিষার তেল (২ লিটার)',    'Family-size 2 litre bottle of cold-pressed mustard oil for everyday cooking.', 'দৈনন্দিন রান্নার জন্য ২ লিটারের ফ্যামিলি প্যাক ঘানিভাঙা সরিষার তেল।', 720.00, NULL, 40, 'mustard-oil-2l.svg', 1, 1, 0, 13),
(4, 'turmeric-powder-250g', 'SPC-001', 'Turmeric Powder (250g)',    'খাঁটি হলুদ গুঁড়া (২৫০ গ্রাম)', 'Sun-dried, stone-ground turmeric — brilliant colour, natural aroma.', 'রোদে শুকিয়ে চাক্কিতে ঘুরিয়ে গুঁড়া করা হলুদ — উজ্জ্বল রং, প্রাকৃতিক সুগন্ধ।', 90.00, NULL, 90, 'spice-turmeric.svg', 1, 0, 1, 21),
(4, 'chili-powder-250g',   'SPC-002', 'Red Chili Powder (250g)',    'খাঁটি মরিচ গুঁড়া (২৫০ গ্রাম)', 'Whole red chilies ground to order — no colour, no chilli oil, only heat.', 'পুরো শুকনো মরিচ পিষে বানানো — নেই রং, নেই মরিচের তেল, শুধু ঝাল।', 120.00, 110.00, 85, 'spice-chili.svg', 1, 1, 0, 19),
(4, 'cumin-powder-200g',   'SPC-003', 'Cumin Powder (200g)',        'খাঁটি জিরা গুঁড়া (২০০ গ্রাম)', 'Roasted-to-order cumin powder with a warm, toasty finish.', 'অর্ডার অনুযায়ী ভেজে পিষে নেওয়া জিরা গুঁড়া — গরম ও কষা গন্ধ।', 100.00, NULL, 75, 'spice-cumin.svg', 1, 0, 1, 15),
(4, 'coriander-powder-250g', 'SPC-004', 'Coriander Powder (250g)',  'খাঁটি ধনে গুঁড়া (২৫০ গ্রাম)', 'Fragrant coriander ground in small batches for true khata-dhone taste.', 'ছোট ব্যাচে পিষে নেওয়া সুগন্ধি ধনে গুঁড়া — খাঁটি খাঁটে ধোনের স্বাদ।', 85.00, 78.00, 80, 'spice-coriander.svg', 1, 0, 1, 17),
(4, 'garam-masala-200g',   'SPC-005', 'Garam Masala (200g)',        'খাঁটি গরম মসলা (২০০ গ্রাম)', 'A balanced blend of whole spices, freshly ground and packed at source.', 'উৎপাদকস্থলে সদ্য পিষে মেশানো সিম্ফনি — গরম মসলার সুষম মিশ্রণ।', 180.00, NULL, 60, 'spice-garam.svg', 1, 0, 0, 8),
(4, 'village-masala-combo', 'SPC-006', 'Village Masala Combo',      'গ্রামীণ মসলা সেট',          'A gift-sized set of turmeric, chili, cumin and coriander from one village ghani.', 'এক গ্রামের ঘানি থেকে সংগৃহীত হলুদ, মরিচ, জিরা ও ধনে — উপহারের মতো এক সেট।', 350.00, 320.00, 30, 'spice-set.svg', 1, 1, 1, 12);

-- Sample order history (for admin demo)
INSERT INTO wsit_orders (user_id, order_number, status, subtotal, discount, shipping, total, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address, shipping_city, shipping_postal, notes) VALUES
(1, 'MF-100001', 'delivered', 800.00, 0.00, 0.00, 800.00, 'cod', 'মেঠোফ্রেশ অ্যাডমিন', 'admin@methofresh.com', '+8801711111111', '12 Gulshan Avenue', 'Dhaka', '1212', 'Demo order'),
(1, 'MF-100002', 'processing', 505.00, 0.00, 60.00, 565.00, 'bkash', 'মেঠোফ্রেশ অ্যাডমিন', 'admin@methofresh.com', '+8801711111111', '12 Gulshan Avenue', 'Dhaka', '1212', NULL);

INSERT INTO wsit_order_items (order_id, product_id, product_name, product_name_bn, price, quantity, total) VALUES
(1, 1,  'Liquid Date Palm Gur (1kg)',  'খেজুরের তরল গুড় (১ কেজি)', 420.00, 1, 420.00),
(1, 8,  'Cold-Pressed Mustard Oil (1L)', 'ঘানিভাঙা সরিষার তেল (১ লিটার)', 380.00, 1, 380.00),
(2, 5,  'Patali Gur (1kg)',            'পাটালি গুড় (১ কেজি)',       395.00, 1, 395.00),
(2, 12, 'Red Chili Powder (250g)',     'খাঁটি মরিচ গুঁড়া (২৫০ গ্রাম)', 110.00, 1, 110.00);