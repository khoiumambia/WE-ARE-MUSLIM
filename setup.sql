CREATE DATABASE muslim;
USE muslim;
-- ============================================
-- DATABASE: muslim
-- Complete Setup with All Tables and Data
-- ============================================

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(50) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `tier` ENUM('Bronze', 'Silver', 'Gold', 'Platinum') DEFAULT 'Bronze',
    `tier_expiry` DATE DEFAULT NULL,
    `total_spent` DECIMAL(12,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. PRODUCTS TABLE (with your uploads images)
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL,
    `brand` VARCHAR(100),
    `fragrance` VARCHAR(100),
    `price` DECIMAL(10,2) NOT NULL,
    `stock` INT DEFAULT 0,
    `description` TEXT,
    `image` VARCHAR(500),
    `ratings` DECIMAL(3,1) DEFAULT 0,
    `reviews` INT DEFAULT 0
);

-- 3. ORDERS TABLE
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20),
    `customer_address` TEXT NOT NULL,
    `city` VARCHAR(50),
    `postal_code` VARCHAR(20),
    `subtotal` DECIMAL(10,2) NOT NULL,
    `discount` DECIMAL(10,2) DEFAULT 0,
    `delivery_charge` DECIMAL(10,2) DEFAULT 60,
    `total` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `transaction_id` VARCHAR(100),
    `tracking_number` VARCHAR(100),
    `payment_confirmed` TINYINT DEFAULT 0,
    `status` VARCHAR(50) DEFAULT 'Processing',
    `coupon_code` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 4. ORDER ITEMS TABLE
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT DEFAULT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `product_price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
);

-- 5. BLOGS TABLE
CREATE TABLE IF NOT EXISTS `blogs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100),
    `author` VARCHAR(100),
    `read_time` INT DEFAULT 5,
    `excerpt` TEXT,
    `content` LONGTEXT,
    `image` VARCHAR(500),
    `status` ENUM('draft', 'published', 'scheduled') DEFAULT 'draft',
    `tags` VARCHAR(255),
    `schedule_date` DATETIME DEFAULT NULL,
    `views` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. BLOG SUBMISSIONS TABLE
CREATE TABLE IF NOT EXISTS `blog_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100),
    `author` VARCHAR(100),
    `read_time` INT DEFAULT 5,
    `excerpt` TEXT,
    `content` LONGTEXT,
    `image` VARCHAR(500),
    `tags` VARCHAR(255),
    `user_email` VARCHAR(100),
    `user_name` VARCHAR(100),
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `processed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. REVIEWS TABLE
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `user_name` VARCHAR(100),
    `user_email` VARCHAR(100),
    `rating` INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    `comment` TEXT,
    `images` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 8. COUPONS TABLE
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `description` TEXT,
    `discount_type` ENUM('percent', 'fixed') DEFAULT 'percent',
    `discount_value` DECIMAL(10,2) NOT NULL,
    `min_order_amount` DECIMAL(10,2) DEFAULT 0,
    `max_discount` DECIMAL(10,2) DEFAULT NULL,
    `usage_limit` INT DEFAULT NULL,
    `used_count` INT DEFAULT 0,
    `user_limit_per_user` INT DEFAULT 1,
    `is_active` TINYINT DEFAULT 1,
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. COUPON USAGE TABLE
CREATE TABLE IF NOT EXISTS `coupon_usage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL,
    `user_email` VARCHAR(100),
    `order_id` INT,
    `discount_amount` DECIMAL(10,2),
    `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
);

-- 10. RETURN REQUESTS TABLE
CREATE TABLE IF NOT EXISTS `return_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` VARCHAR(50) UNIQUE NOT NULL,
    `order_id` VARCHAR(50) NOT NULL,
    `user_email` VARCHAR(100) NOT NULL,
    `type` ENUM('return', 'exchange') NOT NULL,
    `product_id` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `comments` TEXT,
    `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    `admin_response` TEXT,
    `exchange_product_id` INT DEFAULT NULL,
    `exchange_product_name` VARCHAR(200) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processed_at` DATETIME DEFAULT NULL
);

-- 11. CAROUSEL SLIDES TABLE (Homepage banner)
CREATE TABLE IF NOT EXISTS `carousel_slides` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200),
    `subtitle` VARCHAR(500),
    `image` VARCHAR(500) NOT NULL,
    `button_text` VARCHAR(100),
    `button_link` VARCHAR(500),
    `order_index` INT DEFAULT 0,
    `is_active` TINYINT DEFAULT 1
);

-- 12. FEATURES TABLE (Homepage features section)
CREATE TABLE IF NOT EXISTS `features` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `icon` VARCHAR(100),
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `link` VARCHAR(500),
    `order_index` INT DEFAULT 0,
    `is_active` TINYINT DEFAULT 1
);

-- 13. HOMEPAGE CONTENT TABLE (All text content on homepage)
CREATE TABLE IF NOT EXISTS `homepage_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section` VARCHAR(100) UNIQUE NOT NULL,
    `title` VARCHAR(255),
    `subtitle` TEXT,
    `content` TEXT,
    `image` VARCHAR(500),
    `button_text` VARCHAR(100),
    `button_link` VARCHAR(500),
    `order_index` INT DEFAULT 0
);

-- ============================================
-- INSERT DEFAULT DATA (All from uploads folder)
-- ============================================

-- Insert Admin User (password: admin123)
-- First generate hash: echo password_hash('admin123', PASSWORD_DEFAULT);
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `postal_code`, `role`, `tier`, `tier_expiry`, `total_spent`, `created_at`) VALUES
(1, 'Admin User', 'admin@attar.com', '$2y$10$YourHashHere', '+880 1234 567890', 'House #42, Road #12, Banani', 'Dhaka', '1213', 'admin', 'Platinum', DATE_ADD(NOW(), INTERVAL 365 DAY), 150000, NOW());

-- Insert Test User (password: user123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `postal_code`, `role`, `tier`, `tier_expiry`, `total_spent`, `created_at`) VALUES
(2, 'Test Customer', 'user@test.com', '$2y$10$YourHashHere', '+880 1987654321', 'House #15, Road #5, Gulshan', 'Dhaka', '1212', 'user', 'Bronze', DATE_ADD(NOW(), INTERVAL 30 DAY), 0, NOW());

-- Insert Products (using your uploads images)
INSERT INTO `products` (`id`, `name`, `brand`, `fragrance`, `price`, `stock`, `description`, `image`, `ratings`, `reviews`) VALUES
(1, 'Royal Oudh', 'Arabian Oud', 'Oudh', 2990, 50, 'Premium royal oudh attar with long-lasting fragrance. Made from aged Cambodian oudh chips.', 'uploads/Screenshot_2026-05-28_233753.png', 4.8, 12),
(2, 'Musk Al Haramain', 'Harramain', 'Musk', 1890, 75, 'Traditional musk attar that captivates the senses. A blend of white and black musk.', 'uploads/Screenshot_2026-05-28_233339.png', 4.6, 8),
(3, 'Rose Attar', 'Swiss Arabian', 'Rose', 990, 100, 'Pure rose petal attar extracted from Damask roses. Romantic and timeless.', 'uploads/Screenshot_2026-05-28_233510.png', 4.9, 15),
(4, 'Amber Oudh', 'Ajmal', 'Amber', 2490, 40, 'Amber and oudh blend for a warm, sensual experience. Perfect for evenings.', 'uploads/Screenshot_2026-05-28_233406.png', 4.7, 10),
(5, 'Sandalwood Classic', 'Mysore', 'Sandalwood', 1590, 60, 'Pure sandalwood oil from Mysore. Earthy, woody, and meditative.', 'uploads/Screenshot_2026-05-25_181536.png', 4.5, 7),
(6, 'Jasmine Supreme', 'Al Haramain', 'Jasmine', 1290, 85, 'Exquisite jasmine attar that reminds of blooming gardens. Fresh and floral.', 'uploads/Screenshot_2026-05-25_182001.png', 4.8, 9),
(7, 'Oudh Al Misk', 'Abdul Samad Al Qurashi', 'Oudh/Musk', 3590, 30, 'Premium blend of Cambodian oudh and white musk. Long-lasting silage.', 'uploads/Screenshot_2026-05-25_181943.png', 4.9, 14),
(8, 'Saffron Royale', 'Rasasi', 'Saffron', 2190, 45, 'Luxury saffron-infused attar with woody undertones. Unique and bold.', 'uploads/Screenshot_2026-05-28_233839.png', 4.7, 6);

-- Insert Blogs
INSERT INTO `blogs` (`id`, `title`, `category`, `author`, `read_time`, `excerpt`, `content`, `image`, `status`, `tags`, `views`, `created_at`) VALUES
(1, 'The Art of Attar Making: A 5000 Year Old Tradition', 'guide', 'Admin', 8, 'Discover the ancient art of traditional attar making that has been passed down through generations.', '<p>Attar making is an ancient art that has been passed down through generations for over 5000 years. The process involves hydro-distillation of aromatic plants and flowers into a sandalwood oil base.</p><p>Unlike alcohol-based perfumes, attars are oil-based, making them longer-lasting and more skin-friendly.</p><p>The traditional method uses copper pots and slow distillation over low heat for days or even weeks.</p>', 'uploads/Screenshot_2026-05-28_233339.png', 'published', 'attar,guide,traditional', 156, NOW()),
(2, 'Best Oudh Fragrances for Winter Season', 'guide', 'Admin', 6, 'Find the perfect oudh fragrance for the cold season. Winter calls for warm, rich scents.', '<p>Winter calls for warm, rich fragrances that linger in the cold air. Oudh, known as "liquid gold", is perfect for colder months.</p><p>Our top picks for winter include Royal Oudh, Oudh Al Misk, and Amber Oudh.</p><p>These fragrances have excellent longevity and projection in cold weather.</p>', 'uploads/Screenshot_2026-05-25_181926.png', 'published', 'oudh,winter,guide', 89, NOW());

-- Insert Carousel Slides (Homepage Banner - using your uploads)
INSERT INTO `carousel_slides` (`id`, `title`, `subtitle`, `image`, `button_text`, `button_link`, `order_index`, `is_active`) VALUES
(1, 'Premium Attars', 'Experience the finest traditional fragrances', 'uploads/Screenshot_2026-05-31_184600.png', 'Shop Now', 'shop.html', 1, 1),
(2, 'Oudh Collection', 'Discover our premium oudh attars', 'uploads/Screenshot_2026-05-31_184442.png', 'Explore Oudh', 'shop.html?category=oudh', 2, 1),
(3, 'Limited Edition', 'Exclusive fragrances only at #WE ARE MUSLIM', 'uploads/Screenshot_2026-05-31_184225.png', 'Shop Now', 'shop.html', 3, 1);

-- Insert Features (Homepage features section)
INSERT INTO `features` (`id`, `icon`, `title`, `description`, `link`, `order_index`, `is_active`) VALUES
(1, 'fas fa-gem', 'Premium Quality', '100% natural attars, alcohol-free', 'shop.html', 1, 1),
(2, 'fas fa-truck', 'Free Shipping', 'Free delivery on orders over ৳2000', 'shop.html', 2, 1),
(3, 'fas fa-gift', 'Gift Ready', 'Beautiful gift packaging available', 'shop.html', 3, 1),
(4, 'fas fa-shield-alt', '100% Authentic', 'Premium quality guaranteed', 'shop.html', 4, 1),
(5, 'fas fa-map-marker-alt', 'Order Tracking', 'Real-time order updates', 'order-tracking.html', 5, 1),
(6, 'fas fa-headset', '24/7 Support', 'Customer care always ready', 'contact.html', 6, 1);

-- Insert Homepage Content (All text content for homepage)
INSERT INTO `homepage_content` (`id`, `section`, `title`, `subtitle`, `content`, `image`, `button_text`, `button_link`, `order_index`) VALUES
(1, 'hero_title', 'Premium Attars & Fragrances', NULL, NULL, NULL, NULL, NULL, 1),
(2, 'hero_subtitle', NULL, 'Discover the finest collection of traditional and modern attars', NULL, NULL, NULL, NULL, 2),
(3, 'hero_button', 'Shop Now →', NULL, NULL, NULL, NULL, 'shop.html', 3),
(4, 'featured_title', '⭐ Featured Attars', NULL, NULL, NULL, NULL, NULL, 4),
(5, 'featured_subtitle', NULL, 'Our hand-picked selection of premium fragrances', NULL, NULL, NULL, NULL, 5),
(6, 'featured_button_text', 'View All →', NULL, NULL, NULL, NULL, NULL, 6),
(7, 'bestseller_title', '🔥 Best Sellers', NULL, NULL, NULL, NULL, NULL, 7),
(8, 'bestseller_subtitle', NULL, 'Most loved by our customers', NULL, NULL, NULL, NULL, 8),
(9, 'bestseller_button_text', 'Shop Bestsellers →', NULL, NULL, NULL, NULL, NULL, 9),
(10, 'newsletter_title', '📧 Subscribe & Get 15% OFF', NULL, NULL, NULL, NULL, NULL, 10),
(11, 'newsletter_content', NULL, NULL, 'Plus exclusive offers, early access to sales, and perfume guides!', NULL, NULL, NULL, 11),
(12, 'newsletter_button_text', 'Subscribe', NULL, NULL, NULL, NULL, NULL, 12),
(13, 'footer_text', '#WE ARE MUSLIM', NULL, NULL, NULL, NULL, NULL, 13),
(14, 'footer_subtitle', NULL, 'Premium attars crafted with tradition and passion since 2020.', NULL, NULL, NULL, NULL, 14);

-- Insert Sample Coupons
INSERT INTO `coupons` (`code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `usage_limit`, `is_active`, `start_date`, `end_date`) VALUES
('WELCOME15', '15% off on first purchase', 'percent', 15, 500, 500, 100, 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('SAVE10', 'Flat ৳100 off on orders above ৳1000', 'fixed', 100, 1000, NULL, 50, 1, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY));

-- Insert Sample Reviews
INSERT INTO `reviews` (`product_id`, `user_name`, `user_email`, `rating`, `comment`, `created_at`) VALUES
(1, 'Ahmed R.', 'ahmed@example.com', 5, 'Absolutely amazing! The scent lasts all day.', NOW()),
(2, 'Fatima K.', 'fatima@example.com', 4, 'Very good musk, not too strong.', NOW()),
(3, 'Omar H.', 'omar@example.com', 5, 'Beautiful rose fragrance. My wife loves it!', NOW());

-- ============================================
-- AUTO_INCREMENT RESET VALUES
-- ============================================
ALTER TABLE users AUTO_INCREMENT = 3;
ALTER TABLE products AUTO_INCREMENT = 9;
ALTER TABLE blogs AUTO_INCREMENT = 3;
ALTER TABLE carousel_slides AUTO_INCREMENT = 4;
ALTER TABLE features AUTO_INCREMENT = 7;
ALTER TABLE homepage_content AUTO_INCREMENT = 15;
ALTER TABLE coupons AUTO_INCREMENT = 3;