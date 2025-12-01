<?php

date_default_timezone_set('Asia/Kolkata');
require "../../utils/headers.php";

if ($requestMethod == 'POST') {
    require "../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $user = mysqli_real_escape_string($conn, $inputData['name']);
        $password = mysqli_real_escape_string($conn, $inputData['password']);

        $sql = "SELECT * FROM `users` WHERE `name`='$user' OR `email`='$user' OR `phone`='$user'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $num = mysqli_num_rows($result);

            if ($num == 1) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if (password_verify($password, $row['password'])) {
                        $userId = $row['id'];
                        $userName = $row['name'];
                        $userEmail = $row['email'];
                        $userPhone = $row['phone'];

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
                        $expiresAt = date("Y-m-d H:i:s", time() + 86400);

                        $updateSql = "UPDATE `users` SET `auth_token`='$authToken',`expires_at`='$expiresAt' WHERE `id`='$userId'";
                        $updateResult = mysqli_query($conn, $updateSql);

                        if ($updateResult) {
                            setcookie(
                                "authToken",
                                $authToken,
                                [
                                    'expires'  => time() + 86400,
                                    'path'     => '/',
                                    'domain'   => '.ticketbay.in',
                                    'secure'   => true,
                                    'httponly' => true,
                                    'samesite' => 'None'
                                ]
                            );

                            $data = [
                                'status' => 200,
                                'message' => 'Login Successful',
                                'authToken' => $authToken
                            ];
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
                            'status' => 400,
                            'message' => 'Invalid Credentials',
                        ];
                        header("HTTP/1.0 400 Forbidden");
                        echo json_encode($data);
                    }
                }
            } else {
                $data = [
                    'status' => 404,
                    'message' => 'User Not Found',
                ];
                header("HTTP/1.0 404 User Not Found");
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
