<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$status = $_GET['status'] ?? 'pending';
$user_email = $_GET['user_email'] ?? '';

try {
    if ($user_email) {
        $stmt = $pdo->prepare("SELECT * FROM blog_submissions WHERE user_email = ? ORDER BY created_at DESC");
        $stmt->execute([$user_email]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM blog_submissions WHERE status = ? ORDER BY created_at DESC");
        $stmt->execute([$status]);
    }
    
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'submissions' => $submissions
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>