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

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'error' => 'Email and password required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password, phone, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit();
    }
    
    // Check password - supports both hashed and plain text (for migration)
    $passwordValid = false;
    
    if (empty($user['password'])) {
        $passwordValid = false;
    } elseif (strpos($user['password'], '$2y$') === 0) {
        // Hashed password
        $passwordValid = password_verify($password, $user['password']);
    } else {
        // Plain text password (legacy)
        $passwordValid = ($password === $user['password']);
        // Upgrade to hashed password
        if ($passwordValid) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$hashed, $user['id']]);
        }
    }
    
    if (!$passwordValid) {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit();
    }
    
    unset($user['password']);
    $_SESSION['user_id'] = $user['id'];
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'message' => 'Login successful'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>