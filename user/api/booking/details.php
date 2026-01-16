<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";
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

    if (isset($_GET['name']) && isset($_GET['bookingId'])) {
        $userName = mysqli_real_escape_string($conn, $_GET['name']);
        $bookingId = mysqli_real_escape_string($conn, $_GET['bookingId']);

        $sql = "SELECT ob.*, m.poster_image, m.total_time, rt.location FROM online_bookings ob LEFT JOIN movies m ON m.name = ob.movie_name LEFT JOIN registered_theaters rt ON rt.name = ob.theater_name WHERE ob.username = '$userName' AND ob.booking_id = '$bookingId'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            if (mysqli_num_rows($result) === 1) {
                $res = mysqli_fetch_assoc($result);
                $data = [
                    'status' => 200,
                    'message' => 'Booking details fetched.',
                    'details' => $res
                ];
                header("HTTP/1.0 200 OK");
                echo json_encode($data);
            } else {
                $data = [
                    'status' => 404,
                    'message' => 'Booking not found'
                ];
                header("HTTP/1.0 404 Not found");
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
            'status' => 400,
            'message' => 'Name and booking id required.'
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
