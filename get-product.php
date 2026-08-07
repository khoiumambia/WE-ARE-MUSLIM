<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>