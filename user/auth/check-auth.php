<?php

require "../../utils/headers.php";
require "../utils/middleware.php";

$authResult = authenticateRequest();
if (!$authResult['authenticated']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message']
    ];
    header("HTTP/1.0 " . $authResult['status']);
    echo json_encode($data);
    exit;
}

$userID = $authResult['userId'];
$refreshed = $authResult['refreshed'];

if ($requestMethod == 'GET') {
    require "../../_db-connect.php";
    global $conn;

    $data = [
        'status' => 200,
        'message' => 'Authenticated',
        'tokenRefreshed' => $refreshed
    ];
    header("HTTP/1.0 200 Authenticated");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
