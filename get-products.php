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

try {
    // Updated query to include average rating and review count
    $stmt = $pdo->query("
        SELECT 
            p.*,
            COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
            COUNT(r.id) as review_count
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
        GROUP BY p.id
        ORDER BY p.id
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => $products
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>