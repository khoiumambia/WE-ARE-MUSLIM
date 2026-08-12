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

// Debug: Log session status
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'logged_in' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['logged_in' => false, 'reason' => 'User not found in database']);
        }
    } catch(PDOException $e) {
        echo json_encode(['logged_in' => false, 'reason' => $e->getMessage()]);
    }
} else {
    echo json_encode(['logged_in' => false, 'reason' => 'No session user_id']);
}
?>