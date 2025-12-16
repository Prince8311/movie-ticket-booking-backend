<?php
require "../../utils/headers.php";
require "../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();

/**
 * CASE 1: Token expired AND refresh failed
 */
if (!$authResult['authenticated'] && !$authResult['refreshed']) {
    header("HTTP/1.0 401 Unauthorized");
    echo json_encode([
        'status' => 401,
        'message' => 'Authentication failed'
    ]);
    exit;
}

/**
 * CASE 2: Token valid OR refreshed successfully
 */
header("HTTP/1.0 200 OK");

header("X-Token-Refreshed: " . ($authResult['refreshed'] ? 'true' : 'false'));

if ($authResult['refreshed']) {
    header("X-New-Token: " . $authResult['token']);
}

echo json_encode([
    'status' => 200,
    'refreshed' => $authResult['refreshed'],
    'message' => $authResult['refreshed']
        ? 'Token refreshed'
        : 'Token still valid'
]);
