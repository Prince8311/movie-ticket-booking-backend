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
            $totalAmount = $ticketPrice + $baseConvenience + $gst;

            $appEnv = getenv('APP_ENV');

            // Payment Credentials
            $merchantUserId = 'MUIDSHETTY';
            $apiKey = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_API_KEY') : getenv('PHONEPE_PROD_API_KEY');
            $merchantId = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_MERCHANT_ID') : getenv('PHONEPE_PROD_MERCHANT_ID');
            $refundURL = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_REFUND_URL') : getenv('PHONEPE_PROD_REFUND_URL');
            $keyIndex = 1;
            $merchantTransactionId = "MT" . time() . rand(1000, 9999);
            $callbackURL = 'https://api.ticketbay.in/user/api/booking/payment/refund-response.php';

            $payload = [
                "merchantId" => $merchantId,
                "merchantUserId" => $merchantUserId,
                "originalTransactionId" => $bookingMerchantTransactionId,
                "merchantTransactionId" => $merchantTransactionId,
                "amount" => $refundAmount * 100,
                "callbackUrl" => $callbackURL
            ];

            $encoded = base64_encode(json_encode($payload));
            $final_x_header = hash('sha256', $encoded . '/pg/v1/refund' . $apiKey) . '###' . $keyIndex;

            $ch = curl_init($refundURL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "X-VERIFY: $final_x_header"
                ],
                CURLOPT_POSTFIELDS => json_encode(["request" => $encoded])
            ]);

            $response = curl_exec($ch);
            $result = json_decode($response, true);

            if (!$result || empty($result['success']) || $result['success'] !== true) {
                http_response_code(400);
                echo json_encode([
                    'status' => 400,
                    'message' => 'Refund initiation failed',
                    'phonepe_response' => $result
                ]);
                exit;
            }

            $bookingCancelSql = "UPDATE `online_bookings` SET `status`='Cancelled' WHERE `booking_id`='$bookingId' AND `username`='$userName' AND `merchant_transaction_id`='$bookingMerchantTransactionId'";
            $bookingResult = mysqli_query($conn, $bookingCancelSql);

            $refundSql = "INSERT INTO `refund_history`(`booking_id`, `merchant_transaction_id`, `total_amount`, `refund_amount`, `status`) VALUES ('$bookingId','$merchantTransactionId','$totalAmount','$refundAmount','PENDING')";
            $refundResult = mysqli_query($conn, $refundSql);

            if ($bookingResult && $refundResult) {
                $data = [
                    'status' => 200,
                    'message' => 'Refund initiated. Amount will be credited shortly.',
                    'result' => $result
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
