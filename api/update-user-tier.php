<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$total_spent = $data['total_spent'] ?? 0;

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

// Tier thresholds based on total spent (in BDT)
// Bronze: 0 - 19,999 (0% discount)
// Silver: 20,000 - 49,999 (5% discount)
// Gold: 50,000 - 99,999 (10% discount)
// Platinum: 100,000+ (12% discount)

try {
    $tier = 'Bronze';
    $discount = 0;
    
    if ($total_spent >= 100000) {
        $tier = 'Platinum';
        $discount = 12;
    } elseif ($total_spent >= 50000) {
        $tier = 'Gold';
        $discount = 10;
    } elseif ($total_spent >= 20000) {
        $tier = 'Silver';
        $discount = 5;
    } else {
        $tier = 'Bronze';
        $discount = 0;
    }
    
    // Set tier expiry to 1 month from now
    $expiryDate = date('Y-m-d', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("UPDATE users SET tier = ?, tier_expiry = ?, total_spent = ? WHERE email = ?");
    $stmt->execute([$tier, $expiryDate, $total_spent, $email]);
    
    echo json_encode([
        'success' => true,
        'tier' => $tier,
        'discount' => $discount,
        'expiry' => $expiryDate,
        'total_spent' => $total_spent
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>