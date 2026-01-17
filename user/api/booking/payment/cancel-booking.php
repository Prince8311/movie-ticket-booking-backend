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

        if ($bookingResult) {
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
            $showTime = '04:20 AM';
            $showDate = '18 Jan, 2026';
            $amount = $bookingData['ticket_price'];
            $refundAmount = 0;

            $showDateTimeStr = $showDate . ' ' . $showTime;
            $showDateTime = DateTime::createFromFormat('d M, Y h:i A', $showDateTimeStr);
            $currentDateTime = new DateTime('now');
            $interval = $currentDateTime->diff($showDateTime);
            $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);

            if ($showDateTime < $currentDateTime) {
                $data = [
                    'status' => 400,
                    'message' => "This booking can't be cancelled.",
                ];
                header("HTTP/1.0 400 Not available");
                echo json_encode($data);
                exit;
            }

            if ($totalHours >= 6) {
                $refundAmount = (float) $amount;
            } else if ($totalHours <= 6 && $totalHours >= 3) {
                $refundAmount = ((float) $amount) / 2;
            } else if ($totalHours <= 3) {
                $refundAmount = 0;
            }

            $data = [
                'status' => 200,
                'message' => 'Booking data',
                'bookingId' => $bookingId,
                'refundAmount' => $refundAmount,
                'timeLeft' => $totalHours,
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
