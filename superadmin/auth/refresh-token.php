<?php

require "../../utils/headers.php";
require "../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();
if (!$authResult['authenticated']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message']
    ];
    header("HTTP/1.0 " . $authResult['status']);
    echo json_encode($data);
    exit;
}

$refreshed = $authResult['refreshed'];
$newToken = $authResult['token'];

header("X-Token-Refreshed: " . ($refreshed ? "true" : "false"));

if ($refreshed && $newToken) {
    header("X-New-Token: " . $newToken);
}

header("Access-Control-Expose-Headers: X-Token-Refreshed, X-New-Token");

$response = [
    'status' => 200,
    'message' => $refreshed
        ? 'Token refreshed successfully.'
        : 'Token still valid.'
];

header("HTTP/1.0 200 OK");
echo json_encode($response);
