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
$section = $data['section'] ?? '';
$title = $data['title'] ?? '';
$subtitle = $data['subtitle'] ?? '';
$content = $data['content'] ?? '';
$image = $data['image'] ?? '';
$buttonText = $data['button_text'] ?? '';
$buttonLink = $data['button_link'] ?? '';

if (!$section) {
    echo json_encode(['success' => false, 'error' => 'Section name required']);
    exit();
}

try {
    // Check if section exists
    $checkStmt = $pdo->prepare("SELECT id FROM homepage_content WHERE section = ?");
    $checkStmt->execute([$section]);
    
    if ($checkStmt->fetch()) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE homepage_content SET 
            title = COALESCE(NULLIF(?, ''), title),
            subtitle = COALESCE(NULLIF(?, ''), subtitle),
            content = COALESCE(NULLIF(?, ''), content),
            image = COALESCE(NULLIF(?, ''), image),
            button_text = COALESCE(NULLIF(?, ''), button_text),
            button_link = COALESCE(NULLIF(?, ''), button_link)
            WHERE section = ?");
        $stmt->execute([$title, $subtitle, $content, $image, $buttonText, $buttonLink, $section]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO homepage_content (section, title, subtitle, content, image, button_text, button_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$section, $title, $subtitle, $content, $image, $buttonText, $buttonLink]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Content updated successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>