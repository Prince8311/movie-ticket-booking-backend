<?php

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

                        $data = [
                            'status' => 200,
                            'message' => 'Login Successful',
                            'userId' => $userId
                        ];
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
