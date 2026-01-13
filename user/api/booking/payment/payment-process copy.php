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
        $userEmail = mysqli_real_escape_string($conn, $inputData['userEmail']);
        $userPhone = mysqli_real_escape_string($conn, $inputData['userPhone']);
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $movieName = mysqli_real_escape_string($conn, $inputData['movieName']);
        $language = mysqli_real_escape_string($conn, $inputData['language']);
        $format = mysqli_real_escape_string($conn, $inputData['format']);
        $day = mysqli_real_escape_string($conn, $inputData['day']);
        $startTime = mysqli_real_escape_string($conn, $inputData['startTime']);
        $startDate = mysqli_real_escape_string($conn, $inputData['startDate']);
        $validTime = mysqli_real_escape_string($conn, $inputData['validTime']);
        $validDate = mysqli_real_escape_string($conn, $inputData['validDate']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $section = mysqli_real_escape_string($conn, $inputData['section']);
        $seats = mysqli_real_escape_string($conn, $inputData['seats']);
        $ticketPrice = mysqli_real_escape_string($conn, $inputData['ticketPrice']);
        $baseConvenience = mysqli_real_escape_string($conn, $inputData['baseConvenience']);
        $gst = mysqli_real_escape_string($conn, $inputData['gst']);
        $theaterCommission = mysqli_real_escape_string($conn, $inputData['theaterCommission']);
        $status = mysqli_real_escape_string($conn, $inputData['status']);

        $currentDateTime = new DateTime();
        $currentDateTime->add(new DateInterval('PT30M'));
        $expiryDateTime = $currentDateTime->format('Y-m-d H:i:s');

        $isProd = getenv('APP_ENV') === 'prod';

        // Payment Credentials
        $merchantId = $isProd ? getenv('PHONEPE_PROD_MERCHANT_ID') : getenv('PHONEPE_UAT_MERCHANT_ID');
        $apiKey = $isProd ? getenv('PHONEPE_PROD_API_KEY') : getenv('PHONEPE_UAT_API_KEY');
        $paymentURL = $isProd ? getenv('PHONEPE_PROD_URL') : getenv('PHONEPE_UAT_URL');

        $keyIndex = 1;
        $merchantTransactionId = "MT" . time() . rand(1000, 9999);
        $callbackURL = 'https://api.ticketbay.in/user/api/booking/payment/payment-response.php';

        $paymentData = array(
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => "MUIDSHETTY",
            'amount' => $amount * 100,
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
