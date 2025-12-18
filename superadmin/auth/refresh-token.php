<?php

require "../../utils/headers.php";
require "../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();
if (!$authResult['current_token']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message']
    ];
    header("HTTP/1.0 " . $authResult['status']);
    echo json_encode($data);
    exit;
}


if ($requestMethod == 'GET') {
    require "../../_db-connect.php";
    global $conn;

    $currentToken = $authResult['current_token'];

    $escapedToken = mysqli_real_escape_string($conn, $currentToken);

    $data = [
        'status' => 200,
        'message' => 'Token.',
        'newToken' => $escapedToken
    ];

    header("HTTP/1.0 200 OK");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
