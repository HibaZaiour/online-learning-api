<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change this to your MySQL username
define('DB_PASS', '');              // Change this to your MySQL password
define('DB_NAME', 'learning_platform');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Function to prevent SQL injection
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Function to execute prepared statements
function executeQuery($sql, $params = [], $types = '') {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if ($params && $types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}
?>