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
    image          VARCHAR(255) DEFAULT NULL,
    active         TINYINT(1) NOT NULL DEFAULT 1,
    featured       TINYINT(1) NOT NULL DEFAULT 0,
    new_arrival    TINYINT(1) NOT NULL DEFAULT 0,
    sold           INT NOT NULL DEFAULT 0,
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
('footer_about_en',   'MethoFresh delivers fresh groceries and everyday essentials right to your doorstep, at prices that make sense.'),
('footer_about_bn',   'মেঠোফ্রেশ আপনার দরজায় তাজা সবজি ও প্রতিদিনের প্রয়োজনীয় পণ্য পৌঁছে দেয়, যুক্তিসঙ্গত দামে।'),
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
 '<p>MethoFresh is a fresh grocery delivery service bringing vegetables, fruits, dairy and everyday essentials straight from trusted farms and suppliers to your doorstep.</p><p>We carefully select every item so your family gets the freshest produce at fair prices.</p>',
 '<p>মেঠোফ্রেশ একটি তাজা মুদিখানা ডেলিভারি পরিষেবা — বিশ্বস্ত খামার ও সরবরাহকারীদের থেকে শাকসবজি, ফল, দুধ ও প্রতিদিনের প্রয়োজনীয় জিনিস সরাসরি আপনার দরজায় পৌঁছে দেয়।</p><p>আমরা প্রতিটি পণ্য যত্নসহকারে বেছে নিই, যাতে আপনার পরিবার যুক্তিসঙ্গত দামে সেরা তাজা পণ্য পায়।</p>',
 NULL, 1, 1),
('contact', 'Contact Us', 'যোগাযোগ',
 '<p>Have questions about an order, availability, or delivery? We are here to help.</p><p>Email: support@methofresh.com<br>Phone: +880 1711-111111</p>',
 '<p>অর্ডার, প্রাপ্যতা বা ডেলিভারি নিয়ে কোনো প্রশ্ন আছে? আমরা সাহায্য করতে এখানে আছি।</p><p>ইমেইল: support@methofresh.com<br>ফোন: +৮৮০ ১৭১১-১১১১১১</p>',
 NULL, 2, 1);

INSERT INTO wsit_categories (slug, name_en, name_bn, description_en, description_bn, image) VALUES
('vegetables',   'Vegetables',   'শাকসবজি',   'Fresh seasonal vegetables from local farms.', 'স্থানীয় খামার থেকে তাজা মৌসুমি শাকসবজি।', 'assets/img/cat-vegetables.jpg'),
('fruits',       'Fruits',       'ফল',        'Juicy hand-picked fruits, ripe and ready.',  'পাকা ও সুস্বাদু হাতে বাছাই করা ফল।', 'assets/img/cat-fruits.jpg'),
('dairy',        'Dairy & Eggs', 'দুধ ও ডিম', 'Farm-fresh milk, eggs and dairy products.',   'খামারের তাজা দুধ, ডিম ও দুগ্ধজাত পণ্য।', 'assets/img/cat-dairy.jpg'),
('staples',      'Staples',      'নিত্যদিন',  'Rice, lentils, oil and kitchen essentials.',  'চাল, ডাল, তেল ও রান্নাঘরের নিত্যদিনের জিনিস।', 'assets/img/cat-staples.jpg');

INSERT INTO wsit_products (category_id, slug, sku, name_en, name_bn, description_en, description_bn, price, sale_price, stock, image, active, featured, sold) VALUES
(1, 'fresh-tomatoes',          'VEG-001', 'Fresh Tomatoes',          'তাজা টমেটো',            'Plump, juicy ripe tomatoes. Ideal for salads and curries.', 'রসালো পাকা টমেটো। সালাদ ও তরকারির জন্য উপযুক্ত।', 45.00, 35.00, 120, 'assets/img/tomato.jpg', 1, 1, 340),
(1, 'green-capsicum',          'VEG-002', 'Green Capsicum',          'সবুজ ক্যাপসিকাম',       'Crunchy green capsicum, nutrient packed.', 'কুড়মুড়ে সবুজ ক্যাপসিকাম, পুষ্টিতে ভরপুর।', 60.00, NULL, 80, 'assets/img/capsicum.jpg', 1, 1, 210),
(1, 'red-onion',               'VEG-003', 'Red Onion',               'লাল পেঁয়াজ',            'Sweet red onions, freshly harvested.', 'মিষ্টি স্বাদের লাল পেঁয়াজ, সদ্য খামার থেকে।', 55.00, 50.00, 300, 'assets/img/onion.jpg', 1, 0, 500),
(2, 'mango-himsagar',          'FRU-001', 'Himsagar Mango',          'হিমসাগর আম',             'Smooth, fiber-free sweet Himsagar mango. Bengal favorite.', 'রেশমী, আঁশহীন মিষ্টি হিমসাগর আম। বাংলার প্রিয়।', 220.00, 180.00, 60, 'assets/img/mango.jpg', 1, 1, 420),
(2, 'cavendish-banana',        'FRU-002', 'Cavendish Banana',        'ক্যাভেন্ডিশ কলা',        'Energy-rich Cavendish bananas, bunch fresh.', 'শক্তির উৎস ক্যাভেন্ডিশ কলা, তাজা কাঁদি।', 35.00, NULL, 200, 'assets/img/banana.jpg', 1, 0, 380),
(3, 'farm-eggs-6pcs',          'DAI-001', 'Farm Eggs (6 pcs)',       'ফার্মের ডিম (৬টি)',      'Protein-rich brown farm eggs, 6 pieces tray.', 'প্রোটিনসমৃদ্ধ বাদামি ফার্মের ডিম, ৬টি।', 78.00, 70.00, 150, 'assets/img/eggs.jpg', 1, 1, 460),
(3, 'full-cream-milk-1l',      'DAI-002', 'Full Cream Milk (1L)',    'ফুল ক্রিম দুধ (১ লিটার)', 'Pure full cream pasteurized milk.', 'খাঁটি ফুল ক্রিম পাস্তুরিত দুধ।', 95.00, NULL, 90, 'assets/img/milk.jpg', 1, 0, 300),
(4, 'minicate-rice-5kg',       'STP-001', 'Minicate Rice (5kg)',     'মিনিকেট চাল (৫ কেজি)',  'Aromatic local Minicate rice, 5 kg bag.', 'সুগন্ধি দেশি মিনিকেট চাল, ৫ কেজি ব্যাগ।', 650.00, 620.00, 40, 'assets/img/rice.jpg', 1, 1, 150),
(4, 'red-lentils-1kg',         'STP-002', 'Red Lentils (1kg)',       'মসুর ডাল (১ কেজি)',     'Clean premium red lentils, 1 kg pack.', 'পরিষ্কার প্রিমিয়াম মসুর ডাল, ১ কেজি প্যাক।', 140.00, NULL, 100, 'assets/img/lentils.jpg', 1, 0, 260),
(1, 'carrot',                  'VEG-004', 'Carrots',                 'গাজর',                   'Crunchy orange carrots, rich in vitamin A.', 'কুড়মুড়ে কমলা গাজর, ভিটামিন এ সমৃদ্ধ।', 40.00, 36.00, 95, 'assets/img/carrot.jpg', 1, 1, 290),
(2, 'lychee-bombai',           'FRU-003', 'Bombai Lychee',           'বোম্বাই লিচু',           'Sweet juicy Bombai lychees, premium gift grade.', 'মিষ্টি রসালো বোম্বাই লিচু, প্রিমিয়াম গ্রেড।', 180.00, NULL, 50, 'assets/img/lychee.jpg', 1, 1, 175),
(4, 'mustard-oil-1l',          'STP-003', 'Mustard Oil (1L)',        'সরিষার তেল (১ লিটার)',  'Cold-pressed pungent mustard oil, 1 litre.', 'ঠান্ডা চাপা সরিষার তেল, তীব্র স্বাদ, ১ লিটার।', 210.00, NULL, 70, 'assets/img/oil.jpg', 1, 0, 220),
(1, 'fresh-cucumber',          'VEG-005', 'Fresh Cucumber',          'তাজা সাদফল',             'Crisp, cool cucumbers with tender skin.', 'সুগন্ধি কুরকুরে সাদফল, প্রাচীন ছাল।', 35.00, 28.00, 110, 'assets/img/cucumber.jpg', 1, 1, 185),
(1, 'bottle-gourd',            'VEG-006', 'Bottle Gourd',            'লালার শাক',              'Fresh bottle gourd, tender and mild.', 'তাজা লালার শাক, কোমল ও মাইথুনি।', 48.00, NULL, 75, 'assets/img/bottle-gourd.jpg', 1, 0, 140),
(1, 'cauliflower',             'VEG-007', 'Fresh Cauliflower',       'ফুলকচ্ছপি',            'White cauliflower florets, perfectly firm.', 'সাদা ফুলকচ্ছপির টুকরা, ভালো দৃঢ়।', 70.00, 60.00, 55, 'assets/img/cauliflower.jpg', 1, 1, 210),
(1, 'spinach-palak',           'VEG-008', 'Spinach (Palak)',         'পালংশাক',               'Dark green leafy spinach, iron rich.', 'গাঢ় সবুজ পালংশাক, আয়রণ সমৃদ্ধ।', 40.00, 32.00, 130, 'assets/img/spinach.jpg', 1, 0, 300),
(1, 'broccoli',                'VEG-009', 'Fresh Broccoli',          'ব্রকোলি',                'Nutritious green broccoli crowns, fresh-cut.', 'পুষ্টিকর সবুজ ব্রকোলির মুকুল, তাজা।', 130.00, 95.00, 40, 'assets/img/broccoli.jpg', 1, 1, 165),
(1, 'purple-eggplant',         'VEG-010', 'Purple Eggplant',         'বাংলা বাদাঁকি',         'Glossy purple eggplant, firm and fresh.', 'চমদার বাদামি রংধনু বাদাঁকি, দৃঢ় ও তাজা।', 55.00, NULL, 65, 'assets/img/eggplant.jpg', 1, 0, 190),
(2, 'ripe-papaya',             'FRU-004', 'Ripe Papaya',             ' পাকা থাইটি',           'Sweet ripe papaya, tender flesh.', 'মিষ্টি পাকা থাইটি, কোমল মাংস।', 55.00, 45.00, 50, 'assets/img/papaya.jpg', 1, 1, 130),
(2, 'pineapple',               'FRU-005', 'Fresh Pineapple',         'আনানাস',                'Juicy golden pineapple, crown fresh.', 'রসালো সোনালি আনানাস, স্রোত তাজা।', 110.00, 95.00, 35, 'assets/img/pineapple.jpg', 1, 1, 175),
(2, 'watermelon',              'FRU-006', 'Seedless Watermelon',     ' বীজহীন তরমুজ',        'Cool, refreshing seedless watermelon.', 'শীতল তাজা বীজহীন তরমুজ।', 48.00, 38.00, 45, 'assets/img/watermelon.jpg', 1, 0, 220),
(2, 'guava',                   'FRU-007', 'Pink Guava',              'গুয়াবা',                'Sweet-tart pink guava, fragrant and tasty.', 'মিষ্টি-টার্ট গুলাবি গুয়াবা, সুগন্ধি ও স্বাদিষ্ট।', 90.00, 75.00, 60, 'assets/img/guava.jpg', 1, 0, 160),
(2, 'red-apple',               'FRU-008', 'Red Apple',               'রেড আপেল',             'Crisp red apples, sweet and juicy.', 'কুড়মুড়ে লাল আপেল, মিষ্টি ও রসালো।', 200.00, 180.00, 40, 'assets/img/apple.jpg', 1, 0, 110),
(2, 'strawberry',              'FRU-009', 'Fresh Strawberries',      'স্ট্রবেরি',            'Plump red strawberries, farm fresh.', 'গোলমেল লাল স্ট্রবেরি, ফার্ম তাজা।', 260.00, NULL, 25, 'assets/img/strawberry.jpg', 1, 1, 145),
(3, 'plain-yogurt-500g',       'DAI-003', 'Plain Yogurt (500g)',     'সাদা দই (৫০০ গ্রা)',    'Creamy plain yogurt, probiotic rich.', 'ক্রিমি সাদা দই, প্রবায়োটিক সমৃদ্ধ।', 48.00, 42.00, 85, 'assets/img/yogurt.jpg', 1, 1, 195),
(3, 'salted-butter-500g',      'DAI-004', 'Salted Butter (500g)',     'লবণযুক্ত বাটার (৫০০ গ্রা)', 'Premium salted butter, creamy texture.', 'প্রিমিয়াম লবণযুক্ত বাটার, ক্রিমি টেক্সচার।', 200.00, NULL, 45, 'assets/img/butter.jpg', 1, 0, 135),
(3, 'pure-ghee-500ml',         'DAI-005', 'Pure Ghee (500ml)',       'খাঁটি ঘি (৫০০ মিলি)',  'Aromatic pure ghee, clarified butter.', 'সুগন্ধি খাঁটি ঘি, স্পষ্ট বাটার।', 300.00, 280.00, 40, 'assets/img/ghee.jpg', 1, 1, 275),
(3, 'cheddar-cheese-200g',     'DAI-006', 'Cheddar Cheese (200g)',    'চেডার চিজ (২০০ গ্রা)',  'Aged cheddar cheese, sharp flavor.', 'প্রৌঢ় চেডার চিজ, তীব্র স্বাদ।', 280.00, NULL, 25, 'assets/img/cheese.jpg', 1, 0, 115),
(4, 'basmati-rice-5kg',        'STP-004', 'Basmati Rice (5kg)',      'বাসমতী চাল (৫ কেজি)',  'Premium long-grain aromatic basmati rice.', 'প্রিমিয়াম দীর্ঘ শস্য সুগন্ধি বাসমতী চাল।', 950.00, 880.00, 35, 'assets/img/basmati-rice.jpg', 1, 1, 290),
(4, 'white-sugar-2kg',         'STP-005', 'White Sugar (2kg)',       'সাদা চিনি (২ কেজি)',  'Fine granulated white sugar, 2 kg pack.', 'ফাইন গ্র্যানুলেটেড সাদা চিনি, ২ কেজি প্যাক।', 110.00, NULL, 75, 'assets/img/sugar.jpg', 1, 0, 225),
(4, 'wheat-flour-5kg',         'STP-006', 'Wheat Flour (5kg)',       'গম ময়া (৫ কেজি)',     'Stone-ground whole wheat flour, 5 kg.', 'স্টোন-গ্রাউন্ড পূর্ণ গম ময়া, ৫ কেজি।', 280.00, 250.00, 60, 'assets/img/flour.jpg', 1, 0, 180),
(4, 'veggie-noodles-4pack',    'STP-007', 'Veggie Noodles (4 pack)',  'সবুজি নুডলস (৪ প্যাক)', 'Quick-cook vegetable noodles, 4-pack.', 'দ্রুত রান্নার সবুজি নুডলস, ৪ প্যাক।', 135.00, NULL, 90, 'assets/img/noodles.jpg', 1, 1, 155),
(4, 'black-tea-250g',          'STP-008', 'Black Tea (250g)',        'কালো চা (২৫০ গ্রা)',   'Premium Ceylon black tea leaves.', 'প্রিমিয়াম সিলোন কালো চা পাতা।', 120.00, 105.00, 70, 'assets/img/tea.jpg', 1, 0, 240);

-- Sample order history (for admin demo)
INSERT INTO wsit_orders (user_id, order_number, status, subtotal, discount, shipping, total, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address, shipping_city, shipping_postal, notes) VALUES
(1, 'MF-100001', 'delivered', 165.00, 0.00, 0.00, 165.00, 'cod', 'মেঠোফ্রেশ অ্যাডমিন', 'admin@methofresh.com', '+8801711111111', '12 Gulshan Avenue', 'Dhaka', '1212', 'Demo order'),
(1, 'MF-100002', 'processing', 280.00, 0.00, 60.00, 340.00, 'bkash', 'মেঠোফ্রেশ অ্যাডমিন', 'admin@methofresh.com', '+8801711111111', '12 Gulshan Avenue', 'Dhaka', '1212', NULL);

INSERT INTO wsit_order_items (order_id, product_id, product_name, product_name_bn, price, quantity, total) VALUES
(1, 1, 'Fresh Tomatoes', 'তাজা টমেটো', 35.00, 3, 105.00),
(1, 2, 'Green Capsicum', 'সবুজ ক্যাপসিকাম', 60.00, 1, 60.00),
(2, 4, 'Himsagar Mango', 'হিমসাগর আম', 180.00, 1, 180.00),
(2, 3, 'Red Onion', 'লাল পেঁয়াজ', 50.00, 2, 100.00);