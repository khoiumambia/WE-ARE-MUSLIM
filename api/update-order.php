<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// config.php is in the same directory (api folder)
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
    
    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>