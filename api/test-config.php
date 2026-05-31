<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Configuration</h2>";

// Check if config.php exists
$configPath = '../config.php';
echo "<p>Looking for config at: " . realpath(dirname(__FILE__)) . "/../config.php</p>";

if (file_exists($configPath)) {
    echo "<p style='color:green'>✓ config.php found</p>";
    require_once $configPath;
    
    // Test database connection
    try {
        $stmt = $pdo->query("SELECT 1");
        echo "<p style='color:green'>✓ Database connection successful</p>";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<p style='color:green'>✓ Users table has " . $result['count'] . " records</p>";
        
    } catch(PDOException $e) {
        echo "<p style='color:red'>✗ Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ config.php NOT found at: " . $configPath . "</p>";
    echo "<p>Current directory: " . __DIR__ . "</p>";
}
?>