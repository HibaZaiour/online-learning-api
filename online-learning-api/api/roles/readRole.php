<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

$sql = "SELECT * FROM role";
$stmt = $pdo->query($sql);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($roles);
?>
