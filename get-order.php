<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// config.php is in the same directory
if (!file_exists(__DIR__ . '/config.php')) {
    echo json_encode(['success' => false, 'error' => 'Config file not found in ' . __DIR__]);
    exit();
}

require_once __DIR__ . '/config.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
    $stmt->execute([$order_id, $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>