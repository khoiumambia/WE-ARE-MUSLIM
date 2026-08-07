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
$email = $data['email'] ?? '';
$newPassword = $data['new_password'] ?? '';
$step = $data['step'] ?? 'check';

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($step === 'check') {
        // Just check if email exists
        if ($user) {
            echo json_encode([
                'success' => true, 
                'exists' => true,
                'email' => $email,
                'message' => 'Email found. You can now reset your password.'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'exists' => false,
                'message' => 'Email not found. Please check and try again.'
            ]);
        }
    } 
    else if ($step === 'reset') {
        // Reset password
        if (!$newPassword) {
            echo json_encode(['success' => false, 'error' => 'New password required']);
            exit();
        }
        
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
            exit();
        }
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit();
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully! You can now login with your new password.'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>