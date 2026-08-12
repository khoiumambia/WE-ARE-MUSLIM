<?php
session_start();
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
$tags = $data['tags'] ?? '';
$userEmail = $data['user_email'] ?? '';
$userName = $data['user_name'] ?? '';

if (!$title || !$category || !$excerpt || !$content) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

if (empty($image)) {
    $image = 'https://placehold.co/800x400/e8ddd3/8B5E3C?text=' . urlencode($title);
}

try {
    $stmt = $pdo->prepare("INSERT INTO blog_submissions (title, category, author, read_time, excerpt, content, image, tags, user_email, user_name, status, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$title, $category, $author, $readTime, $excerpt, $content, $image, $tags, $userEmail, $userName]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog submitted successfully! Admin will review and publish it soon.'
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>