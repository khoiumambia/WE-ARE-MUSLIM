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
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        $image = $data['image'] ?? '';
        $buttonText = $data['button_text'] ?? '';
        $buttonLink = $data['button_link'] ?? '';
        $orderIndex = $data['order_index'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO carousel_slides (title, subtitle, image, button_text, button_link, order_index) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $subtitle, $image, $buttonText, $buttonLink, $orderIndex]);
        
        echo json_encode(['success' => true, 'message' => 'Slide added successfully', 'id' => $pdo->lastInsertId()]);
    } 
    elseif ($action === 'update') {
        $id = $data['id'] ?? 0;
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        $image = $data['image'] ?? '';
        $buttonText = $data['button_text'] ?? '';
        $buttonLink = $data['button_link'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE carousel_slides SET title = ?, subtitle = ?, image = ?, button_text = ?, button_link = ? WHERE id = ?");
        $stmt->execute([$title, $subtitle, $image, $buttonText, $buttonLink, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Slide updated successfully']);
    }
    elseif ($action === 'delete') {
        $id = $data['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM carousel_slides WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Slide deleted successfully']);
    }
    elseif ($action === 'reorder') {
        $items = $data['items'] ?? [];
        foreach ($items as $item) {
            $stmt = $pdo->prepare("UPDATE carousel_slides SET order_index = ? WHERE id = ?");
            $stmt->execute([$item['order'], $item['id']]);
        }
        echo json_encode(['success' => true, 'message' => 'Order updated']);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>