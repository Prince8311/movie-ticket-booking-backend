<?php

require "../../utils/headers.php";
require "../utils/middleware.php";

$authResult = authenticateRequest();
if (!$authResult['authenticated']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message'],
        'frontendToken' => $authResult['frontendToken'],
        'cookieToken' => $authResult['cookieToken'],
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

    $sql = "SELECT `id`, `name`, `image`, `phone`, `email`, `status`, `ticket_booked` FROM `users` WHERE `id`='$userID'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $data = [
                'status' => 200,
                'message' => 'Authenticated',
                'user' => $user,
                'tokenRefreshed' => $refreshed
            ];
            header("HTTP/1.0 200 Authenticated");
            echo json_encode($data);
        } else {
            $data = [
                'status' => 400,
                'message' => 'No user found'
            ];
            header("HTTP/1.0 400 Not found");
            echo json_encode($data);
        }
    } else {
        $data = [
            'status' => 500,
            'message' => 'Database error: ' . mysqli_error($conn)
        ];
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode($data);
    }
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
