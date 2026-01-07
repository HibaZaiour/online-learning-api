<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name'])) {
    echo json_encode(["error" => "Missing role name"]);
    exit;
}

$sql = "INSERT INTO role (name) VALUES (:name)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':name' => $data['name']]);

echo json_encode(["message" => "Role created successfully"]);
?>
