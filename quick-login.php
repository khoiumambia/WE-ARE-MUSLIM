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
$action = $data['action'] ?? 'login';

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit();
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name, email, password, phone, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Action: Check if user exists
    if ($action === 'check') {
        echo json_encode([
            'success' => true,
            'exists' => $user ? true : false
        ]);
        exit();
    }
    
    // Action: Create user with password (for email signup)
    if ($action === 'create_with_password') {
        if (!$password || strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
            exit();
        }
        
        if ($user) {
            echo json_encode(['success' => false, 'error' => 'User already exists']);
            exit();
        }
        
        $name = explode('@', $email)[0];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => '',
            'role' => 'user',
            'has_password' => true
        ];
        
        unset($user['password']);
        $_SESSION['user_id'] = $user['id'];
        
        echo json_encode([
            'success' => true,
            'user' => $user,
            'message' => 'Account created successfully'
        ]);
        exit();
    }
    
    // Action: Change password for existing user
    if ($action === 'change_password') {
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit();
        }
        
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        
        if (!$newPassword || strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
            exit();
        }
        
        // Verify current password if user has one
        if (!empty($user['password'])) {
            $passwordValid = false;
            if (strpos($user['password'], '$2y$') === 0) {
                $passwordValid = password_verify($currentPassword, $user['password']);
            } else {
                $passwordValid = ($currentPassword === $user['password']);
            }
            
            if (!$passwordValid) {
                echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                exit();
            }
        }
        
        // Hash and update new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully!'
        ]);
        exit();
    }
    
    // Action: Set password for existing user (from dashboard)
    if ($action === 'set_password') {
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit();
        }
        
        if (!$password || strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
            exit();
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password set successfully!'
        ]);
        exit();
    }
    
    // Default: Login without password (for existing email-only users)
    if (!$user) {
        // Create new user without password
        $name = explode('@', $email)[0];
        $stmt = $pdo->prepare("INSERT INTO users (name, email, role) VALUES (?, ?, 'user')");
        $stmt->execute([$name, $email]);
        
        $userId = $pdo->lastInsertId();
        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => '',
            'role' => 'user',
            'has_password' => false
        ];
    } else {
        $user['has_password'] = !empty($user['password']);
    }
    
    unset($user['password']);
    $_SESSION['user_id'] = $user['id'];
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'message' => $user['has_password'] ? 'Login successful' : 'Account created. You can set a password in your profile.'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>