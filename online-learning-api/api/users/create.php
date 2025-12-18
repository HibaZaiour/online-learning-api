<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Only POST requests are allowed."]);
    exit;
}

// Get raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Simple validation
if (!isset($data['username'], $data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required fields."]);
    exit;
}

// Connect to DB
$connection = new mysqli("localhost", "root", "", "ids"); // update DB name if needed

if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

// Insert into DB
$username = $connection->real_escape_string($data['username']);
$email = $connection->real_escape_string($data['email']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO user (username, email, password) VALUES ('$username', '$email', '$password')";

if ($connection->query($sql) === TRUE) {
    echo json_encode(["message" => "User created successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $connection->error]);
}

$connection->close();
