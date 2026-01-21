<?php

date_default_timezone_set('Asia/Kolkata');

require "../../../../utils/headers.php";
require "../../../../_db-connect.php";
global $conn;

/* -------------------------------------------------
 | CONFIG
 * -------------------------------------------------*/
$appEnv   = getenv('APP_ENV');
$apiKey   = ($appEnv === 'uat')
    ? getenv('PHONEPE_UAT_API_KEY')
    : getenv('PHONEPE_PROD_API_KEY');

$keyIndex = 1;
$logFile  = __DIR__ . '/refund_callback.log';

/* -------------------------------------------------
 | READ RAW BODY
 * -------------------------------------------------*/
$rawBody = file_get_contents("php://input");
$payload = json_decode($rawBody, true);

/* -------------------------------------------------
 | LOG EVERYTHING (FIRST)
 * -------------------------------------------------*/
file_put_contents(
    $logFile,
    json_encode([
        'time'     => date('Y-m-d H:i:s'),
        'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'headers'  => [
            'X-VERIFY'      => $_SERVER['HTTP_X_VERIFY'] ?? null,
            'X-MERCHANT-ID'=> $_SERVER['HTTP_X_MERCHANT_ID'] ?? null,
        ],
        'raw_body' => $rawBody,
    ], JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

/* -------------------------------------------------
 | VERIFY SIGNATURE
 * -------------------------------------------------*/
if (!isset($_SERVER['HTTP_X_VERIFY'])) {
    http_response_code(400);
    exit("Missing X-VERIFY header");
}

$receivedChecksum   = $_SERVER['HTTP_X_VERIFY'];
$calculatedChecksum = hash('sha256', $rawBody . $apiKey) . "###" . $keyIndex;

if ($receivedChecksum !== $calculatedChecksum) {
    http_response_code(401);
    exit("Invalid signature");
}

/* -------------------------------------------------
 | DECODE RESPONSE
 * -------------------------------------------------*/
if (!isset($payload['response'])) {
    http_response_code(400);
    exit("Missing response field");
}

$decodedResponse = base64_decode($payload['response']);
$responseData   = json_decode($decodedResponse, true);

if (!$responseData || !isset($responseData['data'])) {
    http_response_code(400);
    exit("Invalid decoded payload");
}

/* -------------------------------------------------
 | ENSURE THIS IS A REFUND CALLBACK
 * -------------------------------------------------*/
$code = $responseData['code']; // REFUND_SUCCESS / REFUND_FAILED

if (!str_starts_with($code, 'REFUND_')) {
    http_response_code(200);
    exit("Not a refund callback");
}

/* -------------------------------------------------
 | EXTRACT REFUND DATA
 * -------------------------------------------------*/
$merchantRefundId = $responseData['data']['merchantRefundId'] ?? null;
$transactionId    = $responseData['data']['transactionId'] ?? null;
$amount           = isset($responseData['data']['amount'])
    ? $responseData['data']['amount'] / 100
    : null;

if (!$merchantRefundId) {
    http_response_code(400);
    exit("Missing merchantRefundId");
}

$merchantRefundId = mysqli_real_escape_string($conn, $merchantRefundId);
$transactionId    = mysqli_real_escape_string($conn, $transactionId ?? '');

/* -------------------------------------------------
 | CHECK EXISTING REFUND
 * -------------------------------------------------*/
$checkSql = "
SELECT status 
FROM refund_history 
WHERE merchant_transaction_id = '$merchantRefundId'
LIMIT 1
";

$checkResult = mysqli_query($conn, $checkSql);

if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
    http_response_code(200);
    exit("Refund record not found");
}

$refundRow = mysqli_fetch_assoc($checkResult);

if ($refundRow['status'] !== 'PENDING') {
    http_response_code(200);
    exit("Refund already processed");
}

/* -------------------------------------------------
 | MAP STATUS
 * -------------------------------------------------*/
$status = strtoupper(str_replace('REFUND_', '', $code)); // SUCCESS / FAILED

$allowedStatuses = ['SUCCESS', 'FAILED', 'PENDING'];

if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    exit("Unknown refund status");
}

/* -------------------------------------------------
 | UPDATE REFUND TABLE
 * -------------------------------------------------*/
$updateSql = "UPDATE refund_history SET transaction_id = '$transactionId', status = '$status' 
WHERE merchant_transaction_id = '$merchantRefundId'
";

$updateResult = mysqli_query($conn, $updateSql);

/* -------------------------------------------------
 | HANDLE RESULT
 * -------------------------------------------------*/
if (!$updateResult) {
    file_put_contents(
        $logFile,
        "SQL ERROR: " . mysqli_error($conn) . PHP_EOL,
        FILE_APPEND
    );

    http_response_code(500);
    exit("Database update failed");
}

http_response_code(200);
echo "OK";
