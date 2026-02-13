<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../../utils/headers.php";
require "../../../../../utils/middleware.php";

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
    require "../../../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $bookingId = mysqli_real_escape_string($conn, $inputData['bookingId']);
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);
        $userEmail = mysqli_real_escape_string($conn, $inputData['userEmail']);
        $userPhone = mysqli_real_escape_string($conn, $inputData['userPhone']);
        $ticketPrice = (float) $inputData['ticketPrice'];
        $baseConvenience = (float) $inputData['baseConvenience'];
        $gst = (float) $inputData['gst'];
        $theaterCommission = mysqli_real_escape_string($conn, $inputData['theaterCommission']);

        $amount = $ticketPrice + $baseConvenience + $gst;
        $appEnv = getenv('APP_ENV');
        $merchantUserId = 'MUIDSHETTY';
        $environment = ($appEnv === 'uat') ? 'SANDBOX' : 'PRODUCTION';
        $apiKey = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_API_KEY') : getenv('PHONEPE_PROD_API_KEY');
        $merchantId = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_MERCHANT_ID') : getenv('PHONEPE_PROD_MERCHANT_ID');

        $keyIndex = 1;
        $merchantTransactionId = "MT" . time() . rand(1000, 9999);
        $callbackURL = 'https://api.ticketbay.in/user/api/booking/payment/app/payment-response.php';
        $apiEndPoint = "/pg/v1/pay";

        $paymentData = array(
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => $merchantUserId,
            'amount' => (int) round($amount * 100),
            'redirectUrl' => $callbackURL,
            'redirectMode' => "POST",
            'callbackUrl' => $callbackURL,
            'merchantOrderId' => "MOID" . uniqid(),
            'mobileNumber' => $userPhone,
            'message' => "Payment of ₹" . $amount,
            'email' => $userEmail,
            'shortName' => "Shetty Ticket Counter",
            'paymentInstrument' => array(
                'type' => 'PAY_PAGE',
            ),
        );

        $jsonPayload = json_encode($paymentData);
        $base64Payload = base64_encode($jsonPayload);
        $checksumString = $base64Payload . $apiEndPoint . $apiKey;
        $sha256 = hash('sha256', $checksumString);
        $checksum = $sha256 . "###" . $keyIndex;

        $data = [
            'status' => 200,
            'body' => $base64Payload,
            'checksum' => $checksum,
            'merchantTransactionId' => $merchantTransactionId,
            'callbackUrl' => $callbackUrl,
            'apiEndPoint' => $apiEndPoint,
            'environment' => $environment
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
