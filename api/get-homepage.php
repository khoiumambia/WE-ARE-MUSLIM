<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    // Get homepage sections
    $stmt = $pdo->query("SELECT * FROM homepage_content ORDER BY order_index");
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get carousel slides
    $stmt = $pdo->query("SELECT * FROM carousel_slides WHERE is_active = 1 ORDER BY order_index");
    $carousel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get features
    $stmt = $pdo->query("SELECT * FROM features WHERE is_active = 1 ORDER BY order_index");
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent blog posts
    $stmt = $pdo->query("SELECT id, title, excerpt, image, created_at, read_time, views FROM blogs WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sections' => $sections,
        'carousel' => $carousel,
        'features' => $features,
        'blogs' => $blogs
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>