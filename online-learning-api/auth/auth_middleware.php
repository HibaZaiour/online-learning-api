<?php
require_once __DIR__ . '/jwt_helper.php';

// Get Authorization header (handle different server configurations)
$authHeader = null;
if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    }
}

// Fallback to $_SERVER if apache_request_headers doesn't work
if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}

// Also check for Authorization in $_SERVER (some configurations)
if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["message" => "Missing Authorization header."]);
    exit;
}

$parts = explode(" ", $authHeader, 2);
if (count($parts) !== 2) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid Authorization format."]);
    exit;
}

list($type, $token) = $parts;

if (strtolower($type) !== 'bearer' || !$token) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid Authorization format."]);
    exit;
}

try {
    $payload = verifyJWT($token);
    // You can optionally store this for later use in the file
    $GLOBALS['user'] = $payload;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Token verification failed."]);
    exit;
}
?>
