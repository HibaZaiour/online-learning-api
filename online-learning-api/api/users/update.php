<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';

try {
    // Ensure it's a PUT request
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        echo json_encode(["error" => "Only PUT method is allowed"]);
        exit;
    }

    // Read raw input for PUT
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!isset($data['id'], $data['username'], $data['email'], $data['password'])) {
        echo json_encode(["error" => "Missing required fields"]);
        exit;
    }

    // Hash the password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $sql = "UPDATE user SET username = :username, email = :email, password = :password WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $data['username'],
        ':email' => $data['email'],
        ':password' => $hashedPassword,
        ':id' => $data['id']
    ]);

    echo json_encode(["message" => "User updated successfully"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
