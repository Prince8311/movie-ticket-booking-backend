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
        $userEmail = mysqli_real_escape_string($conn, $inputData['userEmail']);
        $userPhone = mysqli_real_escape_string($conn, $inputData['userPhone']);
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $movieName = mysqli_real_escape_string($conn, $inputData['movieName']);
        $ticketPrice = (float) $inputData['ticketPrice'];
        $baseConvenience = (float) $inputData['baseConvenience'];
        $gst = (float) $inputData['gst'];
        $theaterCommission = mysqli_real_escape_string($conn, $inputData['theaterCommission']);

        $amount = $ticketPrice + $baseConvenience + $gst;
        $appEnv = getenv('APP_ENV');

        // Payment Credentials
        $merchantUserId = 'MUIDSHETTY';
        $apiKey = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_API_KEY') : getenv('PHONEPE_PROD_API_KEY');
        $merchantId = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_MERCHANT_ID') : getenv('PHONEPE_PROD_MERCHANT_ID');
        $paymentURL = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_URL') : getenv('PHONEPE_PROD_URL');

        $keyIndex = 1;
        $merchantTransactionId = "MT" . time() . rand(1000, 9999);
        $callbackURL = 'https://api.ticketbay.in/user/api/booking/payment/payment-response.php';

        $paymentData = array(
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => $merchantUserId,
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

        $jsonencode = json_encode($paymentData);
        $payloadMain = base64_encode($jsonencode);
        $payload = $payloadMain . "/pg/v1/pay" . $apiKey;
        $sha256 = hash("sha256", $payload);
        $final_x_header = $sha256 . '###' . $keyIndex;
        $request = json_encode(array('request' => $payloadMain));

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $paymentURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-VERIFY: " . $final_x_header,
                "Accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "CURL Error #:" . $err;
        } else {
            $res = json_decode($response);
            if (isset($res->success) && $res->success === true) {
                $payURL = $res->data->instrumentResponse->redirectInfo->url;
                $sql = "UPDATE `online_bookings` SET `ticket_price`='$ticketPrice',`base_convenience`='$baseConvenience',`gst`='$gst',`theater_commission`='$theaterCommission',`merchant_transaction_id`='$merchantTransactionId' WHERE `booking_id`='$bookingId' AND `username`='$userName' AND `theater_name`='$theaterName' AND `movie_name`='$movieName'";
                $result = mysqli_query($conn, $sql);

                if ($result) {
                    $data = [
                        'status' => 200,
                        'success' => true,
                        'payURL' => $payURL
                    ];
                    header("HTTP/1.0 200 OK");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'success' => false,
                        'message' => 'Database error: ' . $error
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 400,
                    'success' => false,
                    'code' => $res->code,
                    'message' => $res->message
                ];
                header("HTTP/1.0 400 Failed");
                echo json_encode($data);
            }
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
