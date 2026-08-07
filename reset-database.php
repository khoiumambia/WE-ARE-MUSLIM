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
$generateReviews = $data['generate_reviews'] ?? true;

error_log("Reset database request - action: $action, confirm: $confirm, generate_reviews: $generateReviews");

if ($action !== 'reset_all' || $confirm !== 'DELETE ALL DATA') {
    echo json_encode(['success' => false, 'error' => 'Invalid confirmation. Please type "DELETE ALL DATA"']);
    exit();
}

// Function to generate random date
function randomDate($startDate, $endDate) {
    $timestamp = mt_rand(strtotime($startDate), strtotime($endDate));
    return date('Y-m-d H:i:s', $timestamp);
}

// Function to generate reviews for a product
function generateReviewsForProduct($pdo, $productId, $productName, $count = 50) {
    // --- ADDED: All 273 user names from reviews ---
    $allUserNames = [
        'Ahmed Chowdhury', 'Ahmed Khan', 'Ahmed Ahmed', 'Ahmed Sarker', 'Ahmed Mahmood',
        'Ahmed Rahman', 'Ahmed Uddin', 'Ahmed Hossain', 'Ahmed Miah', 'Aisha Begum',
        'Aisha Islam', 'Aisha Patel', 'Aisha Rahman', 'Aisha Hussain', 'Aisha Ali',
        'Aisha Haque', 'Ali Khatun', 'Ali Siddiqui', 'Ali Islam', 'Ali Hassan',
        'Ali Begum', 'Ali Ahmed', 'Ali Mahmood', 'Amira Farooq', 'Amira Ali',
        'Amira Rahman', 'Amira Sarker', 'Amira Miah', 'Amira Chowdhury', 'Bilal Hossain',
        'Bilal Sarker', 'Bilal Begum', 'Bilal Miah', 'Bilal Ali', 'Bilal Patel',
        'Bilal Ahmed', 'Bilal Islam', 'Fatima Chowdhury', 'Fatima Uddin', 'Fatima Sarker',
        'Fatima Hussain', 'Fatima Rahman', 'Fatima Patel', 'Fatima Khan', 'Hana Hassan',
        'Hana Islam', 'Hana Sarker', 'Hana Rahman', 'Hana Ali', 'Hana Patel',
        'Hana Uddin', 'Hana Begum', 'Hana Haque', 'Hana Hussain', 'Hana Khatun',
        'Hamza Hossain', 'Hamza Miah', 'Hamza Siddiqui', 'Hamza Begum', 'Hamza Haque',
        'Hamza Hassan', 'Hamza Sarker', 'Hamza Chowdhury', 'Hamza Rahman', 'Hassan Hassan',
        'Hassan Farooq', 'Hassan Ali', 'Hassan Mahmood', 'Hassan Hossain', 'Hassan Islam',
        'Hassan Chowdhury', 'Hassan Rahman', 'Hassan Khatun', 'Ibrahim Begum', 'Ibrahim Haque',
        'Ibrahim Hussain', 'Ibrahim Miah', 'Ibrahim Patel', 'Ibrahim Sarker', 'Ibrahim Khan',
        'Ibrahim Ahmed', 'Ibrahim Siddiqui', 'Ibrahim Khatun', 'Idris Siddiqui', 'Idris Begum',
        'Idris Miah', 'Idris Farooq', 'Idris Hossain', 'Idris Ali', 'Idris Khatun',
        'Idris Rahman', 'Idris Uddin', 'Idris Khan', 'Idris Chowdhury', 'Idris Mahmood',
        'Karim Hussain', 'Karim Patel', 'Karim Rahman', 'Karim Hossain', 'Karim Ahmed',
        'Karim Khan', 'Karim Mahmood', 'Karim Sarker', 'Karim Khatun', 'Khadija Hassan',
        'Khadija Khan', 'Khadija Siddiqui', 'Khadija Ahmed', 'Khadija Uddin', 'Khadija Rahman',
        'Khadija Khatun', 'Khadija Hussain', 'Khadija Mahmood', 'Khadija Patel', 'Layla Hassan',
        'Layla Rahman', 'Layla Khan', 'Layla Ali', 'Layla Hussain', 'Layla Miah',
        'Layla Hossain', 'Layla Patel', 'Leila Begum', 'Leila Hossain', 'Leila Mahmood',
        'Leila Miah', 'Leila Khatun', 'Leila Siddiqui', 'Leila Sarker', 'Leila Islam',
        'Leila Farooq', 'Malik Hassan', 'Malik Hossain', 'Malik Miah', 'Malik Mahmood',
        'Malik Chowdhury', 'Malik Farooq', 'Malik Islam', 'Malik Siddiqui', 'Malik Ahmed',
        'Malik Uddin', 'Mariam Hossain', 'Mariam Farooq', 'Mariam Hussain', 'Mariam Haque',
        'Mariam Ali', 'Mariam Uddin', 'Mariam Ahmed', 'Mariam Khatun', 'Mohammed Hassan',
        'Mohammed Hussain', 'Mohammed Islam', 'Mohammed Haque', 'Mohammed Mahmood', 'Mohammed Miah',
        'Mohammed Rahman', 'Mohammed Begum', 'Mohammed Hossain', 'Mohammed Farooq', 'Nadia Ahmed',
        'Nadia Hassan', 'Nadia Hussain', 'Nadia Uddin', 'Nadia Begum', 'Nadia Hossain',
        'Nadia Farooq', 'Nadia Khan', 'Nadia Khatun', 'Nadia Mahmood', 'Nadia Siddiqui',
        'Noor Hassan', 'Noor Khan', 'Noor Mahmood', 'Noor Islam', 'Noor Patel',
        'Noor Hossain', 'Noor Sarker', 'Noor Haque', 'Noor Khatun', 'Noor Siddiqui',
        'Omar Hassan', 'Omar Mahmood', 'Omar Islam', 'Omar Miah', 'Omar Ahmed',
        'Omar Siddiqui', 'Rashid Begum', 'Rashid Ahmed', 'Rashid Khatun', 'Rashid Siddiqui',
        'Rashid Hussain', 'Rashid Uddin', 'Rashid Rahman', 'Rashid Hassan', 'Rashid Mahmood',
        'Rashid Haque', 'Rashid Ali', 'Rashid Farooq', 'Rashid Khan', 'Rayyan Hossain',
        'Rayyan Khatun', 'Rayyan Mahmood', 'Rayyan Farooq', 'Rayyan Khan', 'Rayyan Ali',
        'Rayyan Patel', 'Rayyan Hassan', 'Saif Hussain', 'Saif Hassan', 'Saif Ali',
        'Saif Siddiqui', 'Saif Mahmood', 'Saif Khan', 'Saif Sarker', 'Salma Sarker',
        'Salma Khan', 'Salma Rahman', 'Salma Uddin', 'Salma Hussain', 'Salma Mahmood',
        'Salma Ahmed', 'Salma Ali', 'Salma Patel', 'Salma Hassan', 'Salma Haque',
        'Salma Miah', 'Salma Farooq', 'Salma Khatun', 'Salma Chowdhury', 'Sara Sarker',
        'Sara Khan', 'Sara Rahman', 'Sara Patel', 'Sara Farooq', 'Sara Ahmed',
        'Sara Hossain', 'Sara Uddin', 'Sumaya Hassan', 'Sumaya Khan', 'Sumaya Hossain',
        'Sumaya Uddin', 'Sumaya Rahman', 'Sumaya Chowdhury', 'Sumaya Hussain', 'Tariq Uddin',
        'Tariq Chowdhury', 'Tariq Rahman', 'Tariq Sarker', 'Tariq Haque', 'Tariq Hussain',
        'Tariq Ahmed', 'Tariq Ali', 'Test Customer', 'Yusuf Siddiqui', 'Yusuf Mahmood',
        'Yusuf Hussain', 'Yusuf Miah', 'Yusuf Farooq', 'Yusuf Uddin', 'Yusuf Hassan',
        'Yusuf Chowdhury', 'Yusuf Hossain', 'Zainab Khan', 'Zainab Chowdhury', 'Zainab Ali',
        'Zainab Begum', 'Zainab Ahmed', 'Zainab Rahman', 'Zainab Mahmood', 'Zainab Miah',
        'Zainab Farooq', 'Zainab Haque', 'Zainab Siddiqui'
    ];

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

    $generatedCount = 0;
    
    for ($i = 0; $i < $count; $i++) {
        // Random rating (realistic distribution)
        $rand = mt_rand(1, 100);
        if ($rand <= 65) {
            $rating = 5;
            $reviewPool = $positiveReviews;
        } elseif ($rand <= 85) {
            $rating = 4;
            $reviewPool = $positiveReviews;
        } elseif ($rand <= 94) {
            $rating = 3;
            $reviewPool = $neutralReviews;
        } elseif ($rand <= 98) {
            $rating = 2;
            $reviewPool = $negativeReviews;
        } else {
            $rating = 1;
            $reviewPool = $negativeReviews;
        }
        
        // Random user name from the 273 users list
        $userName = $allUserNames[array_rand($allUserNames)];
        $firstName = explode(' ', $userName)[0];
        $email = strtolower(str_replace(' ', '.', $userName)) . '@email.com';
        
        // Get random review text
        $comment = $reviewPool[array_rand($reviewPool)];
        
        // Add product name occasionally
        if (mt_rand(1, 100) <= 40) {
            $comment .= " The {$productName} is definitely worth trying!";
        }
        
        // Random date within last 6 months
        $daysAgo = mt_rand(0, 180);
        $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
        
        // Insert review
        $stmt = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, user_name, user_email, rating, comment, created_at) 
            VALUES (?, NULL, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $productId,
            $userName,
            $email,
            $rating,
            $comment,
            $createdAt
        ]);
        
        $generatedCount++;
    }
    
    return $generatedCount;
}

// ============================================
// INSERT ALL 273 USERS
// ============================================
function insertAllUsers($pdo) {
    $users = [
        ['Ahmed Chowdhury', 'ahmed.chowdhury@email.com', '+8801712345601', 'House #12, Road #5, Banani', 'Dhaka', '1213'],
        ['Ahmed Khan', 'ahmed.khan@email.com', '+8801712345602', 'House #25, Road #8, Gulshan', 'Dhaka', '1212'],
        ['Ahmed Ahmed', 'ahmed.ahmed@email.com', '+8801712345603', 'House #7, Road #3, Dhanmondi', 'Dhaka', '1205'],
        ['Ahmed Sarker', 'ahmed.sarker@email.com', '+8801712345604', 'House #45, Road #12, Uttara', 'Dhaka', '1230'],
        ['Ahmed Mahmood', 'ahmed.mahmood@email.com', '+8801712345605', 'House #3, Road #7, Mirpur', 'Dhaka', '1216'],
        ['Ahmed Rahman', 'ahmed.rahman@email.com', '+8801712345606', 'House #19, Road #4, Mohammadpur', 'Dhaka', '1207'],
        ['Ahmed Uddin', 'ahmed.uddin@email.com', '+8801712345607', 'House #33, Road #9, Khilgaon', 'Dhaka', '1219'],
        ['Ahmed Hossain', 'ahmed.hossain@email.com', '+8801712345608', 'House #8, Road #2, Shantinagar', 'Dhaka', '1217'],
        ['Ahmed Miah', 'ahmed.miah@email.com', '+8801712345609', 'House #22, Road #6, Malibagh', 'Dhaka', '1217'],
        ['Aisha Begum', 'aisha.begum@email.com', '+8801812345610', 'House #15, Road #10, Bashundhara', 'Dhaka', '1229'],
        ['Aisha Islam', 'aisha.islam@email.com', '+8801812345611', 'House #28, Road #15, Baridhara', 'Dhaka', '1212'],
        ['Aisha Patel', 'aisha.patel@email.com', '+8801812345612', 'House #10, Road #3, Niketon', 'Dhaka', '1212'],
        ['Aisha Rahman', 'aisha.rahman@email.com', '+8801812345613', 'House #37, Road #11, Jigatola', 'Dhaka', '1209'],
        ['Aisha Hussain', 'aisha.hussain@email.com', '+8801812345614', 'House #5, Road #6, Lalmatia', 'Dhaka', '1207'],
        ['Aisha Ali', 'aisha.ali@email.com', '+8801812345615', 'House #42, Road #8, Green Road', 'Dhaka', '1205'],
        ['Aisha Haque', 'aisha.haque@email.com', '+8801812345616', 'House #18, Road #5, Malibagh', 'Dhaka', '1217'],
        ['Ali Khatun', 'ali.khatun@email.com', '+8801912345617', 'House #9, Road #2, Pallabi', 'Dhaka', '1216'],
        ['Ali Siddiqui', 'ali.siddiqui@email.com', '+8801912345618', 'House #30, Road #7, Mirpur-1', 'Dhaka', '1216'],
        ['Ali Islam', 'ali.islam@email.com', '+8801912345619', 'House #14, Road #4, Uttara', 'Dhaka', '1230'],
        ['Ali Hassan', 'ali.hassan@email.com', '+8801912345620', 'House #27, Road #9, Gulshan', 'Dhaka', '1212'],
        ['Ali Begum', 'ali.begum@email.com', '+8801912345621', 'House #6, Road #3, Banani', 'Dhaka', '1213'],
        ['Ali Ahmed', 'ali.ahmed@email.com', '+8801912345622', 'House #20, Road #6, Dhanmondi', 'Dhaka', '1205'],
        ['Ali Mahmood', 'ali.mahmood@email.com', '+8801912345623', 'House #35, Road #10, Mohammadpur', 'Dhaka', '1207'],
        ['Amira Farooq', 'amira.farooq@email.com', '+8801912345624', 'House #11, Road #5, Bashundhara', 'Dhaka', '1229'],
        ['Amira Ali', 'amira.ali@email.com', '+8801912345625', 'House #24, Road #8, Baridhara', 'Dhaka', '1212'],
        ['Amira Rahman', 'amira.rahman@email.com', '+8801912345626', 'House #4, Road #2, Jigatola', 'Dhaka', '1209'],
        ['Amira Sarker', 'amira.sarker@email.com', '+8801912345627', 'House #16, Road #7, Lalmatia', 'Dhaka', '1207'],
        ['Amira Miah', 'amira.miah@email.com', '+8801912345628', 'House #38, Road #11, Green Road', 'Dhaka', '1205'],
        ['Amira Chowdhury', 'amira.chowdhury@email.com', '+8801912345629', 'House #21, Road #6, Niketon', 'Dhaka', '1212'],
        ['Bilal Hossain', 'bilal.hossain@email.com', '+8801712345630', 'House #13, Road #4, Mirpur', 'Dhaka', '1216'],
        ['Bilal Sarker', 'bilal.sarker@email.com', '+8801712345631', 'House #26, Road #9, Khilgaon', 'Dhaka', '1219'],
        ['Bilal Begum', 'bilal.begum@email.com', '+8801712345632', 'House #40, Road #12, Shantinagar', 'Dhaka', '1217'],
        ['Bilal Miah', 'bilal.miah@email.com', '+8801712345633', 'House #17, Road #5, Malibagh', 'Dhaka', '1217'],
        ['Bilal Ali', 'bilal.ali@email.com', '+8801712345634', 'House #29, Road #8, Mohammadpur', 'Dhaka', '1207'],
        ['Bilal Patel', 'bilal.patel@email.com', '+8801712345635', 'House #1, Road #1, Uttara', 'Dhaka', '1230'],
        ['Bilal Ahmed', 'bilal.ahmed@email.com', '+8801712345636', 'House #32, Road #10, Bashundhara', 'Dhaka', '1229'],
        ['Bilal Islam', 'bilal.islam@email.com', '+8801712345637', 'House #46, Road #13, Baridhara', 'Dhaka', '1212'],
        ['Fatima Chowdhury', 'fatima.chowdhury@email.com', '+8801812345638', 'House #2, Road #2, Banani', 'Dhaka', '1213'],
        ['Fatima Uddin', 'fatima.uddin@email.com', '+8801812345639', 'House #34, Road #11, Gulshan', 'Dhaka', '1212'],
        ['Fatima Sarker', 'fatima.sarker@email.com', '+8801812345640', 'House #23, Road #7, Dhanmondi', 'Dhaka', '1205'],
        ['Fatima Hussain', 'fatima.hussain@email.com', '+8801812345641', 'House #39, Road #12, Jigatola', 'Dhaka', '1209'],
        ['Fatima Rahman', 'fatima.rahman@email.com', '+8801812345642', 'House #44, Road #14, Lalmatia', 'Dhaka', '1207'],
        ['Fatima Patel', 'fatima.patel@email.com', '+8801812345643', 'House #31, Road #9, Mirpur', 'Dhaka', '1216'],
        ['Fatima Khan', 'fatima.khan@email.com', '+8801812345644', 'House #47, Road #15, Mohakhali', 'Dhaka', '1212'],
        ['Hana Hassan', 'hana.hassan@email.com', '+8801912345645', 'House #36, Road #11, Bashundhara', 'Dhaka', '1229'],
        ['Hana Islam', 'hana.islam@email.com', '+8801912345646', 'House #41, Road #13, Baridhara', 'Dhaka', '1212'],
        ['Hana Sarker', 'hana.sarker@email.com', '+8801912345647', 'House #48, Road #16, Gulshan', 'Dhaka', '1212'],
        ['Hana Rahman', 'hana.rahman@email.com', '+8801912345648', 'House #49, Road #17, Banani', 'Dhaka', '1213'],
        ['Hana Ali', 'hana.ali@email.com', '+8801912345649', 'House #50, Road #18, Dhanmondi', 'Dhaka', '1205'],
        ['Hana Patel', 'hana.patel@email.com', '+8801912345650', 'House #51, Road #19, Jigatola', 'Dhaka', '1209'],
        ['Hana Uddin', 'hana.uddin@email.com', '+8801912345651', 'House #52, Road #20, Lalmatia', 'Dhaka', '1207'],
        ['Hana Begum', 'hana.begum@email.com', '+8801912345652', 'House #53, Road #21, Mirpur', 'Dhaka', '1216'],
        ['Hana Haque', 'hana.haque@email.com', '+8801912345653', 'House #54, Road #22, Khilgaon', 'Dhaka', '1219'],
        ['Hana Hussain', 'hana.hussain@email.com', '+8801912345654', 'House #55, Road #23, Shantinagar', 'Dhaka', '1217'],
        ['Hana Khatun', 'hana.khatun@email.com', '+8801912345655', 'House #56, Road #24, Malibagh', 'Dhaka', '1217'],
        ['Hamza Hossain', 'hamza.hossain@email.com', '+8801912345656', 'House #57, Road #25, Uttara', 'Dhaka', '1230'],
        ['Hamza Miah', 'hamza.miah@email.com', '+8801912345657', 'House #58, Road #26, Bashundhara', 'Dhaka', '1229'],
        ['Hamza Siddiqui', 'hamza.siddiqui@email.com', '+8801912345658', 'House #59, Road #27, Baridhara', 'Dhaka', '1212'],
        ['Hamza Begum', 'hamza.begum@email.com', '+8801912345659', 'House #60, Road #28, Gulshan', 'Dhaka', '1212'],
        ['Hamza Haque', 'hamza.haque@email.com', '+8801912345660', 'House #61, Road #29, Banani', 'Dhaka', '1213'],
        ['Hamza Hassan', 'hamza.hassan@email.com', '+8801912345661', 'House #62, Road #30, Dhanmondi', 'Dhaka', '1205'],
        ['Hamza Sarker', 'hamza.sarker@email.com', '+8801912345662', 'House #63, Road #31, Jigatola', 'Dhaka', '1209'],
        ['Hamza Chowdhury', 'hamza.chowdhury@email.com', '+8801912345663', 'House #64, Road #32, Lalmatia', 'Dhaka', '1207'],
        ['Hamza Rahman', 'hamza.rahman@email.com', '+8801912345664', 'House #65, Road #33, Mirpur', 'Dhaka', '1216'],
        ['Hassan Hassan', 'hassan.hassan@email.com', '+8801912345665', 'House #66, Road #34, Khilgaon', 'Dhaka', '1219'],
        ['Hassan Farooq', 'hassan.farooq@email.com', '+8801912345666', 'House #67, Road #35, Shantinagar', 'Dhaka', '1217'],
        ['Hassan Ali', 'hassan.ali@email.com', '+8801912345667', 'House #68, Road #36, Malibagh', 'Dhaka', '1217'],
        ['Hassan Mahmood', 'hassan.mahmood@email.com', '+8801912345668', 'House #69, Road #37, Uttara', 'Dhaka', '1230'],
        ['Hassan Hossain', 'hassan.hossain@email.com', '+8801912345669', 'House #70, Road #38, Bashundhara', 'Dhaka', '1229'],
        ['Hassan Islam', 'hassan.islam@email.com', '+8801912345670', 'House #71, Road #39, Baridhara', 'Dhaka', '1212'],
        ['Hassan Chowdhury', 'hassan.chowdhury@email.com', '+8801912345671', 'House #72, Road #40, Gulshan', 'Dhaka', '1212'],
        ['Hassan Rahman', 'hassan.rahman@email.com', '+8801912345672', 'House #73, Road #41, Banani', 'Dhaka', '1213'],
        ['Hassan Khatun', 'hassan.khatun@email.com', '+8801912345673', 'House #74, Road #42, Dhanmondi', 'Dhaka', '1205'],
        ['Ibrahim Begum', 'ibrahim.begum@email.com', '+8801712345674', 'House #75, Road #43, Jigatola', 'Dhaka', '1209'],
        ['Ibrahim Haque', 'ibrahim.haque@email.com', '+8801712345675', 'House #76, Road #44, Lalmatia', 'Dhaka', '1207'],
        ['Ibrahim Hussain', 'ibrahim.hussain@email.com', '+8801712345676', 'House #77, Road #45, Mirpur', 'Dhaka', '1216'],
        ['Ibrahim Miah', 'ibrahim.miah@email.com', '+8801712345677', 'House #78, Road #46, Khilgaon', 'Dhaka', '1219'],
        ['Ibrahim Patel', 'ibrahim.patel@email.com', '+8801712345678', 'House #79, Road #47, Shantinagar', 'Dhaka', '1217'],
        ['Ibrahim Sarker', 'ibrahim.sarker@email.com', '+8801712345679', 'House #80, Road #48, Malibagh', 'Dhaka', '1217'],
        ['Ibrahim Khan', 'ibrahim.khan@email.com', '+8801712345680', 'House #81, Road #49, Uttara', 'Dhaka', '1230'],
        ['Ibrahim Ahmed', 'ibrahim.ahmed@email.com', '+8801712345681', 'House #82, Road #50, Bashundhara', 'Dhaka', '1229'],
        ['Ibrahim Siddiqui', 'ibrahim.siddiqui@email.com', '+8801712345682', 'House #83, Road #51, Baridhara', 'Dhaka', '1212'],
        ['Ibrahim Khatun', 'ibrahim.khatun@email.com', '+8801712345683', 'House #84, Road #52, Gulshan', 'Dhaka', '1212'],
        ['Idris Siddiqui', 'idris.siddiqui@email.com', '+8801812345684', 'House #85, Road #53, Banani', 'Dhaka', '1213'],
        ['Idris Begum', 'idris.begum@email.com', '+8801812345685', 'House #86, Road #54, Dhanmondi', 'Dhaka', '1205'],
        ['Idris Miah', 'idris.miah@email.com', '+8801812345686', 'House #87, Road #55, Jigatola', 'Dhaka', '1209'],
        ['Idris Farooq', 'idris.farooq@email.com', '+8801812345687', 'House #88, Road #56, Lalmatia', 'Dhaka', '1207'],
        ['Idris Hossain', 'idris.hossain@email.com', '+8801812345688', 'House #89, Road #57, Mirpur', 'Dhaka', '1216'],
        ['Idris Ali', 'idris.ali@email.com', '+8801812345689', 'House #90, Road #58, Khilgaon', 'Dhaka', '1219'],
        ['Idris Khatun', 'idris.khatun@email.com', '+8801812345690', 'House #91, Road #59, Shantinagar', 'Dhaka', '1217'],
        ['Idris Rahman', 'idris.rahman@email.com', '+8801812345691', 'House #92, Road #60, Malibagh', 'Dhaka', '1217'],
        ['Idris Uddin', 'idris.uddin@email.com', '+8801812345692', 'House #93, Road #61, Uttara', 'Dhaka', '1230'],
        ['Idris Khan', 'idris.khan@email.com', '+8801812345693', 'House #94, Road #62, Bashundhara', 'Dhaka', '1229'],
        ['Idris Chowdhury', 'idris.chowdhury@email.com', '+8801812345694', 'House #95, Road #63, Baridhara', 'Dhaka', '1212'],
        ['Idris Mahmood', 'idris.mahmood@email.com', '+8801812345695', 'House #96, Road #64, Gulshan', 'Dhaka', '1212'],
        ['Karim Hussain', 'karim.hussain@email.com', '+8801912345696', 'House #97, Road #65, Banani', 'Dhaka', '1213'],
        ['Karim Patel', 'karim.patel@email.com', '+8801912345697', 'House #98, Road #66, Dhanmondi', 'Dhaka', '1205'],
        ['Karim Rahman', 'karim.rahman@email.com', '+8801912345698', 'House #99, Road #67, Jigatola', 'Dhaka', '1209'],
        ['Karim Hossain', 'karim.hossain@email.com', '+8801912345699', 'House #100, Road #68, Lalmatia', 'Dhaka', '1207'],
        ['Karim Ahmed', 'karim.ahmed@email.com', '+8801912345700', 'House #101, Road #69, Mirpur', 'Dhaka', '1216'],
        ['Karim Khan', 'karim.khan@email.com', '+8801912345701', 'House #102, Road #70, Khilgaon', 'Dhaka', '1219'],
        ['Karim Mahmood', 'karim.mahmood@email.com', '+8801912345702', 'House #103, Road #71, Shantinagar', 'Dhaka', '1217'],
        ['Karim Sarker', 'karim.sarker@email.com', '+8801912345703', 'House #104, Road #72, Malibagh', 'Dhaka', '1217'],
        ['Karim Khatun', 'karim.khatun@email.com', '+8801912345704', 'House #105, Road #73, Uttara', 'Dhaka', '1230'],
        ['Khadija Hassan', 'khadija.hassan@email.com', '+8801912345705', 'House #106, Road #74, Bashundhara', 'Dhaka', '1229'],
        ['Khadija Khan', 'khadija.khan@email.com', '+8801912345706', 'House #107, Road #75, Baridhara', 'Dhaka', '1212'],
        ['Khadija Siddiqui', 'khadija.siddiqui@email.com', '+8801912345707', 'House #108, Road #76, Gulshan', 'Dhaka', '1212'],
        ['Khadija Ahmed', 'khadija.ahmed@email.com', '+8801912345708', 'House #109, Road #77, Banani', 'Dhaka', '1213'],
        ['Khadija Uddin', 'khadija.uddin@email.com', '+8801912345709', 'House #110, Road #78, Dhanmondi', 'Dhaka', '1205'],
        ['Khadija Rahman', 'khadija.rahman@email.com', '+8801912345710', 'House #111, Road #79, Jigatola', 'Dhaka', '1209'],
        ['Khadija Khatun', 'khadija.khatun@email.com', '+8801912345711', 'House #112, Road #80, Lalmatia', 'Dhaka', '1207'],
        ['Khadija Hussain', 'khadija.hussain@email.com', '+8801912345712', 'House #113, Road #81, Mirpur', 'Dhaka', '1216'],
        ['Khadija Mahmood', 'khadija.mahmood@email.com', '+8801912345713', 'House #114, Road #82, Khilgaon', 'Dhaka', '1219'],
        ['Khadija Patel', 'khadija.patel@email.com', '+8801912345714', 'House #115, Road #83, Shantinagar', 'Dhaka', '1217'],
        ['Layla Hassan', 'layla.hassan@email.com', '+8801712345715', 'House #116, Road #84, Malibagh', 'Dhaka', '1217'],
        ['Layla Rahman', 'layla.rahman@email.com', '+8801712345716', 'House #117, Road #85, Uttara', 'Dhaka', '1230'],
        ['Layla Khan', 'layla.khan@email.com', '+8801712345717', 'House #118, Road #86, Bashundhara', 'Dhaka', '1229'],
        ['Layla Ali', 'layla.ali@email.com', '+8801712345718', 'House #119, Road #87, Baridhara', 'Dhaka', '1212'],
        ['Layla Hussain', 'layla.hussain@email.com', '+8801712345719', 'House #120, Road #88, Gulshan', 'Dhaka', '1212'],
        ['Layla Miah', 'layla.miah@email.com', '+8801712345720', 'House #121, Road #89, Banani', 'Dhaka', '1213'],
        ['Layla Hossain', 'layla.hossain@email.com', '+8801712345721', 'House #122, Road #90, Dhanmondi', 'Dhaka', '1205'],
        ['Layla Patel', 'layla.patel@email.com', '+8801712345722', 'House #123, Road #91, Jigatola', 'Dhaka', '1209'],
        ['Leila Begum', 'leila.begum@email.com', '+8801812345723', 'House #124, Road #92, Lalmatia', 'Dhaka', '1207'],
        ['Leila Hossain', 'leila.hossain@email.com', '+8801812345724', 'House #125, Road #93, Mirpur', 'Dhaka', '1216'],
        ['Leila Mahmood', 'leila.mahmood@email.com', '+8801812345725', 'House #126, Road #94, Khilgaon', 'Dhaka', '1219'],
        ['Leila Miah', 'leila.miah@email.com', '+8801812345726', 'House #127, Road #95, Shantinagar', 'Dhaka', '1217'],
        ['Leila Khatun', 'leila.khatun@email.com', '+8801812345727', 'House #128, Road #96, Malibagh', 'Dhaka', '1217'],
        ['Leila Siddiqui', 'leila.siddiqui@email.com', '+8801812345728', 'House #129, Road #97, Uttara', 'Dhaka', '1230'],
        ['Leila Sarker', 'leila.sarker@email.com', '+8801812345729', 'House #130, Road #98, Bashundhara', 'Dhaka', '1229'],
        ['Leila Islam', 'leila.islam@email.com', '+8801812345730', 'House #131, Road #99, Baridhara', 'Dhaka', '1212'],
        ['Leila Farooq', 'leila.farooq@email.com', '+8801812345731', 'House #132, Road #100, Gulshan', 'Dhaka', '1212'],
        ['Malik Hassan', 'malik.hassan@email.com', '+8801912345732', 'House #133, Road #101, Banani', 'Dhaka', '1213'],
        ['Malik Hossain', 'malik.hossain@email.com', '+8801912345733', 'House #134, Road #102, Dhanmondi', 'Dhaka', '1205'],
        ['Malik Miah', 'malik.miah@email.com', '+8801912345734', 'House #135, Road #103, Jigatola', 'Dhaka', '1209'],
        ['Malik Mahmood', 'malik.mahmood@email.com', '+8801912345735', 'House #136, Road #104, Lalmatia', 'Dhaka', '1207'],
        ['Malik Chowdhury', 'malik.chowdhury@email.com', '+8801912345736', 'House #137, Road #105, Mirpur', 'Dhaka', '1216'],
        ['Malik Farooq', 'malik.farooq@email.com', '+8801912345737', 'House #138, Road #106, Khilgaon', 'Dhaka', '1219'],
        ['Malik Islam', 'malik.islam@email.com', '+8801912345738', 'House #139, Road #107, Shantinagar', 'Dhaka', '1217'],
        ['Malik Siddiqui', 'malik.siddiqui@email.com', '+8801912345739', 'House #140, Road #108, Malibagh', 'Dhaka', '1217'],
        ['Malik Ahmed', 'malik.ahmed@email.com', '+8801912345740', 'House #141, Road #109, Uttara', 'Dhaka', '1230'],
        ['Malik Uddin', 'malik.uddin@email.com', '+8801912345741', 'House #142, Road #110, Bashundhara', 'Dhaka', '1229'],
        ['Mariam Hossain', 'mariam.hossain@email.com', '+8801912345742', 'House #143, Road #111, Baridhara', 'Dhaka', '1212'],
        ['Mariam Farooq', 'mariam.farooq@email.com', '+8801912345743', 'House #144, Road #112, Gulshan', 'Dhaka', '1212'],
        ['Mariam Hussain', 'mariam.hussain@email.com', '+8801912345744', 'House #145, Road #113, Banani', 'Dhaka', '1213'],
        ['Mariam Haque', 'mariam.haque@email.com', '+8801912345745', 'House #146, Road #114, Dhanmondi', 'Dhaka', '1205'],
        ['Mariam Ali', 'mariam.ali@email.com', '+8801912345746', 'House #147, Road #115, Jigatola', 'Dhaka', '1209'],
        ['Mariam Uddin', 'mariam.uddin@email.com', '+8801912345747', 'House #148, Road #116, Lalmatia', 'Dhaka', '1207'],
        ['Mariam Ahmed', 'mariam.ahmed@email.com', '+8801912345748', 'House #149, Road #117, Mirpur', 'Dhaka', '1216'],
        ['Mariam Khatun', 'mariam.khatun@email.com', '+8801912345749', 'House #150, Road #118, Khilgaon', 'Dhaka', '1219'],
        ['Mohammed Hassan', 'mohammed.hassan@email.com', '+8801912345750', 'House #151, Road #119, Shantinagar', 'Dhaka', '1217'],
        ['Mohammed Hussain', 'mohammed.hussain@email.com', '+8801912345751', 'House #152, Road #120, Malibagh', 'Dhaka', '1217'],
        ['Mohammed Islam', 'mohammed.islam@email.com', '+8801912345752', 'House #153, Road #121, Uttara', 'Dhaka', '1230'],
        ['Mohammed Haque', 'mohammed.haque@email.com', '+8801912345753', 'House #154, Road #122, Bashundhara', 'Dhaka', '1229'],
        ['Mohammed Mahmood', 'mohammed.mahmood@email.com', '+8801912345754', 'House #155, Road #123, Baridhara', 'Dhaka', '1212'],
        ['Mohammed Miah', 'mohammed.miah@email.com', '+8801912345755', 'House #156, Road #124, Gulshan', 'Dhaka', '1212'],
        ['Mohammed Rahman', 'mohammed.rahman@email.com', '+8801912345756', 'House #157, Road #125, Banani', 'Dhaka', '1213'],
        ['Mohammed Begum', 'mohammed.begum@email.com', '+8801912345757', 'House #158, Road #126, Dhanmondi', 'Dhaka', '1205'],
        ['Mohammed Hossain', 'mohammed.hossain@email.com', '+8801912345758', 'House #159, Road #127, Jigatola', 'Dhaka', '1209'],
        ['Mohammed Farooq', 'mohammed.farooq@email.com', '+8801912345759', 'House #160, Road #128, Lalmatia', 'Dhaka', '1207'],
        ['Nadia Ahmed', 'nadia.ahmed@email.com', '+8801712345760', 'House #161, Road #129, Mirpur', 'Dhaka', '1216'],
        ['Nadia Hassan', 'nadia.hassan@email.com', '+8801712345761', 'House #162, Road #130, Khilgaon', 'Dhaka', '1219'],
        ['Nadia Hussain', 'nadia.hussain@email.com', '+8801712345762', 'House #163, Road #131, Shantinagar', 'Dhaka', '1217'],
        ['Nadia Uddin', 'nadia.uddin@email.com', '+8801712345763', 'House #164, Road #132, Malibagh', 'Dhaka', '1217'],
        ['Nadia Begum', 'nadia.begum@email.com', '+8801712345764', 'House #165, Road #133, Uttara', 'Dhaka', '1230'],
        ['Nadia Hossain', 'nadia.hossain@email.com', '+8801712345765', 'House #166, Road #134, Bashundhara', 'Dhaka', '1229'],
        ['Nadia Farooq', 'nadia.farooq@email.com', '+8801712345766', 'House #167, Road #135, Baridhara', 'Dhaka', '1212'],
        ['Nadia Khan', 'nadia.khan@email.com', '+8801712345767', 'House #168, Road #136, Gulshan', 'Dhaka', '1212'],
        ['Nadia Khatun', 'nadia.khatun@email.com', '+8801712345768', 'House #169, Road #137, Banani', 'Dhaka', '1213'],
        ['Nadia Mahmood', 'nadia.mahmood@email.com', '+8801712345769', 'House #170, Road #138, Dhanmondi', 'Dhaka', '1205'],
        ['Nadia Siddiqui', 'nadia.siddiqui@email.com', '+8801712345770', 'House #171, Road #139, Jigatola', 'Dhaka', '1209'],
        ['Noor Hassan', 'noor.hassan@email.com', '+8801812345771', 'House #172, Road #140, Lalmatia', 'Dhaka', '1207'],
        ['Noor Khan', 'noor.khan@email.com', '+8801812345772', 'House #173, Road #141, Mirpur', 'Dhaka', '1216'],
        ['Noor Mahmood', 'noor.mahmood@email.com', '+8801812345773', 'House #174, Road #142, Khilgaon', 'Dhaka', '1219'],
        ['Noor Islam', 'noor.islam@email.com', '+8801812345774', 'House #175, Road #143, Shantinagar', 'Dhaka', '1217'],
        ['Noor Patel', 'noor.patel@email.com', '+8801812345775', 'House #176, Road #144, Malibagh', 'Dhaka', '1217'],
        ['Noor Hossain', 'noor.hossain@email.com', '+8801812345776', 'House #177, Road #145, Uttara', 'Dhaka', '1230'],
        ['Noor Sarker', 'noor.sarker@email.com', '+8801812345777', 'House #178, Road #146, Bashundhara', 'Dhaka', '1229'],
        ['Noor Haque', 'noor.haque@email.com', '+8801812345778', 'House #179, Road #147, Baridhara', 'Dhaka', '1212'],
        ['Noor Khatun', 'noor.khatun@email.com', '+8801812345779', 'House #180, Road #148, Gulshan', 'Dhaka', '1212'],
        ['Noor Siddiqui', 'noor.siddiqui@email.com', '+8801812345780', 'House #181, Road #149, Banani', 'Dhaka', '1213'],
        ['Omar Hassan', 'omar.hassan@email.com', '+8801912345781', 'House #182, Road #150, Dhanmondi', 'Dhaka', '1205'],
        ['Omar Mahmood', 'omar.mahmood@email.com', '+8801912345782', 'House #183, Road #151, Jigatola', 'Dhaka', '1209'],
        ['Omar Islam', 'omar.islam@email.com', '+8801912345783', 'House #184, Road #152, Lalmatia', 'Dhaka', '1207'],
        ['Omar Miah', 'omar.miah@email.com', '+8801912345784', 'House #185, Road #153, Mirpur', 'Dhaka', '1216'],
        ['Omar Ahmed', 'omar.ahmed@email.com', '+8801912345785', 'House #186, Road #154, Khilgaon', 'Dhaka', '1219'],
        ['Omar Siddiqui', 'omar.siddiqui@email.com', '+8801912345786', 'House #187, Road #155, Shantinagar', 'Dhaka', '1217'],
        ['Rashid Begum', 'rashid.begum@email.com', '+8801712345787', 'House #188, Road #156, Malibagh', 'Dhaka', '1217'],
        ['Rashid Ahmed', 'rashid.ahmed@email.com', '+8801712345788', 'House #189, Road #157, Uttara', 'Dhaka', '1230'],
        ['Rashid Khatun', 'rashid.khatun@email.com', '+8801712345789', 'House #190, Road #158, Bashundhara', 'Dhaka', '1229'],
        ['Rashid Siddiqui', 'rashid.siddiqui@email.com', '+8801712345790', 'House #191, Road #159, Baridhara', 'Dhaka', '1212'],
        ['Rashid Hussain', 'rashid.hussain@email.com', '+8801712345791', 'House #192, Road #160, Gulshan', 'Dhaka', '1212'],
        ['Rashid Uddin', 'rashid.uddin@email.com', '+8801712345792', 'House #193, Road #161, Banani', 'Dhaka', '1213'],
        ['Rashid Rahman', 'rashid.rahman@email.com', '+8801712345793', 'House #194, Road #162, Dhanmondi', 'Dhaka', '1205'],
        ['Rashid Hassan', 'rashid.hassan@email.com', '+8801712345794', 'House #195, Road #163, Jigatola', 'Dhaka', '1209'],
        ['Rashid Mahmood', 'rashid.mahmood@email.com', '+8801712345795', 'House #196, Road #164, Lalmatia', 'Dhaka', '1207'],
        ['Rashid Haque', 'rashid.haque@email.com', '+8801712345796', 'House #197, Road #165, Mirpur', 'Dhaka', '1216'],
        ['Rashid Ali', 'rashid.ali@email.com', '+8801712345797', 'House #198, Road #166, Khilgaon', 'Dhaka', '1219'],
        ['Rashid Farooq', 'rashid.farooq@email.com', '+8801712345798', 'House #199, Road #167, Shantinagar', 'Dhaka', '1217'],
        ['Rashid Khan', 'rashid.khan@email.com', '+8801712345799', 'House #200, Road #168, Malibagh', 'Dhaka', '1217'],
        ['Rayyan Hossain', 'rayyan.hossain@email.com', '+8801812345800', 'House #201, Road #169, Uttara', 'Dhaka', '1230'],
        ['Rayyan Khatun', 'rayyan.khatun@email.com', '+8801812345801', 'House #202, Road #170, Bashundhara', 'Dhaka', '1229'],
        ['Rayyan Mahmood', 'rayyan.mahmood@email.com', '+8801812345802', 'House #203, Road #171, Baridhara', 'Dhaka', '1212'],
        ['Rayyan Farooq', 'rayyan.farooq@email.com', '+8801812345803', 'House #204, Road #172, Gulshan', 'Dhaka', '1212'],
        ['Rayyan Khan', 'rayyan.khan@email.com', '+8801812345804', 'House #205, Road #173, Banani', 'Dhaka', '1213'],
        ['Rayyan Ali', 'rayyan.ali@email.com', '+8801812345805', 'House #206, Road #174, Dhanmondi', 'Dhaka', '1205'],
        ['Rayyan Patel', 'rayyan.patel@email.com', '+8801812345806', 'House #207, Road #175, Jigatola', 'Dhaka', '1209'],
        ['Rayyan Hassan', 'rayyan.hassan@email.com', '+8801812345807', 'House #208, Road #176, Lalmatia', 'Dhaka', '1207'],
        ['Saif Hussain', 'saif.hussain@email.com', '+8801912345808', 'House #209, Road #177, Mirpur', 'Dhaka', '1216'],
        ['Saif Hassan', 'saif.hassan@email.com', '+8801912345809', 'House #210, Road #178, Khilgaon', 'Dhaka', '1219'],
        ['Saif Ali', 'saif.ali@email.com', '+8801912345810', 'House #211, Road #179, Shantinagar', 'Dhaka', '1217'],
        ['Saif Siddiqui', 'saif.siddiqui@email.com', '+8801912345811', 'House #212, Road #180, Malibagh', 'Dhaka', '1217'],
        ['Saif Mahmood', 'saif.mahmood@email.com', '+8801912345812', 'House #213, Road #181, Uttara', 'Dhaka', '1230'],
        ['Saif Khan', 'saif.khan@email.com', '+8801912345813', 'House #214, Road #182, Bashundhara', 'Dhaka', '1229'],
        ['Saif Sarker', 'saif.sarker@email.com', '+8801912345814', 'House #215, Road #183, Baridhara', 'Dhaka', '1212'],
        ['Salma Sarker', 'salma.sarker@email.com', '+8801912345815', 'House #216, Road #184, Gulshan', 'Dhaka', '1212'],
        ['Salma Khan', 'salma.khan@email.com', '+8801912345816', 'House #217, Road #185, Banani', 'Dhaka', '1213'],
        ['Salma Rahman', 'salma.rahman@email.com', '+8801912345817', 'House #218, Road #186, Dhanmondi', 'Dhaka', '1205'],
        ['Salma Uddin', 'salma.uddin@email.com', '+8801912345818', 'House #219, Road #187, Jigatola', 'Dhaka', '1209'],
        ['Salma Hussain', 'salma.hussain@email.com', '+8801912345819', 'House #220, Road #188, Lalmatia', 'Dhaka', '1207'],
        ['Salma Mahmood', 'salma.mahmood@email.com', '+8801912345820', 'House #221, Road #189, Mirpur', 'Dhaka', '1216'],
        ['Salma Ahmed', 'salma.ahmed@email.com', '+8801912345821', 'House #222, Road #190, Khilgaon', 'Dhaka', '1219'],
        ['Salma Ali', 'salma.ali@email.com', '+8801912345822', 'House #223, Road #191, Shantinagar', 'Dhaka', '1217'],
        ['Salma Patel', 'salma.patel@email.com', '+8801912345823', 'House #224, Road #192, Malibagh', 'Dhaka', '1217'],
        ['Salma Hassan', 'salma.hassan@email.com', '+8801912345824', 'House #225, Road #193, Uttara', 'Dhaka', '1230'],
        ['Salma Haque', 'salma.haque@email.com', '+8801912345825', 'House #226, Road #194, Bashundhara', 'Dhaka', '1229'],
        ['Salma Miah', 'salma.miah@email.com', '+8801912345826', 'House #227, Road #195, Baridhara', 'Dhaka', '1212'],
        ['Salma Farooq', 'salma.farooq@email.com', '+8801912345827', 'House #228, Road #196, Gulshan', 'Dhaka', '1212'],
        ['Salma Khatun', 'salma.khatun@email.com', '+8801912345828', 'House #229, Road #197, Banani', 'Dhaka', '1213'],
        ['Salma Chowdhury', 'salma.chowdhury@email.com', '+8801912345829', 'House #230, Road #198, Dhanmondi', 'Dhaka', '1205'],
        ['Sara Sarker', 'sara.sarker@email.com', '+8801912345830', 'House #231, Road #199, Jigatola', 'Dhaka', '1209'],
        ['Sara Khan', 'sara.khan@email.com', '+8801912345831', 'House #232, Road #200, Lalmatia', 'Dhaka', '1207'],
        ['Sara Rahman', 'sara.rahman@email.com', '+8801912345832', 'House #233, Road #201, Mirpur', 'Dhaka', '1216'],
        ['Sara Patel', 'sara.patel@email.com', '+8801912345833', 'House #234, Road #202, Khilgaon', 'Dhaka', '1219'],
        ['Sara Farooq', 'sara.farooq@email.com', '+8801912345834', 'House #235, Road #203, Shantinagar', 'Dhaka', '1217'],
        ['Sara Ahmed', 'sara.ahmed@email.com', '+8801912345835', 'House #236, Road #204, Malibagh', 'Dhaka', '1217'],
        ['Sara Hossain', 'sara.hossain@email.com', '+8801912345836', 'House #237, Road #205, Uttara', 'Dhaka', '1230'],
        ['Sara Uddin', 'sara.uddin@email.com', '+8801912345837', 'House #238, Road #206, Bashundhara', 'Dhaka', '1229'],
        ['Sumaya Hassan', 'sumaya.hassan@email.com', '+8801912345838', 'House #239, Road #207, Baridhara', 'Dhaka', '1212'],
        ['Sumaya Khan', 'sumaya.khan@email.com', '+8801912345839', 'House #240, Road #208, Gulshan', 'Dhaka', '1212'],
        ['Sumaya Hossain', 'sumaya.hossain@email.com', '+8801912345840', 'House #241, Road #209, Banani', 'Dhaka', '1213'],
        ['Sumaya Uddin', 'sumaya.uddin@email.com', '+8801912345841', 'House #242, Road #210, Dhanmondi', 'Dhaka', '1205'],
        ['Sumaya Rahman', 'sumaya.rahman@email.com', '+8801912345842', 'House #243, Road #211, Jigatola', 'Dhaka', '1209'],
        ['Sumaya Chowdhury', 'sumaya.chowdhury@email.com', '+8801912345843', 'House #244, Road #212, Lalmatia', 'Dhaka', '1207'],
        ['Sumaya Hussain', 'sumaya.hussain@email.com', '+8801912345844', 'House #245, Road #213, Mirpur', 'Dhaka', '1216'],
        ['Tariq Uddin', 'tariq.uddin@email.com', '+8801712345845', 'House #246, Road #214, Khilgaon', 'Dhaka', '1219'],
        ['Tariq Chowdhury', 'tariq.chowdhury@email.com', '+8801712345846', 'House #247, Road #215, Shantinagar', 'Dhaka', '1217'],
        ['Tariq Rahman', 'tariq.rahman@email.com', '+8801712345847', 'House #248, Road #216, Malibagh', 'Dhaka', '1217'],
        ['Tariq Sarker', 'tariq.sarker@email.com', '+8801712345848', 'House #249, Road #217, Uttara', 'Dhaka', '1230'],
        ['Tariq Haque', 'tariq.haque@email.com', '+8801712345849', 'House #250, Road #218, Bashundhara', 'Dhaka', '1229'],
        ['Tariq Hussain', 'tariq.hussain@email.com', '+8801712345850', 'House #251, Road #219, Baridhara', 'Dhaka', '1212'],
        ['Tariq Ahmed', 'tariq.ahmed@email.com', '+8801712345851', 'House #252, Road #220, Gulshan', 'Dhaka', '1212'],
        ['Tariq Ali', 'tariq.ali@email.com', '+8801712345852', 'House #253, Road #221, Banani', 'Dhaka', '1213'],
        ['Test Customer', 'test.customer@email.com', '+8801712345853', 'House #254, Road #222, Dhanmondi', 'Dhaka', '1205'],
        ['Yusuf Siddiqui', 'yusuf.siddiqui@email.com', '+8801812345854', 'House #255, Road #223, Jigatola', 'Dhaka', '1209'],
        ['Yusuf Mahmood', 'yusuf.mahmood@email.com', '+8801812345855', 'House #256, Road #224, Lalmatia', 'Dhaka', '1207'],
        ['Yusuf Hussain', 'yusuf.hussain@email.com', '+8801812345856', 'House #257, Road #225, Mirpur', 'Dhaka', '1216'],
        ['Yusuf Miah', 'yusuf.miah@email.com', '+8801812345857', 'House #258, Road #226, Khilgaon', 'Dhaka', '1219'],
        ['Yusuf Farooq', 'yusuf.farooq@email.com', '+8801812345858', 'House #259, Road #227, Shantinagar', 'Dhaka', '1217'],
        ['Yusuf Uddin', 'yusuf.uddin@email.com', '+8801812345859', 'House #260, Road #228, Malibagh', 'Dhaka', '1217'],
        ['Yusuf Hassan', 'yusuf.hassan@email.com', '+8801812345860', 'House #261, Road #229, Uttara', 'Dhaka', '1230'],
        ['Yusuf Chowdhury', 'yusuf.chowdhury@email.com', '+8801812345861', 'House #262, Road #230, Bashundhara', 'Dhaka', '1229'],
        ['Yusuf Hossain', 'yusuf.hossain@email.com', '+8801812345862', 'House #263, Road #231, Baridhara', 'Dhaka', '1212'],
        ['Zainab Khan', 'zainab.khan@email.com', '+8801912345863', 'House #264, Road #232, Gulshan', 'Dhaka', '1212'],
        ['Zainab Chowdhury', 'zainab.chowdhury@email.com', '+8801912345864', 'House #265, Road #233, Banani', 'Dhaka', '1213'],
        ['Zainab Ali', 'zainab.ali@email.com', '+8801912345865', 'House #266, Road #234, Dhanmondi', 'Dhaka', '1205'],
        ['Zainab Begum', 'zainab.begum@email.com', '+8801912345866', 'House #267, Road #235, Jigatola', 'Dhaka', '1209'],
        ['Zainab Ahmed', 'zainab.ahmed@email.com', '+8801912345867', 'House #268, Road #236, Lalmatia', 'Dhaka', '1207'],
        ['Zainab Rahman', 'zainab.rahman@email.com', '+8801912345868', 'House #269, Road #237, Mirpur', 'Dhaka', '1216'],
        ['Zainab Mahmood', 'zainab.mahmood@email.com', '+8801912345869', 'House #270, Road #238, Khilgaon', 'Dhaka', '1219'],
        ['Zainab Miah', 'zainab.miah@email.com', '+8801912345870', 'House #271, Road #239, Shantinagar', 'Dhaka', '1217'],
        ['Zainab Farooq', 'zainab.farooq@email.com', '+8801912345871', 'House #272, Road #240, Malibagh', 'Dhaka', '1217'],
        ['Zainab Haque', 'zainab.haque@email.com', '+8801912345872', 'House #273, Road #241, Uttara', 'Dhaka', '1230'],
        ['Zainab Siddiqui', 'zainab.siddiqui@email.com', '+8801912345873', 'House #274, Road #242, Bashundhara', 'Dhaka', '1229'],
        ['Test user', 'user@test.com', '+8801712345853', 'House #275, Road #243, Dhanmondi', 'Dhaka', '1205']
    ];
    
    $count = 0;
    $hashedPassword = password_hash('user123', PASSWORD_DEFAULT);
    
    foreach ($users as $user) {
        try {
            // Check if user already exists by email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$user[1]]);
            if ($stmt->fetch()) {
                // Update existing user
                $updateStmt = $pdo->prepare("UPDATE users SET 
                    name = ?, phone = ?, address = ?, city = ?, postal_code = ? 
                    WHERE email = ?");
                $updateStmt->execute([$user[0], $user[2], $user[3], $user[4], $user[5], $user[1]]);
                $count++;
            } else {
                // Insert new user
                $insertStmt = $pdo->prepare("INSERT INTO users 
                    (name, email, password, phone, address, city, postal_code, role, tier, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'Bronze', NOW())");
                $insertStmt->execute([$user[0], $user[1], $hashedPassword, $user[2], $user[3], $user[4], $user[5]]);
                $count++;
            }
        } catch (PDOException $e) {
            error_log("Error inserting user {$user[0]}: " . $e->getMessage());
        }
    }
    
    return $count;
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
    // 2. DELETE COUPON USAGE
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM coupon_usage");
        $stmt->execute();
        $results['coupon_usage'] = $stmt->rowCount() . " rows deleted";
    } catch (PDOException $e) {
        $results['coupon_usage'] = "Table not found or already empty";
    }
    
    // ============================================
    // 3. DELETE ALL COUPONS
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons");
        $stmt->execute();
        $results['coupons_deleted'] = $stmt->rowCount() . " coupons deleted";
    } catch (PDOException $e) {
        $results['coupons'] = "Coupons table not found";
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
    // 5. DELETE REVIEWS (will be regenerated)
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews");
        $stmt->execute();
        $results['reviews_deleted'] = $stmt->rowCount() . " rows deleted";
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
    // 7. DELETE ALL USERS EXCEPT ADMIN (id=1)
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id != 1");
        $stmt->execute();
        $results['users_deleted'] = $stmt->rowCount() . " users deleted (kept admin id=1)";
    } catch (PDOException $e) {
        $errors['users'] = $e->getMessage();
    }
    
    // ============================================
    // 8. INSERT ALL 273 USERS
    // ============================================
    try {
        $usersInserted = insertAllUsers($pdo);
        $results['users_inserted'] = $usersInserted . " users inserted/updated";
    } catch (PDOException $e) {
        $errors['users_insert'] = $e->getMessage();
    }
    
    // ============================================
    // 9. RESET ADMIN USER (id = 1)
    // ============================================
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
            total_spent = 150000,
            created_at = NOW()
            WHERE id = 1");
        $stmt->execute([$hashedPassword]);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = 1");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, address, city, postal_code, role, tier, tier_expiry, total_spent, created_at) 
                                   VALUES (1, 'Admin User', 'admin@attar.com', ?, '+880 1234 567890', 'House #42, Road #12, Banani', 'Dhaka', '1213', 'admin', 'Platinum', DATE_ADD(NOW(), INTERVAL 365 DAY), 150000, NOW())");
            $stmt->execute([$hashedPassword]);
            $results['admin_created'] = "Admin user created with id 1 (Platinum tier)";
        } else {
            $results['admin_reset'] = "Admin user reset to Platinum tier";
        }
    } catch (PDOException $e) {
        $errors['admin_reset'] = $e->getMessage();
    }
    
    // ============================================
    // 10. RESET PRODUCTS (id 1-8)
    // ============================================
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id > 8");
        $stmt->execute();
        $results['products_deleted'] = $stmt->rowCount() . " products with id > 8 deleted";
    } catch (PDOException $e) {
        $errors['products_delete'] = $e->getMessage();
    }
    
    $sampleProducts = [
        1 => ['Royal Oudh', 'Arabian Oud', 'Oudh', 2990, 50, 'Premium royal oudh attar with long-lasting fragrance.', 'uploads/Screenshot_2026-05-28_233753.png'],
        2 => ['Musk Al Haramain', 'Harramain', 'Musk', 1890, 75, 'Traditional musk attar that captivates the senses.', 'uploads/Screenshot_2026-05-28_233339.png'],
        3 => ['Rose Attar', 'Swiss Arabian', 'Rose', 990, 100, 'Pure rose petal attar extracted from Damask roses.', 'uploads/Screenshot_2026-05-28_233510.png'],
        4 => ['Amber Oudh', 'Ajmal', 'Amber', 2490, 40, 'Amber and oudh blend for a warm, sensual experience.', 'uploads/Screenshot_2026-05-28_233406.png'],
        5 => ['Sandalwood Classic', 'Mysore', 'Sandalwood', 1590, 60, 'Pure sandalwood oil from Mysore.', 'uploads/Screenshot_2026-05-25_181536.png'],
        6 => ['Jasmine Supreme', 'Al Haramain', 'Jasmine', 1290, 85, 'Exquisite jasmine attar that reminds of blooming gardens.', 'uploads/Screenshot_2026-05-25_182001.png'],
        7 => ['Oudh Al Misk', 'Abdul Samad Al Qurashi', 'Oudh/Musk', 3590, 30, 'Premium blend of Cambodian oudh and white musk.', 'uploads/Screenshot_2026-05-25_181943.png'],
        8 => ['Saffron Royale', 'Rasasi', 'Saffron', 2190, 45, 'Luxury saffron-infused attar with woody undertones.', 'uploads/Screenshot_2026-05-28_233839.png']
    ];
    
    foreach ($sampleProducts as $id => $p) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()) {
                $updateStmt = $pdo->prepare("UPDATE products SET name = ?, brand = ?, fragrance = ?, price = ?, stock = ?, description = ?, image = ? WHERE id = ?");
                $updateStmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $id]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO products (id, name, brand, fragrance, price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([$id, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]]);
            }
        } catch (PDOException $e) {
            $errors["product_$id"] = $e->getMessage();
        }
    }
    $results['products'] = count($sampleProducts) . " products preserved (id 1-8)";
    
    // ============================================
    // 11. GENERATE REVIEWS (50 per product)
    // ============================================
    $reviewResults = [];
    if ($generateReviews === true || $generateReviews === 'true' || $generateReviews === 1) {
        $reviewsPerProduct = 50;
        $totalReviewsGenerated = 0;
        
        foreach ($sampleProducts as $id => $p) {
            $productName = $p[0];
            $generated = generateReviewsForProduct($pdo, $id, $productName, $reviewsPerProduct);
            $totalReviewsGenerated += $generated;
            $reviewResults[] = "{$productName}: {$generated} reviews generated";
        }
        
        $results['reviews_generated'] = $totalReviewsGenerated . " total reviews generated (50 per product)";
        $results['review_details'] = $reviewResults;
    } else {
        $results['reviews'] = "⚠️ Reviews were NOT generated (set generate_reviews=false to skip)";
    }
    
    // ============================================
    // 12. UPDATE PRODUCT RATINGS AFTER REVIEWS GENERATED
    // ============================================
    try {
        $pdo->exec("
            UPDATE products p
            SET 
                p.ratings = (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id),
                p.reviews = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id)
        ");
        $results['ratings_updated'] = "✅ Product ratings updated after review generation";
    } catch (PDOException $e) {
        $errors['ratings_update'] = "⚠️ Error updating ratings: " . $e->getMessage();
    }
    
    // ============================================
    // 13. RESET BLOGS (id 1-2)
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
            'content' => '<p>Attar making is an ancient art that has been passed down through generations for over 5000 years.</p>',
            'image' => 'uploads/Screenshot_2026-05-28_233339.png',
            'status' => 'published', 'tags' => 'attar,guide,traditional', 'views' => 0
        ],
        2 => [
            'title' => 'Best Oudh Fragrances for Winter Season',
            'category' => 'guide', 'author' => 'Admin', 'read_time' => 6,
            'excerpt' => 'Find the perfect oudh fragrance for the cold season.',
            'content' => '<p>Winter calls for warm, rich fragrances that linger in the cold air.</p>',
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
    // 14. RESET CAROUSEL SLIDES (id 1-3)
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
    $results['carousel_slides'] = count($carouselSlides) . " carousel slides preserved";
    
    // ============================================
    // 15. RESET FEATURES (id 1-6)
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
        2 => ['fas fa-truck', 'Free Shipping', 'Free delivery on orders over ৳2000', 'shop.html', 2],
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
    $results['features'] = count($features) . " features preserved";
    
    // ============================================
    // 16. RESET HOMEPAGE CONTENT (id 1-14)
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
    $results['homepage_content'] = count($homepageContent) . " homepage sections preserved";
    
    // ============================================
    // 17. CREATE TRIGGERS FOR AUTO UPDATING RATINGS
    // ============================================
    try {
        // Drop existing triggers
        $pdo->exec("DROP TRIGGER IF EXISTS update_product_ratings_on_review_insert");
        $pdo->exec("DROP TRIGGER IF EXISTS update_product_ratings_on_review_update");
        $pdo->exec("DROP TRIGGER IF EXISTS update_product_ratings_on_review_delete");
        
        // Create trigger for INSERT
        $pdo->exec("
            CREATE TRIGGER update_product_ratings_on_review_insert
            AFTER INSERT ON reviews
            FOR EACH ROW
            BEGIN
                UPDATE products p
                SET 
                    p.ratings = (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id),
                    p.reviews = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id)
                WHERE p.id = NEW.product_id;
            END
        ");
        
        // Create trigger for UPDATE
        $pdo->exec("
            CREATE TRIGGER update_product_ratings_on_review_update
            AFTER UPDATE ON reviews
            FOR EACH ROW
            BEGIN
                UPDATE products p
                SET 
                    p.ratings = (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id),
                    p.reviews = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id)
                WHERE p.id = NEW.product_id;
            END
        ");
        
        // Create trigger for DELETE
        $pdo->exec("
            CREATE TRIGGER update_product_ratings_on_review_delete
            AFTER DELETE ON reviews
            FOR EACH ROW
            BEGIN
                UPDATE products p
                SET 
                    p.ratings = (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id),
                    p.reviews = (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id)
                WHERE p.id = OLD.product_id;
            END
        ");
        
        $results['triggers_created'] = "✅ Product rating triggers created successfully";
    } catch (PDOException $e) {
        $errors['triggers'] = "⚠️ Error creating triggers: " . $e->getMessage();
    }
    
    // ============================================
    // 18. RESET AUTO_INCREMENT VALUES
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
        $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 276");
        $pdo->exec("ALTER TABLE coupons AUTO_INCREMENT = 1");
        $results['auto_increment'] = "Auto-increment values reset (users next id: 276)";
    } catch (PDOException $e) {
        $errors['auto_increment'] = $e->getMessage();
    }
    
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => '✅ Database reset successfully! 273 users inserted. ' . ($generateReviews ? '50 reviews generated per product.' : 'No reviews generated.'),
        'results' => $results,
        'errors' => $errors,
        'default_users' => [
            'admin' => [
                'email' => 'admin@attar.com',
                'password' => 'admin123',
                'tier' => 'Platinum',
                'discount' => '12%'
            ],
            'test_user' => [
                'email' => 'user@test.com',
                'password' => 'user123',
                'tier' => 'Bronze',
                'discount' => '0%'
            ]
        ],
        'preserved_ids' => [
            'users' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275],
            'products' => [1, 2, 3, 4, 5, 6, 7, 8],
            'blogs' => [1, 2],
            'carousel_slides' => [1, 2, 3],
            'features' => [1, 2, 3, 4, 5, 6],
            'homepage_content' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]
        ],
        'total_users_inserted' => 275 // Admin (id=1) + 274 users = 275 total
    ];
    
    echo json_encode($response);
    
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