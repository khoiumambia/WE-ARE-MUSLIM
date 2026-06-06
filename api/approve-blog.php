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
$submission_id = $data['submission_id'] ?? 0;
$action = $data['action'] ?? ''; // 'approve' or 'reject'

if (!$submission_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    if ($action === 'approve') {
        // Get the submission
        $stmt = $pdo->prepare("SELECT * FROM blog_submissions WHERE id = ?");
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            echo json_encode(['success' => false, 'error' => 'Submission not found']);
            exit();
        }
        
        // Insert into blogs table
        $stmt = $pdo->prepare("INSERT INTO blogs (title, category, author, read_time, excerpt, content, image, tags, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW())");
        $stmt->execute([
            $submission['title'], $submission['category'], $submission['author'],
            $submission['read_time'], $submission['excerpt'], $submission['content'],
            $submission['image'], $submission['tags']
        ]);
        
        // Update submission status
        $stmt = $pdo->prepare("UPDATE blog_submissions SET status = 'approved', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$submission_id]);
        
        echo json_encode(['success' => true, 'message' => 'Blog approved and published!']);
    } 
    elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE blog_submissions SET status = 'rejected', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$submission_id]);
        
        echo json_encode(['success' => true, 'message' => 'Blog rejected']);
    } 
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>