<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';
require_once '../../auth/auth_middleware.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(["error" => "User ID is required"]);
    exit;
}

$id = $_GET['user_id'];



try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(["message" => "User not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
