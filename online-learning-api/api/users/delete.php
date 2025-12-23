<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['user_id'])) {
        echo json_encode(["error" => "Missing user ID"]);
        exit;
    }

    $sql = "DELETE FROM user WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $data['user_id']]);

    echo json_encode(["message" => "User deleted successfully"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
