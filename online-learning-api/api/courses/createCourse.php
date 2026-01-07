<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['title'], $data['description'], $data['category_id'], $data['level'], $data['instructor_id'])) {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$sql = "INSERT INTO course (title, description, category_id, level, instructor_id) 
        VALUES (:title, :description, :category_id, :level, :instructor_id)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':title' => $data['title'],
    ':description' => $data['description'],
    ':category_id' => $data['category_id'],
    ':level' => $data['level'],
    ':instructor_id' => $data['instructor_id']
]);

echo json_encode(["message" => "Course created successfully"]);
