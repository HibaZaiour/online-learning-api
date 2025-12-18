<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id'])) {
        echo json_encode(["error" => "Missing user ID"]);
        exit;
    }

    $sql = "DELETE FROM user WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $data['id']]);

    echo json_encode(["message" => "User deleted successfully"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
