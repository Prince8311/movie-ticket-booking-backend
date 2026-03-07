<?php

require "../../utils/headers.php";

if ($requestMethod == 'POST') {
    require "../../_db-connect.php";
    global $conn;

    $sessionEmail = $_SESSION['userEmail'] ?? '';
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $requestEmail = $inputData['email'] ?? '';
        $userEmail = !empty($requestEmail) ? $requestEmail : $sessionEmail;
        if (empty($userEmail)) {
            $data = [
                'status' => 400,
                'message' => 'Email is required.'
            ];
            header("HTTP/1.0 400 Bad Request");
            echo json_encode($data);
            exit;
        }
        $password = mysqli_real_escape_string($conn, $inputData['password']);
        $confirmPassword = mysqli_real_escape_string($conn, $inputData['confirmPassword']);

        if ($password == $confirmPassword) {
            $sql = "SELECT * FROM `users` WHERE `email` = '$userEmail'";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                $data = [
                    'status' => 400,
                    'message' => 'New password cannot be the same as your previous password.'
                ];
                header("HTTP/1.0 400 Bad Request");
                echo json_encode($data);
                exit;
            }
            $hashPass = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = "UPDATE `users` SET `password`='$hashPass' WHERE `email`='$userEmail'";
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
                'message' => 'Password should be matched.'
            ];
            header("HTTP/1.0 400 Not matched");
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
