<?php
define('SECRET_KEY', 'SuperSecret123'); 
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['exp'] = time() + 3600; 
    $payload = json_encode($payload);

    $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

    $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", SECRET_KEY, true);
    $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
}
function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new Exception('Invalid token format');
    }

    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

    $header = json_decode(base64_decode(strtr($base64UrlHeader, '-_', '+/')), true);
    $payload = json_decode(base64_decode(strtr($base64UrlPayload, '-_', '+/')), true);
    $signatureProvided = base64_decode(strtr($base64UrlSignature, '-_', '+/'));

    $expectedSignature = hash_hmac(
        'sha256',
        "$base64UrlHeader.$base64UrlPayload",
        SECRET_KEY,
        true
    );

    if (!hash_equals($expectedSignature, $signatureProvided)) {
        throw new Exception('Signature verification failed');
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        throw new Exception('Token expired');
    }

    return $payload;
}

?>
