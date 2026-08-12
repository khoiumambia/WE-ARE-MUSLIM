<?php
error_reporting(0);
ini_set('display_errors', 0);

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

$name = $data['name'] ?? '';
$brand = $data['brand'] ?? '';
$fragrance = $data['fragrance'] ?? '';
$price = $data['price'] ?? 0;
$stock = $data['stock'] ?? 0;
$description = $data['description'] ?? '';
$image = $data['image'] ?? '';

if (empty($image)) {
    $image = 'https://via.placeholder.com/300x250?text=' . urlencode($name);
}

if (!$name || !$brand || !$fragrance || !$price) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO products (name, brand, fragrance, price, stock, description, image, ratings, reviews) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)");
    $stmt->execute([$name, $brand, $fragrance, $price, $stock, $description, $image]);
    
    echo json_encode([
        'success' => true,
        'product_id' => $pdo->lastInsertId(),
        'message' => 'Product added successfully'
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>