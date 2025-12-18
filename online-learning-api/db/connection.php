<?php
$host = 'localhost';
$dbname = 'ids'; // your database name
$username = 'root'; // default XAMPP username
$password = ''; // default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully"; // (optional) test connection
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>
