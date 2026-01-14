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

    $production = false;
    $response = $_POST;

    // Payment Credentials
    $apiKey = $production ? 'dd3ac85a-750a-42d8-bca2-08b7afabee0c' : 'aa2fbb7d-de50-4e3e-b628-c5ee22468e47';
    $statusURL = $production ? 'https://api.phonepe.com/apis/hermes/pg/v1/status/' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/';
    $keyIndex = 1;

    $string = '/pg/v1/status/' . $response['merchantId'] . '/' . $response['transactionId'] . $apiKey;
    $sha256 = hash('sha256', $string);
    $final_x_header = $sha256 . '###' . $keyIndex;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $statusURL . $response['merchantId'] . "/" . $response['transactionId']);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'accept: application/json',
            'X-VERIFY: ' . $final_x_header,
            'X-MERCHANT-ID:' . $response['merchantId']
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $final = json_decode($response, true);

    if ($final) {
        $success = $final['success'];
        $message = $final['message'];
        $data = $final['data'];
        $merchantTransactionId = $data['merchantTransactionId'];

        if ($success) {
            $transactionId = $data['transactionId'];
            $amount = $data['amount'] / 100;
            $paymentInstrument = $data['paymentInstrument'];
            $paymentType = $paymentInstrument['type'];
            $status = 'Confirmed';

            $bookingSql = "UPDATE `online_bookings` SET `status`='$status',`expires_at`=NULL WHERE `merchant_transaction_id`='$merchantTransactionId'";
            $bookingResult = mysqli_query($conn, $bookingSql);

            if ($bookingResult) {
                header("Location: http://localhost:3000/booking-success");
                exit;
            }
        } else {
            $deleteSql = "DELETE FROM `online_bookings` WHERE `merchant_transaction_id` = '$merchantTransactionId'";
            $deleteResult = mysqli_query($conn, $deleteSql);

            if ($deleteResult) {
                header("Location: http://localhost:3000/booking-fail");
                exit;
            }
        }
    } else {
        echo "Failed to decode the response.";
    }
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
