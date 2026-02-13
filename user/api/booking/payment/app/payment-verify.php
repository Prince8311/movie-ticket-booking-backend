<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../../utils/headers.php";

if ($requestMethod == 'POST') {
    require "../../../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $merchantTransactionId = mysqli_real_escape_string($conn, $inputData['merchantTransactionId']);
    } else {
        $data = [
            'status' => 400,
            'message' => 'merchantTransactionId required'
        ];
        header("HTTP/1.0 400 Bad Request");
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
