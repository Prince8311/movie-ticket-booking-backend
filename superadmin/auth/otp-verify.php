<?php

ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'true'); // only if using HTTPS
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '.ticketbay.in',  // important for cross-subdomain (superadmin.ticketbay.in, api.ticketbay.in, etc.)
    'secure' => true,              // must be true if SameSite=None
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

if ($requestMethod == 'POST') {
    require "../../_db-connect.php";
    global $conn;

    $userId = $_SESSION['userId'] ?? '';
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $otp = mysqli_real_escape_string($conn, $inputData['otp']);
        $authentication = isset($inputData['authentication']) ? (bool)$inputData['authentication'] : false;

        $sql = "SELECT * FROM `admin_users` WHERE `id` = '$userId'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $savedOtp = $row['mail_otp'];

        if ($savedOtp === null) {
            $data = [
                'status' => 401,
                'message' => 'Authentication error',
                'userId' => $userId
            ];
            header("HTTP/1.0 401 Authentication error");
            echo json_encode($data);
            exit;
        }

        if ($savedOtp == $otp) {
            if ($authentication) {
                $authToken = bin2hex(random_bytes(64));
                setcookie("authToken", $authToken, time() + 86400, "/", ".ticketbay.in", true, true);

                $updateUserSql = "UPDATE `admin_users` SET `mail_otp` = NULL, `token`='$authToken' WHERE `id` = '$userId'";
                mysqli_query($conn, $updateUserSql);

                $data = [
                    'status' => 200,
                    'message' => 'Authentication Successful',
                    'userId' => $userId,
                    'authToken' => $authToken
                ];
            } else {
                $updateUserSql = "UPDATE `admin_users` SET `mail_otp` = NULL WHERE `id` = '$userId'";
                mysqli_query($conn, $updateUserSql);

                $data = [
                    'status' => 200,
                    'message' => 'Verification Successful'
                ];
            }

            header("HTTP/1.0 200 OK");
            echo json_encode($data);
        } else {
            $data = [
                'status' => 404,
                'message' => 'Wrong OTP',
            ];
            header("HTTP/1.0 404 Wrong OTP");
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
