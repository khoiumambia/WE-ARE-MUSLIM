<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? 0;
$title = $data['title'] ?? '';
$category = $data['category'] ?? '';
$author = $data['author'] ?? '';
$readTime = $data['readTime'] ?? 5;
$excerpt = $data['excerpt'] ?? '';
$content = $data['content'] ?? '';
$image = $data['image'] ?? '';
$status = $data['status'] ?? 'draft';
$tags = $data['tags'] ?? '';
$scheduleDate = $data['scheduleDate'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Blog ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE blogs SET 
        title = ?, category = ?, author = ?, read_time = ?, excerpt = ?, 
        content = ?, image = ?, status = ?, tags = ?, schedule_date = ?,
        updated_at = NOW()
        WHERE id = ?");
    $stmt->execute([$title, $category, $author, $readTime, $excerpt, $content, $image, $status, $tags, $scheduleDate, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog post updated successfully'
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>