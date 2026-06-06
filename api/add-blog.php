<?php
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

if (!$title || !$category || !$excerpt || !$content) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

if (empty($image)) {
    $image = 'https://placehold.co/800x400/e8ddd3/8B5E3C?text=' . urlencode($title);
}

try {
    $stmt = $pdo->prepare("INSERT INTO blogs (title, category, author, read_time, excerpt, content, image, status, tags, schedule_date, views, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$title, $category, $author, $readTime, $excerpt, $content, $image, $status, $tags, $scheduleDate]);
    
    echo json_encode([
        'success' => true,
        'blog_id' => $pdo->lastInsertId(),
        'message' => 'Blog post created successfully'
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 