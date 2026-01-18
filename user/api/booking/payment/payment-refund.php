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
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);
        $bookingId = mysqli_real_escape_string($conn, $inputData['bookingId']);
        $refundAmount = (float) $inputData['refundAmount'];

        $bookingSql = "SELECT * FROM `online_bookings` WHERE `booking_id`='$bookingId' AND `username`='$userName'";
        $bookingResult = mysqli_query($conn, $bookingSql);

        if ($bookingResult) {
            $bookingData = mysqli_fetch_assoc($bookingResult);
            $ticketPrice = (float) $bookingData['ticket_price'];
            $baseConvenience = (float) $bookingData['base_convenience'];
            $gst = (float) $bookingData['gst'];
            $bookingMerchantTransactionId = $bookingData['merchant_transaction_id'];
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
