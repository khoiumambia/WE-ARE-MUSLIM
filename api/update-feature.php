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
$action = $data['action'] ?? '';

try {
    if ($action === 'add') {
        $icon = $data['icon'] ?? '';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $link = $data['link'] ?? '';
        $orderIndex = $data['order_index'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO features (icon, title, description, link, order_index) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$icon, $title, $description, $link, $orderIndex]);
        
        echo json_encode(['success' => true, 'message' => 'Feature added successfully']);
    } 
    elseif ($action === 'update') {
        $id = $data['id'] ?? 0;
        $icon = $data['icon'] ?? '';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $link = $data['link'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE features SET icon = ?, title = ?, description = ?, link = ? WHERE id = ?");
        $stmt->execute([$icon, $title, $description, $link, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Feature updated successfully']);
    }
    elseif ($action === 'delete') {
        $id = $data['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM features WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Feature deleted successfully']);
    }
    elseif ($action === 'toggle') {
        $id = $data['id'] ?? 0;
        $isActive = $data['is_active'] ?? 1;
        $stmt = $pdo->prepare("UPDATE features SET is_active = ? WHERE id = ?");
        $stmt->execute([$isActive, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Feature status updated']);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>