<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../../auth/jwt_helper.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["message" => "Email and password are required."]);
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "", "ids");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$email = $conn->real_escape_string($data['email']);
$password = $data['password'];

$result = $conn->query("SELECT * FROM user WHERE email = '$email' LIMIT 1");

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid credentials."]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid credentials."]);
    exit;
}

// Create token
$token = generateJWT([
    'id' => $user['user_id'],
    'username' => $user['username'],
    'email' => $user['email']
]);

echo json_encode(["token" => $token]);
?>
