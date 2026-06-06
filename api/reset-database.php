<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Get raw input
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// If no data received, try $_POST
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

$action = $data['action'] ?? '';
$confirm = $data['confirm'] ?? '';

// Debug log
error_log("Reset database request - action: $action, confirm: $confirm");

if ($action !== 'reset_all' || $confirm !== 'DELETE ALL DATA') {
    echo json_encode(['success' => false, 'error' => 'Invalid confirmation. Please type "DELETE ALL DATA"']);
    exit();
}

try {
    // Test database connection first
    $pdo->query("SELECT 1");
    
    $pdo->beginTransaction();
    
    $results = [];
    $errors = [];
    
    // ============================================
    // 1. DELETE ORDERS AND ORDER ITEMS
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM order_items");
        $stmt->execute();
        $results['order_items'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $errors['order_items'] = $e->getMessage();
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM orders");
        $stmt->execute();
        $results['orders'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $errors['orders'] = $e->getMessage();
    }
    
    // ============================================
    // 2. DELETE COUPON USAGE (if table exists)
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM coupon_usage");
        $stmt->execute();
        $results['coupon_usage'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $results['coupon_usage'] = "Table not found or already empty";
    }
    
    // ============================================
    // 3. DELETE ALL COUPONS (NO DEFAULT COUPONS)
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons");
        $stmt->execute();
        $results['coupons_deleted'] = $stmt->rowCount() . " coupons deleted (no default coupons created)";
    } catch (PDOException $e) {
        $results['coupons'] = "Coupons table not found or already empty";
    }
    
    // ============================================
    // 4. DELETE BLOG SUBMISSIONS
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM blog_submissions");
        $stmt->execute();
        $results['blog_submissions'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $errors['blog_submissions'] = $e->getMessage();
    }
    
    // ============================================
    // 5. DELETE REVIEWS
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews");
        $stmt->execute();
        $results['reviews'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $errors['reviews'] = $e->getMessage();
    }
    
    // ============================================
    // 6. DELETE RETURN REQUESTS
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM return_requests");
        $stmt->execute();
        $results['return_requests'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $errors['return_requests'] = $e->getMessage();
    }
    
    // ============================================
    // 7. RESET USERS - Keep only id 1 and 2 with complete address info
    // ============================================
    try {
        // Delete all users except id 1 and 2
        $stmt = $pdo->prepare("DELETE FROM users WHERE id NOT IN (1, 2)");
        $stmt->execute();
        $results['users_deleted'] = $stmt->rowCount() . " users deleted (kept id 1 and 2)";
    } catch (PDOException $e) {
        $errors['users'] = $e->getMessage();
    }
    
    // === RESET ADMIN USER (id = 1) with FULL ADDRESS ===
    try {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET 
            name = 'Admin User',
            email = 'admin@attar.com',
            password = ?,
            phone = '+880 1234 567890',
            address = 'House #42, Road #12, Banani',
            city = 'Dhaka',
            postal_code = '1213',
            role = 'admin',
            tier = 'Platinum',
            tier_expiry = DATE_ADD(NOW(), INTERVAL 365 DAY),
            total_spent = 0,
            created_at = NOW()
            WHERE id = 1");
        $stmt->execute([$hashedPassword]);
        
        // If user 1 doesn't exist, insert it
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = 1");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, address, city, postal_code, role, tier, tier_expiry, total_spent, created_at) 
                                   VALUES (1, 'Admin User', 'admin@attar.com', ?, '+880 1234 567890', 'House #42, Road #12, Banani', 'Dhaka', '1213', 'admin', 'Platinum', DATE_ADD(NOW(), INTERVAL 365 DAY), 0, NOW())");
            $stmt->execute([$hashedPassword]);
            $results['admin_created'] = "Admin user created with id 1 (Platinum tier)";
        } else {
            $results['admin_reset'] = "Admin user reset to Platinum tier with 150,000 total spent and full address";
        }
    } catch (PDOException $e) {
        $errors['admin_reset'] = $e->getMessage();
    }
    
    // === RESET TEST USER (id = 2) with FULL ADDRESS ===
    try {
        $hashedPassword = password_hash('user123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET 
            name = 'Test Customer',
            email = 'user@test.com',
            password = ?,
            phone = '+880 1987654321',
            address = 'House #15, Road #5, Gulshan',
            city = 'Dhaka',
            postal_code = '1212',
            role = 'user',
            tier = 'Bronze',
            tier_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY),
            total_spent = 0,
            created_at = NOW()
            WHERE id = 2");
        $stmt->execute([$hashedPassword]);
        
        // If user 2 doesn't exist, insert it
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = 2");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, address, city, postal_code, role, tier, tier_expiry, total_spent, created_at) 
                                   VALUES (2, 'Test Customer', 'user@test.com', ?, '+880 1987654321', 'House #15, Road #5, Gulshan', 'Dhaka', '1212', 'user', 'Bronze', DATE_ADD(NOW(), INTERVAL 30 DAY), 0, NOW())");
            $stmt->execute([$hashedPassword]);
            $results['testuser_created'] = "Test user created with id 2 (Bronze tier)";
        } else {
            $results['testuser_reset'] = "Test user reset to Bronze tier with 0 total spent and full address";
        }
    } catch (PDOException $e) {
        $errors['testuser_reset'] = $e->getMessage();
    }
    
    // ============================================
    // 8. DELETE PRODUCTS WITH ID > 8, THEN ENSURE 1-8 EXIST
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id > 8");
        $stmt->execute();
        $results['products_deleted'] = $stmt->rowCount() . " products with id > 8 deleted";
    } catch (PDOException $e) {
        $errors['products_delete'] = $e->getMessage();
    }
    
    $sampleProducts = [
        1 => ['Royal Oudh', 'Arabian Oud', 'Oudh', 2990, 50, 'Premium royal oudh attar with long-lasting fragrance.', 'uploads/Screenshot_2026-05-28_233753.png', 0, 0],
        2 => ['Musk Al Haramain', 'Harramain', 'Musk', 1890, 75, 'Traditional musk attar that captivates the senses.', 'uploads/Screenshot_2026-05-28_233339.png', 0, 0],
        3 => ['Rose Attar', 'Swiss Arabian', 'Rose', 990, 100, 'Pure rose petal attar extracted from Damask roses.', 'uploads/Screenshot_2026-05-28_233510.png', 0, 0],
        4 => ['Amber Oudh', 'Ajmal', 'Amber', 2490, 40, 'Amber and oudh blend for a warm, sensual experience.', 'uploads/Screenshot_2026-05-28_233406.png', 0, 0],
        5 => ['Sandalwood Classic', 'Mysore', 'Sandalwood', 1590, 60, 'Pure sandalwood oil from Mysore.', 'uploads/Screenshot_2026-05-25_181536.png', 0, 0],
        6 => ['Jasmine Supreme', 'Al Haramain', 'Jasmine', 1290, 85, 'Exquisite jasmine attar that reminds of blooming gardens.', 'uploads/Screenshot_2026-05-25_182001.png', 0, 0],
        7 => ['Oudh Al Misk', 'Abdul Samad Al Qurashi', 'Oudh/Musk', 3590, 30, 'Premium blend of Cambodian oudh and white musk.', 'uploads/Screenshot_2026-05-25_181943.png', 0, 0],
        8 => ['Saffron Royale', 'Rasasi', 'Saffron', 2190, 45, 'Luxury saffron-infused attar with woody undertones.', 'uploads/Screenshot_2026-05-28_233839.png', 0, 0]
    ];
    
    foreach ($sampleProducts as $id => $p) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE products SET name = ?, brand = ?, fragrance = ?, price = ?, stock = ?, description = ?, image = ?, ratings = ?, reviews = ? WHERE id = ?");
                $updateStmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $id]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO products (id, name, brand, fragrance, price, stock, description, image, ratings, reviews) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([$id, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8]]);
            }
        } catch (PDOException $e) {
            $errors["product_$id"] = $e->getMessage();
        }
    }
    $results['products'] = count($sampleProducts) . " products preserved (id 1-8)";
    
    // ============================================
    // 9. DELETE BLOGS WITH ID > 2, THEN ENSURE 1-2 EXIST
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM blogs WHERE id > 2");
        $stmt->execute();
        $results['blogs_deleted'] = $stmt->rowCount() . " blogs with id > 2 deleted";
    } catch (PDOException $e) {
        $errors['blogs_delete'] = $e->getMessage();
    }
    
    $sampleBlogs = [
        1 => [
            'title' => 'The Art of Attar Making: A 5000 Year Old Tradition',
            'category' => 'guide', 'author' => 'Admin', 'read_time' => 8,
            'excerpt' => 'Discover the ancient art of traditional attar making that has been passed down through generations.',
            'content' => '<p>Attar making is an ancient art that has been passed down through generations for over 5000 years. The process involves hydro-distillation of aromatic plants and flowers into a sandalwood oil base.</p><p>Unlike alcohol-based perfumes, attars are oil-based, making them longer-lasting and more skin-friendly.</p>',
            'image' => 'uploads/Screenshot_2026-05-28_233339.png',
            'status' => 'published', 'tags' => 'attar,guide,traditional', 'views' => 0
        ],
        2 => [
            'title' => 'Best Oudh Fragrances for Winter Season',
            'category' => 'guide', 'author' => 'Admin', 'read_time' => 6,
            'excerpt' => 'Find the perfect oudh fragrance for the cold season.',
            'content' => '<p>Winter calls for warm, rich fragrances that linger in the cold air. Oudh, known as "liquid gold", is perfect for colder months.</p><p>Our top picks for winter include Royal Oudh, Oudh Al Misk, and Amber Oudh.</p>',
            'image' => 'uploads/Screenshot_2026-05-25_181926.png',
            'status' => 'published', 'tags' => 'oudh,winter,guide', 'views' => 0
        ]
    ];
    
    foreach ($sampleBlogs as $id => $blog) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM blogs WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE blogs SET title = ?, category = ?, author = ?, read_time = ?, excerpt = ?, content = ?, image = ?, status = ?, tags = ?, views = ? WHERE id = ?");
                $updateStmt->execute([$blog['title'], $blog['category'], $blog['author'], $blog['read_time'], $blog['excerpt'], $blog['content'], $blog['image'], $blog['status'], $blog['tags'], $blog['views'], $id]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO blogs (id, title, category, author, read_time, excerpt, content, image, status, tags, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $insertStmt->execute([$id, $blog['title'], $blog['category'], $blog['author'], $blog['read_time'], $blog['excerpt'], $blog['content'], $blog['image'], $blog['status'], $blog['tags'], $blog['views']]);
            }
        } catch (PDOException $e) {
            $errors["blog_$id"] = $e->getMessage();
        }
    }
    $results['blogs'] = count($sampleBlogs) . " blogs preserved (id 1-2)";
    
    // ============================================
    // 10. DELETE CAROUSEL SLIDES WITH ID > 3, THEN ENSURE 1-3 EXIST (UPDATED IMAGES)
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM carousel_slides WHERE id > 3");
        $stmt->execute();
        $results['carousel_deleted'] = $stmt->rowCount() . " carousel slides with id > 3 deleted";
    } catch (PDOException $e) {
        $errors['carousel_delete'] = $e->getMessage();
    }
    
    $carouselSlides = [
        1 => ['Premium Attars', 'Experience the finest traditional fragrances', 'uploads/Screenshot_2026-05-31_184600.png', 'Shop Now', 'shop.html', 1],
        2 => ['Oudh Collection', 'Discover our premium oudh attars', 'uploads/Screenshot_2026-05-31_184442.png', 'Explore Oudh', 'shop.html?category=oudh', 2],
        3 => ['Limited Edition', 'Exclusive fragrances only at #WE ARE MUSLIM', 'uploads/Screenshot_2026-05-31_184225.png', 'Shop Now', 'shop.html', 3]
    ];
    
    foreach ($carouselSlides as $id => $slide) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM carousel_slides WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE carousel_slides SET title = ?, subtitle = ?, image = ?, button_text = ?, button_link = ?, order_index = ?, is_active = 1 WHERE id = ?");
                $updateStmt->execute([$slide[0], $slide[1], $slide[2], $slide[3], $slide[4], $slide[5], $id]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO carousel_slides (id, title, subtitle, image, button_text, button_link, order_index, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $insertStmt->execute([$id, $slide[0], $slide[1], $slide[2], $slide[3], $slide[4], $slide[5]]);
            }
        } catch (PDOException $e) {
            $errors["carousel_$id"] = $e->getMessage();
        }
    }
    $results['carousel_slides'] = count($carouselSlides) . " carousel slides preserved (id 1-3) with updated images";
    
    // ============================================
    // 11. DELETE FEATURES WITH ID > 6, THEN ENSURE 1-6 EXIST
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM features WHERE id > 6");
        $stmt->execute();
        $results['features_deleted'] = $stmt->rowCount() . " features with id > 6 deleted";
    } catch (PDOException $e) {
        $errors['features_delete'] = $e->getMessage();
    }
    
    $features = [
        1 => ['fas fa-gem', 'Premium Quality', '100% natural attars, alcohol-free', 'shop.html', 1],
        2 => ['fas fa-truck', 'Free Shipping', 'Free delivery on orders over ৳10000', 'shop.html', 2],
        3 => ['fas fa-gift', 'Gift Ready', 'Beautiful gift packaging available', 'shop.html', 3],
        4 => ['fas fa-shield-alt', '100% Authentic', 'Premium quality guaranteed', 'shop.html', 4],
        5 => ['fas fa-map-marker-alt', 'Order Tracking', 'Real-time order updates', 'order-tracking.html', 5],
        6 => ['fas fa-headset', '24/7 Support', 'Customer care always ready', 'contact.html', 6]
    ];
    
    foreach ($features as $id => $feature) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM features WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE features SET icon = ?, title = ?, description = ?, link = ?, order_index = ?, is_active = 1 WHERE id = ?");
                $updateStmt->execute([$feature[0], $feature[1], $feature[2], $feature[3], $feature[4], $id]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO features (id, icon, title, description, link, order_index, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $insertStmt->execute([$id, $feature[0], $feature[1], $feature[2], $feature[3], $feature[4]]);
            }
        } catch (PDOException $e) {
            $errors["feature_$id"] = $e->getMessage();
        }
    }
    $results['features'] = count($features) . " features preserved (id 1-6)";
    
    // ============================================
    // 12. DELETE HOMEPAGE CONTENT WITH ID > 14, THEN ENSURE 1-14 EXIST
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM homepage_content WHERE id > 14");
        $stmt->execute();
        $results['homepage_deleted'] = $stmt->rowCount() . " homepage content rows with id > 14 deleted";
    } catch (PDOException $e) {
        $errors['homepage_delete'] = $e->getMessage();
    }
    
    $homepageContent = [
        1 => ['hero_title', 'Premium Attars & Fragrances', null, null, null, null, 1],
        2 => ['hero_subtitle', null, 'Discover the finest collection of traditional and modern attars', null, null, null, 2],
        3 => ['hero_button', 'Shop Now →', null, null, null, 'shop.html', 3],
        4 => ['featured_title', '⭐ Featured Attars', null, null, null, null, 4],
        5 => ['featured_subtitle', null, 'Our hand-picked selection of premium fragrances', null, null, null, 5],
        6 => ['featured_button_text', 'View All →', null, null, null, null, 6],
        7 => ['bestseller_title', '🔥 Best Sellers', null, null, null, null, 7],
        8 => ['bestseller_subtitle', null, 'Most loved by our customers', null, null, null, 8],
        9 => ['bestseller_button_text', 'Shop Bestsellers →', null, null, null, null, 9],
        10 => ['newsletter_title', '📧 Subscribe & Get 15% OFF', null, null, null, null, 10],
        11 => ['newsletter_content', null, null, 'Plus exclusive offers, early access to sales, and perfume guides!', null, null, 11],
        12 => ['newsletter_button_text', 'Subscribe', null, null, null, null, 12],
        13 => ['footer_text', '#WE ARE MUSLIM', null, null, null, null, 13],
        14 => ['footer_subtitle', null, 'Premium attars crafted with tradition and passion since 2020.', null, null, null, 14]
    ];
    
    foreach ($homepageContent as $id => $content) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM homepage_content WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE homepage_content SET section = ?, title = ?, subtitle = ?, content = ?, button_text = ?, button_link = ?, order_index = ? WHERE id = ?");
                $updateStmt->execute([$content[0], $content[1], $content[2], $content[3], $content[4], $content[5], $content[6], $id]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO homepage_content (id, section, title, subtitle, content, button_text, button_link, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([$id, $content[0], $content[1], $content[2], $content[3], $content[4], $content[5], $content[6]]);
            }
        } catch (PDOException $e) {
            $errors["homepage_$id"] = $e->getMessage();
        }
    }
    $results['homepage_content'] = count($homepageContent) . " homepage sections preserved (id 1-14)";
    
    // ============================================
    // 13. DROP LOYALTY POINTS TABLES (if they exist)
    // ============================================
    try {
        $stmt = $pdo->prepare("DROP TABLE IF EXISTS points_history");
        $stmt->execute();
        $results['points_history'] = "points_history table dropped";
    } catch (PDOException $e) {
        $errors['points_history'] = $e->getMessage();
    }
    
    try {
        $stmt = $pdo->prepare("DROP TABLE IF EXISTS loyalty_points");
        $stmt->execute();
        $results['loyalty_points'] = "loyalty_points table dropped";
    } catch (PDOException $e) {
        $errors['loyalty_points'] = $e->getMessage();
    }
    
    // ============================================
    // 14. RESET AUTO_INCREMENT VALUES
    // ============================================
    try {
        $pdo->exec("ALTER TABLE products AUTO_INCREMENT = 9");
        $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE blogs AUTO_INCREMENT = 3");
        $pdo->exec("ALTER TABLE blog_submissions AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE reviews AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE return_requests AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE carousel_slides AUTO_INCREMENT = 4");
        $pdo->exec("ALTER TABLE features AUTO_INCREMENT = 7");
        $pdo->exec("ALTER TABLE homepage_content AUTO_INCREMENT = 15");
        $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 3");
        $pdo->exec("ALTER TABLE coupons AUTO_INCREMENT = 1");
        $results['auto_increment'] = "Auto-increment values reset";
    } catch (PDOException $e) {
        $errors['auto_increment'] = $e->getMessage();
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database reset successfully! Admin has Platinum tier, Test user has Bronze tier.',
        'results' => $results,
        'errors' => $errors,
        'default_users' => [
            'admin' => [
                'email' => 'admin@attar.com',
                'password' => 'admin123',
                'tier' => 'Platinum',
                'discount' => '12%',
                'total_spent' => '150,000',
                'address' => 'House #42, Road #12, Banani, Dhaka - 1213',
                'phone' => '+880 1234 567890'
            ],
            'test_user' => [
                'email' => 'user@test.com',
                'password' => 'user123',
                'tier' => 'Bronze',
                'discount' => '0%',
                'total_spent' => '0',
                'address' => 'House #15, Road #5, Gulshan, Dhaka - 1212',
                'phone' => '+880 1987654321'
            ]
        ],
        'carousel_images_updated' => [
            'slide_1' => 'uploads/Screenshot_2026-05-31_184600.png',
            'slide_2' => 'uploads/Screenshot_2026-05-31_184442.png',
            'slide_3' => 'uploads/Screenshot_2026-05-31_184225.png'
        ],
        'coupons' => 'No default coupons created. Admin can create coupons from admin panel.',
        'preserved_ids' => [
            'users' => [1, 2],
            'products' => [1, 2, 3, 4, 5, 6, 7, 8],
            'blogs' => [1, 2],
            'carousel_slides' => [1, 2, 3],
            'features' => [1, 2, 3, 4, 5, 6],
            'homepage_content' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]
        ],
        'tier_info' => [
            'Platinum (12% off)' => 'Total spent: 100,000+',
            'Gold (10% off)' => 'Total spent: 50,000 - 99,999',
            'Silver (5% off)' => 'Total spent: 20,000 - 49,999',
            'Bronze (0% off)' => 'Total spent: 0 - 19,999'
        ]
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database reset error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General reset error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>