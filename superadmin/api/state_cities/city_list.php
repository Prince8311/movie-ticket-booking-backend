<?php

session_start();
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

require "../../../utils/middleware.php";

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

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['name'])) {
        $stateName  = mysqli_real_escape_string($conn, $_GET['name'] ?? '');

        $sql = "SELECT `city` FROM `state_cities` WHERE `state`='$stateName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $cities = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $data = [
                'status' => 200,
                'message' => 'Cities fetched successfully.',
                'cities' => $cities,
            ];
            header("HTTP/1.0 200 OK");
            echo json_encode($data);
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
            'status' => 400,
            'message' => 'State name is missing'
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
