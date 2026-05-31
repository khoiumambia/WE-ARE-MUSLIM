<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$email = $_GET['email'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT tier, tier_expiry, total_spent FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    
    // Check if tier expired
    $today = date('Y-m-d');
    if ($user['tier_expiry'] && $user['tier_expiry'] < $today) {
        // Tier expired, reset to Bronze
        $user['tier'] = 'Bronze';
        $user['tier_expiry'] = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("UPDATE users SET tier = 'Bronze', tier_expiry = ? WHERE email = ?");
        $stmt->execute([$user['tier_expiry'], $email]);
    }
    
    // Calculate discount based on tier
    $discount = 0;
    switch($user['tier']) {
        case 'Platinum': $discount = 12; break;
        case 'Gold': $discount = 10; break;
        case 'Silver': $discount = 5; break;
        default: $discount = 0;
    }
    
    echo json_encode([
        'success' => true,
        'tier' => $user['tier'],
        'discount' => $discount,
        'expiry' => $user['tier_expiry'],
        'total_spent' => (float)($user['total_spent'] ?? 0)
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>