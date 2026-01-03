<?php

require "../../utils/headers.php";

if ($requestMethod == 'POST') {
    require "../../_db-connect.php";
    global $conn;

    $userEmail = $_SESSION['userEmail'] ?? '';
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $password = mysqli_real_escape_string($conn, $inputData['password']);
        $confirmPassword = mysqli_real_escape_string($conn, $inputData['confirmPassword']);

        if ($password == $confirmPassword) {
            $hashPass = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = "UPDATE `users` SET `password`='$hashPass' WHERE `email`='$userEmail'";
            $updateResult = mysqli_query($conn, $updateSql);

            if ($updateResult) {
                $data = [
                    'status' => 200,
                    'message' => 'Password updated.'
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
