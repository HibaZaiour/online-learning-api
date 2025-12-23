<?php
require_once __DIR__ . '/jwt_helper.php';


$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["message" => "Missing Authorization header."]);
    exit;
}

$authHeader = $headers['Authorization'];
list($type, $token) = explode(" ", $authHeader, 2);

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
