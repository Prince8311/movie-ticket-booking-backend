<?php

require_once __DIR__ . '/../../utils/auth-helper.php';
require_once __DIR__ . '/../../_db-connect.php';

function authenticateRequest()
{
    global $conn;
    $cookieToken = $_COOKIE['authToken'] ?? '';
    $authHeader  = getAuthorizationHeader();
    $frontendToken = null;

    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $frontendToken = $matches[1];
    }

    return [
        'authenticated' => true,
        'frontendToken' => $frontendToken,
        'refreshed' => true,
        'cookieToken' => $cookieToken
    ];
}
