<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../utils/headers.php";
require "../../../../_db-connect.php";
global $conn;

$appEnv = getenv('APP_ENV');
$apiKey = ($appEnv === 'uat') ? getenv('PHONEPE_UAT_API_KEY') : getenv('PHONEPE_PROD_API_KEY');
$keyIndex = 1;
$logFile = __DIR__ . '/refund_callback.log';

$rawBody = file_get_contents("php://input");
$payload = json_decode($rawBody, true);

$logData = [
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'headers' => [
        'X-VERIFY' => $_SERVER['HTTP_X_VERIFY'] ?? null,
        'X-MERCHANT-ID' => $_SERVER['HTTP_X_MERCHANT_ID'] ?? null,
    ],
    'raw_body' => $rawBody,
    'payload' => $payload
];

file_put_contents(
    $logFile,
    json_encode($logData, JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if (!isset($_SERVER['HTTP_X_VERIFY'])) {
    http_response_code(400);
    exit("Missing X-VERIFY header");
}

$receivedChecksum = $_SERVER['HTTP_X_VERIFY'];
$calculatedChecksum = hash('sha256', $rawBody . $apiKey) . "###" . $keyIndex;

if ($receivedChecksum !== $calculatedChecksum) {
    http_response_code(401);
    exit("Invalid signature");
}

if (
    empty($payload) ||
    !isset($payload['merchantTransactionId']) ||
    !isset($payload['code'])
) {
    http_response_code(400);
    exit("Invalid payload");
}

$merchantTxnId = mysqli_real_escape_string($conn, $payload['merchantTransactionId']);
$code = $payload['code']; // PAYMENT_SUCCESS / PAYMENT_FAILED / PAYMENT_PENDING
$transactionId = $payload['transactionId'] ?? null;
$amount = isset($payload['amount']) ? ($payload['amount'] / 100) : null;

$refundSql = "SELECT * FROM `refund_history` WHERE `merchant_transaction_id`='$merchantTxnId'";
$refundResult = mysqli_query($conn, $refundSql);

if (!$refundResult || mysqli_num_rows($refundResult) === 0) {
    http_response_code(200);
    exit("Refund record not found");
}

$refund = mysqli_fetch_assoc($refundResult);

if ($refund['status'] !== 'PENDING') {
    http_response_code(200);
    exit("Already processed");
}

$status = strtoupper(str_replace('PAYMENT_', '', $code));
$allowedStatuses = ['SUCCESS', 'FAILED', 'PENDING'];

if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    exit("Unknown payment status");
}

$refundUpdateSql = "UPDATE `refund_history` SET `transaction_id`='$transactionId',`status`='$status' WHERE `merchant_transaction_id`='$merchantTxnId'";
$updateResult = mysqli_query($conn, $refundUpdateSql);

if ($updateResult) {
    http_response_code(200);
    echo "OK";
}
