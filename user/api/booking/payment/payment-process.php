<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../utils/headers.php";
require "../../../../utils/middleware.php";

if ($requestMethod == 'POST') {
    require "../../../../_db-connect.php";
    global $conn;

    $appEnv = getenv('APP_ENV');

    // Payment Credentials
    $apiKey = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_API_KEY') : getenv('PHONEPE_PROD_API_KEY');
    $merchantId = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_MERCHANT_ID') : getenv('PHONEPE_PROD_MERCHANT_ID');
    $paymentURL = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_URL') : getenv('PHONEPE_PROD_URL');

    $data = [
        'status' => 200,
        'success' => true,
        'apiKey' => $apiKey,
        'merchantId' => $merchantId,
        'paymentURL' => $paymentURL,
    ];
    header("HTTP/1.0 200 OK");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
