<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(["error" => "Only DELETE method allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["error" => "Missing course ID"]);
    exit;
}

$sql = "DELETE FROM course WHERE course_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $data['id']]);

echo json_encode(["message" => "Course deleted"]);
