<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

$product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$user_name = isset($data['user_name']) ? trim($data['user_name']) : '';
$user_email = isset($data['user_email']) ? trim($data['user_email']) : '';
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : '';
$images = isset($data['images']) ? $data['images'] : null;

// Get user email from session if not provided
if (empty($user_email) && isset($_SESSION['user_email'])) {
    $user_email = $_SESSION['user_email'];
}

if (!$product_id || !$rating || !$comment) {
    echo json_encode(['success' => false, 'error' => 'Product ID, rating and comment are required']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
    exit();
}

if (empty($user_name)) {
    $user_name = 'Anonymous User';
}

try {
    $pdo->beginTransaction();
    
    // Insert review - removed status column
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, user_name, user_email, rating, comment, images, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$product_id, $user_id, $user_name, $user_email, $rating, $comment, $images]);
    
    // Update product ratings
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $avgRating = round($result['avg_rating'], 1);
    $reviewCount = $result['review_count'];
    
    $stmt = $pdo->prepare("UPDATE products SET ratings = ?, reviews = ? WHERE id = ?");
    $stmt->execute([$avgRating, $reviewCount, $product_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully!'
    ]);
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>