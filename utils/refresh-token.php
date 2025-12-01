<?php 

require_once __DIR__ . '/auth-helper.php';
require_once __DIR__ . '/../_db-connect.php';

function authenticateRequest() {
    global $conn;
    $cookieToken = $_COOKIE['authToken'] ?? '';

    if (empty($cookieToken)) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Authentication error'
        ];
    }

    $authHeader = getAuthorizationHeader();
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $frontendToken = $matches[1];
        if ($cookieToken !== $frontendToken) {
            return [
                'authenticated' => false,
                'status' => 401,
                'message' => 'Authentication mismatch'
            ];
        }
    }

    return [
        'authenticated' => true,
        'token' => $cookieToken
    ];
}


?>