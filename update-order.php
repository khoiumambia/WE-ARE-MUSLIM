<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$status = $data['status'] ?? null;
$tracking_number = $data['tracking_number'] ?? null;
$payment_confirmed = $data['payment_confirmed'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit();
}

try {
    $updates = [];
    $params = [];
    
    if ($status !== null) {
        $updates[] = "status = ?";
        $params[] = $status;
    }
    if ($tracking_number !== null) {
        $updates[] = "tracking_number = ?";
        $params[] = $tracking_number;
    }
    if ($payment_confirmed !== null) {
        $updates[] = "payment_confirmed = ?";
        $params[] = $payment_confirmed;
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'No updates provided']);
        exit();
    }
    
    $params[] = $id;
    $sql = "UPDATE orders SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // If payment was confirmed, update user's total_spent and tier
    if ($payment_confirmed == 1) {
        $stmt = $pdo->prepare("SELECT customer_email, total FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            updateUserSpendingAndTier($pdo, $order['customer_email'], $order['total']);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function updateUserSpendingAndTier($pdo, $email, $orderTotal) {
    try {
        // Update total_spent
        $stmt = $pdo->prepare("UPDATE users SET total_spent = total_spent + ? WHERE email = ?");
        $stmt->execute([$orderTotal, $email]);
        
        // Update tier based on new total_spent
        $stmt = $pdo->prepare("
            UPDATE users SET 
                tier = CASE
                    WHEN total_spent >= 100000 THEN 'Platinum'
                    WHEN total_spent >= 50000 THEN 'Gold'
                    WHEN total_spent >= 20000 THEN 'Silver'
                    ELSE 'Bronze'
                END,
                tier_expiry = DATE_ADD(NOW(), INTERVAL 
                    CASE
                        WHEN total_spent >= 100000 THEN 365
                        WHEN total_spent >= 50000 THEN 180
                        WHEN total_spent >= 20000 THEN 90
                        ELSE 30
                    END DAY
                )
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        return true;
    } catch(PDOException $e) {
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}
?>