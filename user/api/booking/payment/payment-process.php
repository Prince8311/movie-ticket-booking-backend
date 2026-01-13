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
    $env = getenv('APP_ENV');
    $isProd = getenv('APP_ENV') === 'prod';

    // Payment Credentials
    $merchantId = $isProd ? getenv('PHONEPE_PROD_MERCHANT_ID') : getenv('PHONEPE_UAT_MERCHANT_ID');
    $apiKey = $isProd ? getenv('PHONEPE_PROD_API_KEY') : getenv('PHONEPE_UAT_API_KEY');
    $paymentURL = $isProd ? getenv('PHONEPE_PROD_URL') : getenv('PHONEPE_UAT_URL');

    $data = [
        'status' => 200,
        'message' => 'Payment data',
        'env' => $env,
        'merchantId' => $merchantId,
        'apiKey' => $apiKey,
        'paymentURL' => $paymentURL
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
