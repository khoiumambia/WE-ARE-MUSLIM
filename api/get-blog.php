<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Blog ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        echo json_encode(['success' => false, 'error' => 'Blog not found']);
        exit();
    }
    
    // Increment views
    $updateStmt = $pdo->prepare("UPDATE blogs SET views = views + 1 WHERE id = ?");
    $updateStmt->execute([$id]);
    $blog['views'] = ($blog['views'] ?? 0) + 1;
    
    echo json_encode([
        'success' => true,
        'blog' => $blog
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>