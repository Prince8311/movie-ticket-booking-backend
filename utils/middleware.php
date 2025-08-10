<?php 

require_once __DIR__ . '/auth-helper.php';

function authenticateRequest() {
    $authHeader = getAuthorizationHeader();
    $cookieToken = $_COOKIE['authToken'] ?? '';

    if (!isset($cookieToken) || empty($cookieToken)) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Authentication error'
        ];
    }

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Missing or malformed Authorization token'
        ];
    }

    $frontendToken = $matches[1];

    if (empty($cookieToken) || $cookieToken !== $frontendToken) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Authentication mismatch'
        ];
    }

    return [
        'authenticated' => true,
        'token' => $cookieToken
    ];
}

?>