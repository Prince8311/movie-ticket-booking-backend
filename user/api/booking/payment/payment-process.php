<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../utils/headers.php";
require "../../../../utils/middleware.php";

// $authResult = authenticateRequest();
// if (!$authResult['authenticated']) {
//     $data = [
//         'status' => $authResult['status'],
//         'message' => $authResult['message']
//     ];
//     header("HTTP/1.0 " . $authResult['status']);
//     echo json_encode($data);
//     exit;
// }

if ($requestMethod == 'POST') {
    require "../../../../_db-connect.php";
    global $conn;

    $currentDateTime = new DateTime();
    $currentDateTime->add(new DateInterval('PT30M'));
    $expiryDateTime = $currentDateTime->format('Y-m-d H:i:s');
    $production = false;

    // Payment Credentials
    $apiKey = $production ? 'dd3ac85a-750a-42d8-bca2-08b7afabee0c' : 'aa2fbb7d-de50-4e3e-b628-c5ee22468e47';
    $merchantId = $production ? 'M22EJS7CELBPU' : 'TICKETBAYUAT';
    $paymentURL = $production ? 'https://api.phonepe.com/apis/hermes/pg/v1/pay' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay';

    $data = [
        'status' => 200,
        'message' => 'Payment',
        'production' => $production,
        'merchantId' => $merchantId,
        'apiKey' => $apiKey,
        'paymentURL' => $paymentURL,
        'expiryDateTime' => $expiryDateTime,
    ];
    header("HTTP/1.0 200 Payment");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
