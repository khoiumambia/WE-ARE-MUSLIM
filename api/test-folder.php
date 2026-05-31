<?php
$uploadDir = 'uploads/';
echo "Current directory: " . __DIR__ . "<br>";
echo "Uploads path: " . $uploadDir . "<br>";
echo "Full path: " . realpath($uploadDir) . "<br>";
echo "Folder exists: " . (file_exists($uploadDir) ? 'YES' : 'NO') . "<br>";

if (!file_exists($uploadDir)) {
    echo "Creating uploads folder...<br>";
    mkdir($uploadDir, 0777, true);
    echo "Folder created!<br>";
}

// Try to write a test file
$testFile = $uploadDir . 'test.txt';
if (file_put_contents($testFile, 'test')) {
    echo "Can write to folder: YES<br>";
    unlink($testFile);
} else {
    echo "Can write to folder: NO<br>";
}
?>