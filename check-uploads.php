<?php
$uploadDir = '../uploads/';
echo "<h2>Uploads Directory Check</h2>";
echo "Path: " . realpath($uploadDir) . "<br>";
echo "Exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . "<br>";
echo "Writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";

if (!file_exists($uploadDir)) {
    echo "<p style='color:red'>Creating uploads directory...</p>";
    mkdir($uploadDir, 0777, true);
    echo "Directory created!";
}

echo "<h2>Files in uploads:</h2>";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<div>";
            echo "<img src='$uploadDir$file' width='100' height='100' style='margin:5px; border:1px solid #ccc;' onerror='this.style.display=\"none\"'>";
            echo "<br><small>$file</small>";
            echo "</div>";
        }
    }
}
?>