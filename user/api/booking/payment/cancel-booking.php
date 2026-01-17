<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../utils/headers.php";
require "../../../../utils/middleware.php";

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

if ($requestMethod == 'POST') {
    require "../../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $bookingId = mysqli_real_escape_string($conn, $inputData['bookingId']);
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);

        $bookingSql = "SELECT * FROM `online_bookings` WHERE `booking_id`='$bookingId' AND `username`='$userName'";
        $bookingResult = mysqli_query($conn, $bookingSql);

        if (!$bookingResult) {
            $data = [
                'status' => 500,
                'message' => 'Database error: ' . mysqli_error($conn)
            ];
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode($data);
            exit;
        }

        if (mysqli_num_rows($bookingResult) === 0) {
            $data = [
                'status' => 404,
                'message' => 'No booking found with this id'
            ];
            header("HTTP/1.0 404 Not found");
            echo json_encode($data);
            exit;
        }

        $bookingData = mysqli_fetch_assoc($bookingResult);
        $showTime = $bookingData['start_time'];
        $showData = $bookingData['start_date'];
        $amount = $bookingData['ticket_price'];

        $showDateTimeStr = $showDate . ' ' . $showTime;
        $showDateTime = DateTime::createFromFormat('d M, Y h:i A', $showDateTimeStr);
        $currentDateTime = new DateTime('now');
        $interval = $currentDateTime->diff($showDateTime);
        $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);

        $data = [
            'status' => 200,
            'message' => 'Booking data',
            'bookingId' => $bookingId,
            'amount' => $amount,
            'timeLeft' => $totalHours,
        ];
        header("HTTP/1.0 200 OK");
        echo json_encode($data);
    } else {
        $data = [
            'status' => 400,
            'message' => 'Empty request data'
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
