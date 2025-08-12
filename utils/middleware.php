<?php 

require_once __DIR__ . '/auth-helper.php';

function authenticateRequest() {
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