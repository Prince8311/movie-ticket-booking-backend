<?php

require "../../../utils/headers.php";
require "../../../utils/middleware.php";

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
    require "../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (empty($inputData)) {
        $data = [
            'status' => 400,
            'message' => 'Empty request data'
        ];
        header("HTTP/1.0 400 Bad Request");
        echo json_encode($data);
        exit;
    }

    $userId = mysqli_real_escape_string($conn, $inputData['userId']);
    $currentPassword = mysqli_real_escape_string($conn, $inputData['currentPassword']);
    $newPassword = mysqli_real_escape_string($conn, $inputData['newPassword']);
    $confirmPassword = mysqli_real_escape_string($conn, $inputData['confirmPassword']);

    $userSql = "SELECT * FROM `users` WHERE `id`='$userId'";
    $userResult = mysqli_query($conn, $userSql);

    if ($userResult) {
        $user = mysqli_fetch_assoc($userResult);
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword !== $confirmPassword) {
                $data = [
                    'status' => 400,
                    'message' => 'Password is not matched.',
                ];
                header("HTTP/1.0 400 Forbidden");
                echo json_encode($data);
            }
            $hashPass = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE `users` SET `password`='$newPassword' WHERE `id`='$userId'";
            $updateResult = mysqli_query($conn, $updateSql);
            if ($updateResult) {
                $data = [
                    'status' => 200,
                    'message' => 'Password updated successfully.'
                ];
                header("HTTP/1.0 200 Ok");
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
                'message' => 'Current password is invalid.',
            ];
            header("HTTP/1.0 400 Forbidden");
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
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
