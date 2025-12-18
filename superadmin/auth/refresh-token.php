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
    $userSql = "SELECT * FROM `admin_users` WHERE `auth_token`='$escapedToken'";
    $userResult = mysqli_query($conn, $userSql);

    $tokenRow = mysqli_fetch_assoc($userResult);

    $data = [
        'status' => 200,
        'message' => 'Token refreshed successfully.',
        'data' => $tokenRow
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
