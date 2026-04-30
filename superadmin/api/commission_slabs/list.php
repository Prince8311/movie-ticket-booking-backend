<?php

require __DIR__ . "/../../../utils/headers.php";
require __DIR__ . "/../../../utils/middleware.php";

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

if ($requestMethod == 'GET') {
    require __DIR__ . "/../../../_db-connect.php";
    global $conn;

    if (isset($_GET['type'])) {
        $type = mysqli_real_escape_string($conn, $_GET['type']);

        if ($type == "admin") {
            $sql = "SELECT * FROM `admin_commission_slabs`";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $slabs = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $data = [
                    'status' => 200,
                    'message' => 'Admin slabs fetched.',
                    'slabs' => $slabs
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
        } else if ($type == "theater") {
            $sql = "SELECT * FROM `theater_commission_slabs`";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $slabs = mysqli_fetch_all($result, MYSQLI_ASSOC);
                $data = [
                    'status' => 200,
                    'message' => 'Theater slabs fetched.',
                    'slabs' => $slabs
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
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'No parameter found',
        ];
        header("HTTP/1.0 400 No parameter");
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
