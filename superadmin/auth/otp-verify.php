<?php

date_default_timezone_set('Asia/Kolkata');
require "../../utils/headers.php";

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
        $userName = $row['name'];
        $userEmail = $row['email'];
        $userPhone = $row['phone'];

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
                $payload = [
                    'id' => $userId,
                    'name' => $userName,
                    'email' => $userEmail,
                    'phone' => $userPhone,
                    'timestamp' => time(),
                ];
                $jsonPayload = json_encode($payload);
                $randomBytes = random_bytes(64);
                $tokenData = $jsonPayload . '|' . bin2hex($randomBytes);
                $authToken = base64_encode($tokenData);
                $expiresAt = date("Y-m-d H:i:s", time() + 120);

                $updateUserSql = "UPDATE `admin_users` SET `mail_otp` = NULL, `auth_token`='$authToken', `expires_at`='$expiresAt' WHERE `id` = '$userId'";
                mysqli_query($conn, $updateUserSql);

                if ($updateUserSql) {
                    setcookie(
                        "authToken",
                        $authToken,
                        [
                            'expires' => time() + 120,
                            'path' => '/',
                            'domain' => '.ticketbay.in',
                            'secure' => true,
                            'httponly' => true,
                            'samesite' => 'None'
                        ]
                    );

                    $data = [
                        'status' => 200,
                        'message' => 'Authentication Successful',
                        'authToken' => $authToken
                    ];
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Database error: ' . mysqli_error($conn)
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($data);
                }
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
