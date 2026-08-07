<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'users' => $users]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>