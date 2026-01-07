<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(["error" => "Only PUT method allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'], $data['title'], $data['description'], $data['category_id'], $data['level'], $data['instructor_id'])) {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$sql = "UPDATE course 
        SET title = :title, description = :description, category_id = :category_id, level = :level, instructor_id = :instructor_id 
        WHERE course_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':title' => $data['title'],
    ':description' => $data['description'],
    ':category_id' => $data['category_id'],
    ':level' => $data['level'],
    ':instructor_id' => $data['instructor_id'],
    ':id' => $data['id']
]);

echo json_encode(["message" => "Course updated successfully"]);
