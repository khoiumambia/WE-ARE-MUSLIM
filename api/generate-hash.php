<?php
// hash-generator.php
echo "<h2>Password Hash Generator</h2>";
echo "Password: admin123<br>";
echo "Hash: " . password_hash('admin123', PASSWORD_DEFAULT) . "<br>";
echo "<hr>";
echo "Copy this hash and use it in your SQL INSERT statement.";
?>