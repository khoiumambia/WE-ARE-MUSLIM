<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Blog ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog post deleted successfully'
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>