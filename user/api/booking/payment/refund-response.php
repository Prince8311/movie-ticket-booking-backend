<?php

date_default_timezone_set('Asia/Kolkata');
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