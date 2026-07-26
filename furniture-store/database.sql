-- =====================================================================
-- FurnishHub - Furniture E-Commerce Database
-- =====================================================================
DROP DATABASE IF EXISTS furniture_store;
CREATE DATABASE furniture_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE furniture_store;

-- ---------------------------------------------------------------------
-- USERS (customers + admins share one table, differentiated by `role`)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ADDRESSES (shipping/billing addresses belonging to a customer)
-- ---------------------------------------------------------------------
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) DEFAULT 'Home',
    recipient_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address_line1 VARCHAR(180) NOT NULL,
    address_line2 VARCHAR(180) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    county VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    country VARCHAR(80) NOT NULL DEFAULT 'Kenya',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES (Sofas, Beds, Dining Tables, Chairs, Storage, Office, ...)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_categories_slug (slug)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCTS (furniture items)
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    sku VARCHAR(60) NOT NULL UNIQUE,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    short_description VARCHAR(500),
    full_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    material VARCHAR(150) DEFAULT NULL,
    color VARCHAR(80) DEFAULT NULL,
    dimensions VARCHAR(150) DEFAULT NULL COMMENT 'e.g. 200cm x 90cm x 85cm (L x W x H)',
    weight_kg DECIMAL(6,2) DEFAULT NULL,
    warranty_months INT DEFAULT 0,
    assembly_required TINYINT(1) NOT NULL DEFAULT 0,
    main_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sales_count INT NOT NULL DEFAULT 0,
    views_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_products_category (category_id),
    INDEX idx_products_slug (slug),
    INDEX idx_products_status (status),
    INDEX idx_products_featured (is_featured),
    FULLTEXT INDEX ft_products_search (title, short_description, full_description)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCT IMAGES (gallery - multiple images per product)
-- ---------------------------------------------------------------------
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCT VARIANTS (optional color/material combinations with their own
-- price adjustment and stock, e.g. "Grey Fabric" vs "Tan Leather")
-- ---------------------------------------------------------------------
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    price_adjustment DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    sku_suffix VARCHAR(30) DEFAULT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variants_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CART ITEMS (persisted cart per logged-in user; guests use PHP session)
-- ---------------------------------------------------------------------
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_cart_line (user_id, product_id, variant_id),
    INDEX idx_cart_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- COUPONS
-- ---------------------------------------------------------------------
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    usage_limit_per_customer INT DEFAULT 1,
    times_used INT NOT NULL DEFAULT 0,
    starts_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupons_code (code)
) ENGINE=InnoDB;

CREATE TABLE coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_coupon_usage_coupon (coupon_id),
    INDEX idx_coupon_usage_user (user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ORDERS
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    shipping_address_id INT DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_id INT DEFAULT NULL,
    order_status ENUM('pending','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
    payment_method VARCHAR(50) DEFAULT NULL,
    transaction_reference VARCHAR(120) DEFAULT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE SET NULL,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (order_status),
    INDEX idx_orders_payment (payment_status)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    product_title VARCHAR(180) NOT NULL COMMENT 'snapshot at time of order',
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PAYMENTS (payment attempts/records - supports multiple gateways)
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    gateway VARCHAR(40) NOT NULL COMMENT 'test, mpesa, stripe, paypal',
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    gateway_reference VARCHAR(150) DEFAULT NULL,
    raw_response TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_payments_order (order_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ORDER STATUS HISTORY (delivery tracking timeline)
-- ---------------------------------------------------------------------
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(40) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_status_history_order (order_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REVIEWS
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL COMMENT 'proves verified purchase',
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_review_per_order_product (order_id, product_id),
    INDEX idx_reviews_product (product_id),
    INDEX idx_reviews_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- WISHLIST
-- ---------------------------------------------------------------------
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_wishlist (user_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NEWSLETTER SUBSCRIBERS
-- ---------------------------------------------------------------------
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTACT MESSAGES
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','replied') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- WEBSITE SETTINGS (key/value store for admin-editable settings)
-- ---------------------------------------------------------------------
CREATE TABLE website_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PASSWORD RESETS
-- ---------------------------------------------------------------------
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_user (user_id)
) ENGINE=InnoDB;

-- =====================================================================
-- SAMPLE DATA
-- =====================================================================

-- Default admin account. Password is "ChangeMe123!" — CHANGE IMMEDIATELY
-- after installation (see README.md). Hash generated with password_hash().
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Store Administrator', 'admin@furnishhub.test',
 '$2y$10$V5m3vT7z8lD1n3o0aQeYQeXk4Zt2p8yGZP0kM9dQe1c8s6r0aXbXK', 'admin');
-- NOTE: The hash above is a placeholder. Generate a real one with:
--   php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
-- and UPDATE the row before going live.

INSERT INTO categories (name, slug, description) VALUES
('Sofas & Couches', 'sofas-couches', 'Comfortable seating for living rooms'),
('Beds', 'beds', 'Beds and bed frames for every bedroom'),
('Dining Tables', 'dining-tables', 'Tables for dining rooms and kitchens'),
('Chairs', 'chairs', 'Accent, dining and office chairs'),
('Storage & Wardrobes', 'storage-wardrobes', 'Wardrobes, cabinets and shelving'),
('Office Furniture', 'office-furniture', 'Desks and office seating');

INSERT INTO products
(category_id, sku, title, slug, short_description, full_description, price, discount_price,
 stock_quantity, material, color, dimensions, weight_kg, warranty_months, assembly_required,
 main_image, status, is_featured)
VALUES
(1, 'SOF-001', 'Nairobi 3-Seater Sofa', 'nairobi-3-seater-sofa',
 'Plush fabric 3-seater sofa with solid wood legs.',
 'The Nairobi 3-Seater Sofa combines comfort and durability with a solid hardwood frame, high-density foam cushions, and a stain-resistant fabric cover. Perfect for family living rooms.',
 45000.00, 39900.00, 12, 'Hardwood frame, polyester fabric', 'Charcoal Grey',
 '210cm x 90cm x 85cm', 55.00, 12, 1, 'assets/images/products/sofa-nairobi.jpg', 'active', 1),
(2, 'BED-001', 'Malindi Queen Bed Frame', 'malindi-queen-bed-frame',
 'Minimalist queen-size bed frame with upholstered headboard.',
 'A minimalist queen-size bed frame featuring an upholstered headboard, reinforced slats, and a low-profile silhouette that suits modern bedrooms.',
 32000.00, NULL, 8, 'Engineered wood, linen upholstery', 'Beige',
 '160cm x 200cm x 100cm', 48.00, 24, 1, 'assets/images/products/bed-malindi.jpg', 'active', 1),
(3, 'TBL-001', 'Kisumu Dining Table (6-Seater)', 'kisumu-dining-table-6-seater',
 'Solid mahogany dining table seating six.',
 'Crafted from solid mahogany, the Kisumu Dining Table seats six comfortably and features a durable matte lacquer finish resistant to scratches and spills.',
 58000.00, 52000.00, 5, 'Solid mahogany', 'Walnut Brown',
 '180cm x 90cm x 76cm', 62.00, 24, 1, 'assets/images/products/table-kisumu.jpg', 'active', 1),
(4, 'CHR-001', 'Eldoret Office Chair', 'eldoret-office-chair',
 'Ergonomic mesh-back office chair with lumbar support.',
 'The Eldoret Office Chair offers breathable mesh backing, adjustable lumbar support, and a smooth-rolling five-star base for all-day comfort.',
 12500.00, NULL, 20, 'Mesh, steel frame', 'Black',
 '65cm x 65cm x 120cm', 14.00, 12, 1, 'assets/images/products/chair-eldoret.jpg', 'active', 0),
(5, 'WRD-001', 'Mombasa 3-Door Wardrobe', 'mombasa-3-door-wardrobe',
 'Spacious 3-door wardrobe with hanging rail and shelves.',
 'This spacious wardrobe offers a full hanging rail, four shelves, and a mirrored door panel, finished in a warm oak veneer.',
 40000.00, 36500.00, 6, 'MDF, oak veneer', 'Oak', '150cm x 60cm x 200cm',
 70.00, 12, 1, 'assets/images/products/wardrobe-mombasa.jpg', 'active', 1),
(6, 'DSK-001', 'Nakuru Writing Desk', 'nakuru-writing-desk',
 'Compact writing desk with two storage drawers.',
 'A compact writing desk suited for home offices, with two soft-close drawers and a durable laminate work surface.',
 18500.00, NULL, 15, 'Particleboard, laminate', 'White', '110cm x 55cm x 75cm',
 22.00, 6, 1, 'assets/images/products/desk-nakuru.jpg', 'active', 0);

INSERT INTO website_settings (setting_key, setting_value) VALUES
('site_name', 'FurnishHub'),
('site_tagline', 'Furniture that fits your life'),
('contact_email', 'hello@furnishhub.test'),
('contact_phone', '+254 700 000000'),
('currency_symbol', 'KSh'),
('shipping_flat_fee', '1500'),
('free_shipping_threshold', '50000');
