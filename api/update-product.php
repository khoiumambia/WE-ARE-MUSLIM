<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit();
}

$id = $data['id'] ?? 0;
$name = $data['name'] ?? '';
$brand = $data['brand'] ?? '';
$fragrance = $data['fragrance'] ?? '';
$price = $data['price'] ?? 0;
$stock = $data['stock'] ?? 0;
$description = $data['description'] ?? '';
$image = $data['image'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit();
}

try {
    // First, check if product exists
    $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $checkStmt->execute([$id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit();
    }
    
    // Update the product
    $sql = "UPDATE products SET name = ?, brand = ?, fragrance = ?, price = ?, stock = ?, description = ?, image = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$name, $brand, $fragrance, $price, $stock, $description, $image, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update product']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>