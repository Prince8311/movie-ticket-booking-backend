<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../../utils/headers.php";

if ($requestMethod == 'POST') {
    require "../../../../../_db-connect.php";
    global $conn;

    $appEnv = getenv('APP_ENV');
    $response = $_POST;

    // Payment Credentials
    $merchantId = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_MERCHANT_ID') : getenv('PHONEPE_PROD_MERCHANT_ID');
    $apiKey = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_API_KEY') : getenv('PHONEPE_PROD_API_KEY');
    $statusURL = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_STATUS_URL') : getenv('PHONEPE_PROD_STATUS_URL');
    $keyIndex = 1;

    $string = '/pg/v1/status/' . $merchantId . '/' . $response['merchantTransactionId'] . $apiKey;
    $sha256 = hash('sha256', $string);
    $final_x_header = $sha256 . '###' . $keyIndex;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $statusURL . $merchantId . "/" . $response['merchantTransactionId']);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'accept: application/json',
            'X-VERIFY: ' . $final_x_header,
            'X-MERCHANT-ID:' . $merchantId
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $final = json_decode($response, true);

    if (!$final || !isset($final['success'])) {
        header("HTTP/1.0 500 Internal Server Error");
        echo json_encode([
            'status' => 500,
            'message' => 'Unable to verify payment'
        ]);
        exit;
    }

    if ($final) {
        $success = $final['success'];
        $message = $final['message'];
        $data = $final['data'];
        $merchantTransactionId = $data['merchantTransactionId'];

        if ($success && isset($data['state']) && $data['state'] === 'COMPLETED') {
            $transactionId = $data['transactionId'];
            $amount = $data['amount'] / 100;
            $paymentInstrument = $data['paymentInstrument'];
            $paymentType = $paymentInstrument['type'];
            $status = 'Confirmed';

            $bookingSql = "UPDATE `online_bookings` SET `status`='$status',`expires_at`=NULL WHERE `merchant_transaction_id`='$merchantTransactionId'";
            $bookingResult = mysqli_query($conn, $bookingSql);

            $paymentSql = "INSERT INTO `payment_history`(`transaction_id`, `merchant_transaction_id`, `payment_type`, `amount`) VALUES ('$transactionId','$merchantTransactionId','$paymentType','$amount')";
            $paymentResult = mysqli_query($conn, $paymentSql);

            if ($bookingResult && $paymentResult) {
                $data = [
                    'status' => 200,
                    'message' => 'Payment successful',
                    'merchantTransactionId' => $merchantTransactionId,
                    'transactionId' => $transactionId
                ];
                header("HTTP/1.0 200 OK");
                echo json_encode($data);
                exit;
            }
        } else {
            $deleteSql = "DELETE FROM `online_bookings` WHERE `merchant_transaction_id` = '$merchantTransactionId'";
            $deleteResult = mysqli_query($conn, $deleteSql);

            if ($deleteResult) {
                $data = [
                    'status' => 400,
                    'message' => 'Payment not completed',
                    'merchantTransactionId' => $merchantTransactionId
                ];
                header("HTTP/1.0 400 Failed");
                echo json_encode($data);
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
