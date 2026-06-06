<?php
$host = 'localhost';
$dbname = 'we-are-muslim';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all products
    $stmt = $pdo->query("SELECT id, name, image FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Image Path Check</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Current Image Path</th><th>Status</th><th>Preview</th></tr>";
    
    foreach ($products as $product) {
        $image = $product['image'];
        $status = "";
        $preview = "";
        
        if (empty($image)) {
            $status = "❌ No image";
        } elseif (strpos($image, 'data:image') === 0) {
            $status = "⚠️ Base64 image (too long, may not display)";
            // Extract first 50 chars for display
            $preview = substr($image, 0, 50) . "...";
        } elseif (strpos($image, 'uploads/') === 0) {
            $fullPath = $image;
            $fileExists = file_exists($image);
            if ($fileExists) {
                $status = "✅ File exists";
                $preview = "<img src='$image' width='50' height='50' style='border:1px solid #ccc;' onerror='this.style.display=\"none\"'>";
            } else {
                $status = "❌ File not found: " . $image;
            }
        } elseif (strpos($image, 'http') === 0) {
            $status = "✅ External URL";
            $preview = "<img src='$image' width='50' height='50' style='border:1px solid #ccc;' onerror='this.style.display=\"none\"'>";
        } else {
            $status = "⚠️ Unknown format";
            $preview = substr($image, 0, 50);
        }
        
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td style='word-break:break-all; max-width:300px;'>" . htmlspecialchars(substr($image, 0, 100)) . "</td>";
        echo "<td>$status</td>";
        echo "<td>$preview</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Files in uploads folder:</h2>";
    $uploadDir = 'uploads/';
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $uploadDir . $file;
                echo "<li>";
                echo "<img src='$filePath' width='50' height='50' style='border:1px solid #ccc; margin-right:10px;' onerror='this.style.display=\"none\"'>";
                echo " $file";
                echo "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>Uploads folder not found!</p>";
    }
    
} catch(PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>