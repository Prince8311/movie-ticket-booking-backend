<?php

date_default_timezone_set('Asia/Kolkata');
require __DIR__ . "/../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require __DIR__ . "/../../../_db-connect.php";
    global $conn;

    $data = [
        'status' => 200,
        'message' => 'Cities fetched.'
    ];
    header("HTTP/1.0 200 City list");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
