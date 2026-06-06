<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    
    // Get original filename without extension
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Clean the original name (remove special characters, replace spaces with underscores)
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
    $cleanName = substr($cleanName, 0, 50); // Limit length
    
    // Calculate file hash to detect duplicate content
    $fileHash = md5_file($file['tmp_name']);
    
    // Check if a file with the same hash already exists
    $existingFile = null;
    $files = scandir($uploadDir);
    foreach ($files as $existing) {
        if ($existing != '.' && $existing != '..') {
            $existingPath = $uploadDir . $existing;
            if (is_file($existingPath) && md5_file($existingPath) === $fileHash) {
                $existingFile = $existing;
                break;
            }
        }
    }
    
    // If duplicate found, return the existing file path instead of uploading again
    if ($existingFile) {
        $imageUrl = 'uploads/' . $existingFile;
        echo json_encode([
            'success' => true,
            'image_url' => $imageUrl,
            'original_name' => $file['name'],
            'message' => 'Image already exists. Using existing file.',
            'duplicate' => true
        ]);
        exit();
    }
    
    // Check if file with same name already exists (for naming)
    $targetPath = $uploadDir . $cleanName . '.' . $extension;
    $counter = 1;
    
    // If file with same name exists (but different content), append counter
    while (file_exists($targetPath)) {
        // Verify if it's the same content (should not happen due to hash check)
        if (md5_file($targetPath) === $fileHash) {
            // This shouldn't happen because we already checked, but just in case
            $imageUrl = 'uploads/' . basename($targetPath);
            echo json_encode([
                'success' => true,
                'image_url' => $imageUrl,
                'original_name' => $file['name'],
                'message' => 'Image already exists.',
                'duplicate' => true
            ]);
            exit();
        }
        $targetPath = $uploadDir . $cleanName . '_' . $counter . '.' . $extension;
        $counter++;
    }
    
    // Check file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large (max 2MB)']);
        exit();
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
        exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $imageUrl = 'uploads/' . basename($targetPath);
        echo json_encode([
            'success' => true,
            'image_url' => $imageUrl,
            'original_name' => $file['name'],
            'message' => 'Image uploaded successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
    }
} else {
    $error = isset($_FILES['image']) ? 'Upload error: ' . $_FILES['image']['error'] : 'No file uploaded';
    echo json_encode(['success' => false, 'error' => $error]);
}
?>