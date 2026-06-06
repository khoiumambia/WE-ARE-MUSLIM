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
$action = $data['action'] ?? 'update';

if ($action === 'reset_password') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (!$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'Email and password required']);
        exit();
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        echo json_encode(['success' => true, 'message' => 'Password updated']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    $id = $data['id'] ?? 0;
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';
    $city = $data['city'] ?? '';
    $postal_code = $data['postal_code'] ?? '';
    $role = $data['role'] ?? 'user';
    $password = $data['password'] ?? '';
    
    if (!$id || !$name || !$email) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    try {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $city, $postal_code, $role, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $city, $postal_code, $role, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'User updated']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>