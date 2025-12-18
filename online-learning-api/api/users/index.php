<?php
header("Content-Type: application/json");
require_once '../../db/connection.php';

try {
    $stmt = $pdo->query("SELECT * FROM User");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
