<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Content-Type: application/json");

require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'], $data['name'])) {
    echo json_encode(["error" => "Missing role ID or name"]);
    exit;
}

$sql = "UPDATE role SET name = :name WHERE role_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':name' => $data['name'],
    ':id' => $data['id']
]);

echo json_encode(["message" => "Role updated successfully"]);
?>
