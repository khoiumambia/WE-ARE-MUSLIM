<?php
// Database configuration for XAMPP
$host = 'localhost';
$dbname = 'muslim';  // ← Changed from 'we-are-muslim'
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>