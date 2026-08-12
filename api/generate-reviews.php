<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// Function to generate random date within the last 6 months
function randomDate($startDate, $endDate) {
    $timestamp = mt_rand(strtotime($startDate), strtotime($endDate));
    return date('Y-m-d H:i:s', $timestamp);
}

// Realistic user names
$firstNames = ['Mohammed', 'Ahmed', 'Fatima', 'Ali', 'Aisha', 'Omar', 'Zainab', 'Hassan', 'Khadija', 'Yusuf', 
               'Mariam', 'Ibrahim', 'Sara', 'Bilal', 'Nadia', 'Hamza', 'Leila', 'Rashid', 'Sumaya', 'Tariq',
               'Layla', 'Saif', 'Amira', 'Malik', 'Noor', 'Rayyan', 'Hana', 'Idris', 'Salma', 'Karim'];

$lastNames = ['Rahman', 'Ahmed', 'Hassan', 'Ali', 'Hussain', 'Islam', 'Khan', 'Chowdhury', 'Miah', 'Uddin',
              'Sarker', 'Haque', 'Begum', 'Hossain', 'Khatun', 'Patel', 'Mahmood', 'Siddiqui', 'Farooq'];

// Review content templates
$positiveReviews = [
    "Absolutely love this attar! The fragrance is long-lasting and gets me compliments everywhere I go.",
    "Best purchase I've made this year. The scent is unique, elegant, and very traditional.",
    "Amazing fragrance! My whole family loves it. Will definitely buy again.",
    "Excellent quality for the price. The scent stays on for more than 12 hours.",
    "This has become my signature scent. Everyone asks what I'm wearing.",
    "Beautiful blend of oud and musk. Very authentic Arabic fragrance.",
    "The packaging was beautiful and the scent is exactly as described. Highly recommended!",
    "Perfect for daily wear or special occasions. Not too overpowering but definitely noticeable.",
    "I bought this as a gift and the recipient loved it. Such a sophisticated scent.",
    "Truly authentic fragrance. Reminds me of traditional Arabian perfumes.",
    "Worth every penny! The quality is premium and the scent lasts all day.",
    "Very impressed with the longevity. One application lasts the whole day.",
    "The fragrance evolves beautifully - starts fresh and settles into a warm, woody base.",
    "Great value for money. Beats many expensive designer fragrances.",
    "My favorite attar so far. The perfect balance of sweet and woody notes.",
    "Excellent projection and sillage. People notice it from across the room.",
    "The quality of ingredients is top-notch. You can really tell the difference.",
    "This attar is pure perfection. Will be ordering more for sure.",
    "Such a calming and spiritual scent. Perfect for prayers and meditation.",
    "Rich, complex, and absolutely beautiful. A masterpiece of perfumery."
];

$neutralReviews = [
    "Good but not great. The fragrance is nice but doesn't last as long as expected. About 4-5 hours maximum.",
    "Decent product for the price point. Nothing extraordinary but does the job.",
    "The scent is pleasant but fades quickly. Needs reapplication after a few hours.",
    "Good for everyday use but nothing special. Would recommend for beginners.",
    "It's okay. Not my favorite but not bad either. Probably won't repurchase.",
    "The packaging was nice but the fragrance itself is just average.",
    "Mixed feelings about this one. Some days I love it, other days it's just okay.",
    "Average quality. You can find similar products at a lower price point.",
    "Not bad but not amazing either. Does what it promises.",
    "The scent is decent but doesn't project well. Stays very close to the skin.",
    "It's alright for the price. Not a standout but not disappointing either.",
    "The fragrance is pleasant but nothing memorable. Just average.",
    "Does the job but I expected more from this brand.",
    "It's fine. I've used better, I've used worse.",
    "Mediocre at best. Probably won't buy again but not terrible."
];

$negativeReviews = [
    "Very disappointed. The fragrance fades within an hour and smells nothing like described.",
    "Too strong for my taste. Gave me a headache and I had to wash it off.",
    "Not worth the money at all. The fragrance is too synthetic and cheap smelling.",
    "Complete waste of money. Lasts only about 2 hours. Very disappointing.",
    "The bottle arrived leaking. Poor packaging and customer service was unhelpful.",
    "Smells nothing like what I expected. Very different from the description.",
    "Not authentic at all. I've used similar attars before and this is poor quality.",
    "The scent is way too overpowering and chemical-like. Would not recommend.",
    "Does not last at all. Gone within 2 hours. Very disappointed with the quality.",
    "Overpriced for what you get. Many better options available for less money.",
    "Terrible quality. Avoid this product at all costs.",
    "The fragrance is nauseating. Had to throw it away.",
    "Cheap and artificial smelling. Nothing like the description.",
    "Complete rip-off. Save your money and buy elsewhere.",
    "Worst attar I've ever purchased. Zero longevity and poor scent profile."
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Generate Product Reviews</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #8B5E3C; }
        .success { color: green; }
        .progress { margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; }
        .summary { margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 5px; }
        button { background: #8B5E3C; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #6B4226; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📝 Product Review Generator</h1>";

// Get all products
try {
    // First, check what columns exist in reviews table
    $stmt = $pdo->query("SHOW COLUMNS FROM reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Found columns in reviews table: " . implode(', ', $columns) . "</p>";
    
    // Check if reviews table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='error'>❌ Reviews table does not exist! Creating it now...</div>";
        
        // Create reviews table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                user_id INT NULL,
                user_name VARCHAR(100),
                user_email VARCHAR(100),
                rating INT CHECK (rating BETWEEN 1 AND 5),
                comment TEXT,
                images TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ");
        echo "<div class='success'>✓ Reviews table created successfully!</div>";
    }
    
    // Fetch all products
    $stmt = $pdo->query("SELECT id, name, brand FROM products ORDER BY id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<div class='error'>❌ No products found in database!</div>";
        exit();
    }
    
    echo "<p>Found " . count($products) . " products in database.</p>";
    echo "<hr>";
    
    $totalReviewsGenerated = 0;
    $reviewsPerProduct = 50;
    
    foreach ($products as $product) {
        echo "<div class='progress'>";
        echo "<strong>📦 Product: {$product['name']}</strong><br>";
        
        // Check existing review count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE product_id = ?");
        $stmt->execute([$product['id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingCount = $existing['count'];
        
        $needed = $reviewsPerProduct - $existingCount;
        
        if ($needed <= 0) {
            echo "✓ Already has {$existingCount} reviews (target: {$reviewsPerProduct}) - Skipping<br>";
            echo "</div>";
            continue;
        }
        
        echo "📝 Need to generate {$needed} reviews...<br>";
        
        $generatedCount = 0;
        
        // Generate reviews
        for ($i = 0; $i < $needed; $i++) {
            // Random rating (realistic distribution)
            $rand = mt_rand(1, 100);
            if ($rand <= 65) { // 65% 5-star
                $rating = 5;
                $reviewPool = $positiveReviews;
            } elseif ($rand <= 85) { // 20% 4-star
                $rating = 4;
                $reviewPool = $positiveReviews;
            } elseif ($rand <= 94) { // 9% 3-star
                $rating = 3;
                $reviewPool = $neutralReviews;
            } elseif ($rand <= 98) { // 4% 2-star
                $rating = 2;
                $reviewPool = $negativeReviews;
            } else { // 2% 1-star
                $rating = 1;
                $reviewPool = $negativeReviews;
            }
            
            // Random user name
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $userName = $firstName . ' ' . $lastName;
            $email = strtolower($firstName . '.' . $lastName . mt_rand(1, 999)) . '@example.com';
            
            // Get random review text
            $comment = $reviewPool[array_rand($reviewPool)];
            
            // Add product name occasionally for personalization
            if (mt_rand(1, 100) <= 40) {
                $comment .= " The {$product['name']} is definitely worth trying!";
            }
            
            // Random date within last 6 months
            $daysAgo = mt_rand(0, 180);
            $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
            
            // Insert review - WITHOUT helpful column
            $stmt = $pdo->prepare("
                INSERT INTO reviews (product_id, user_id, user_name, user_email, rating, comment, created_at) 
                VALUES (?, NULL, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $product['id'],
                $userName,
                $email,
                $rating,
                $comment,
                $createdAt
            ]);
            
            $generatedCount++;
            $totalReviewsGenerated++;
            
            // Show progress every 10 reviews
            if ($generatedCount % 10 == 0) {
                echo "  → Generated {$generatedCount}/{$needed} reviews<br>";
                flush();
            }
        }
        
        echo "✅ <strong>Successfully generated {$generatedCount} reviews for {$product['name']}</strong><br>";
        echo "</div>";
        echo "<br>";
    }
    
    // Display summary
    echo "<div class='summary'>";
    echo "<h2>✅ Generation Complete!</h2>";
    echo "<p><strong>Total reviews generated:</strong> {$totalReviewsGenerated}</p>";
    echo "<p><strong>Target per product:</strong> {$reviewsPerProduct} reviews</p>";
    
    // Show final counts
    echo "<h3>📊 Final Review Counts:</h3>";
    echo "<ul>";
    $stmt = $pdo->query("
        SELECT p.id, p.name, COUNT(r.id) as review_count, ROUND(AVG(r.rating), 1) as avg_rating
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
        GROUP BY p.id
        ORDER BY p.id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $avgRating = $row['avg_rating'] ? $row['avg_rating'] : 0;
        $starDisplay = str_repeat('★', round($avgRating)) . str_repeat('☆', 5 - round($avgRating));
        echo "<li><strong>{$row['name']}</strong>: {$row['review_count']} reviews (Average rating: {$avgRating} {$starDisplay})</li>";
    }
    echo "</ul>";
    
    // Sample of generated reviews
    echo "<h3>📝 Sample of Generated Reviews:</h3>";
    $stmt = $pdo->query("
        SELECT r.*, p.name as product_name 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        ORDER BY r.id DESC 
        LIMIT 5
    ");
    $samples = $stmt->fetchAll();
    echo "<ul>";
    foreach ($samples as $sample) {
        echo "<li><strong>{$sample['product_name']}</strong> - {$sample['user_name']} rated {$sample['rating']}★: " . substr($sample['comment'], 0, 100) . "...</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<a href='../products.html'><button>📦 Go to Products Page</button></a> ";
    echo "<button onclick='location.reload()'>🔄 Regenerate Missing Reviews</button>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Database Error:</strong> " . $e->getMessage() . "<br><br>";
    echo "<strong>Solution:</strong> Run this SQL in phpMyAdmin first:<br>";
    echo "<pre>
-- Check current table structure
DESCRIBE reviews;

-- If 'helpful' column is missing, add it (optional)
ALTER TABLE reviews ADD COLUMN helpful INT DEFAULT 0;

-- Or if you don't want helpful column, the script above will work without it
    </pre>";
    echo "</div>";
}
?>

    </div>
</body>
</html>