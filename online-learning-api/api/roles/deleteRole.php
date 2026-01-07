<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Content-Type: application/json");

require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["error" => "Missing role ID"]);
    exit;
}

$sql = "DELETE FROM role WHERE role_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $data['id']]);

echo json_encode(["message" => "Role deleted successfully"]);
?>
