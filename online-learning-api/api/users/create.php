<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Only POST requests are allowed."]);
    exit;
}

// Get raw POST data
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Debug logging (remove in production)
error_log("Create user request: " . $rawInput);

// Check if JSON decode was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON data: " . json_last_error_msg()]);
    exit;
}

// Simple validation
if (!isset($data['username'], $data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required fields.", "received" => array_keys($data ?: [])]);
    exit;
}

// Connect to DB
$connection = new mysqli("localhost", "root", "", "ids"); // update DB name if needed

if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed: " . $connection->connect_error]);
    error_log("Database connection error: " . $connection->connect_error);
    exit;
}

// Set charset to utf8
$connection->set_charset("utf8");

// Check if email already exists
$email = $connection->real_escape_string($data['email']);
$checkEmail = $connection->query("SELECT * FROM user WHERE email = '$email' LIMIT 1");

if ($checkEmail && $checkEmail->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["message" => "Email already exists. Please use a different email."]);
    $connection->close();
    exit;
}

// Insert into DB
$username = $connection->real_escape_string($data['username']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO user (username, email, password) VALUES ('$username', '$email', '$password')";

error_log("Executing SQL: " . $sql);

if ($connection->query($sql) === TRUE) {
    $userId = $connection->insert_id;
    http_response_code(201);
    echo json_encode([
        "message" => "User created successfully.",
        "user_id" => $userId
    ]);
    error_log("User created successfully with ID: " . $userId);
} else {
    $error = $connection->error;
    http_response_code(500);
    echo json_encode([
        "message" => "Database error: " . $error,
        "error_code" => $connection->errno
    ]);
    error_log("Database error: " . $error . " (Error code: " . $connection->errno . ")");
}

$connection->close();
