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
    // Get user info
    $stmt = $pdo->prepare("SELECT id, name, tier, tier_expiry FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    
    // Calculate total spent directly from orders table (CONFIRMED payments only)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as total_spent 
        FROM orders 
        WHERE customer_email = ? AND payment_confirmed = 1
    ");
    $stmt->execute([$email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSpent = floatval($result['total_spent']);
    
    // Determine correct tier based on total_spent
    $today = date('Y-m-d');
    $currentTier = $user['tier'];
    $tierExpiry = $user['tier_expiry'];
    
    if ($totalSpent >= 100000) {
        $correctTier = 'Platinum';
        $expiryDays = 365;
    } elseif ($totalSpent >= 50000) {
        $correctTier = 'Gold';
        $expiryDays = 180;
    } elseif ($totalSpent >= 20000) {
        $correctTier = 'Silver';
        $expiryDays = 90;
    } else {
        $correctTier = 'Bronze';
        $expiryDays = 30;
    }
    
    $newExpiry = date('Y-m-d', strtotime("+$expiryDays days"));
    $needsUpdate = false;
    
    // Check if tier needs update
    if ($currentTier !== $correctTier) {
        $needsUpdate = true;
        echo "Tier changed from $currentTier to $correctTier\n";
    }
    
    // Check if expiry is passed
    if ($tierExpiry && $tierExpiry < $today) {
        $needsUpdate = true;
        echo "Tier expired, renewing\n";
    }
    
    // Update user if needed
    if ($needsUpdate) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET tier = ?, tier_expiry = ?, total_spent = ? 
            WHERE email = ?
        ");
        $stmt->execute([$correctTier, $newExpiry, $totalSpent, $email]);
        
        $currentTier = $correctTier;
        $tierExpiry = $newExpiry;
        
        error_log("User $email: Tier updated to $correctTier, Total spent: $totalSpent");
    } else {
        // Still update total_spent even if tier didn't change
        $stmt = $pdo->prepare("UPDATE users SET total_spent = ? WHERE email = ?");
        $stmt->execute([$totalSpent, $email]);
    }
    
    // Calculate discount based on tier
    $discount = 0;
    switch($currentTier) {
        case 'Platinum': $discount = 12; break;
        case 'Gold': $discount = 10; break;
        case 'Silver': $discount = 5; break;
        default: $discount = 0;
    }
    
    echo json_encode([
        'success' => true,
        'tier' => $currentTier,
        'discount' => $discount,
        'expiry' => $tierExpiry,
        'total_spent' => $totalSpent
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>