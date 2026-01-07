<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

$sql = "SELECT * FROM course";
$stmt = $pdo->query($sql);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($courses);
