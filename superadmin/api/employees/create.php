<?php

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
    if (!empty($inputData)) {
        $userType = 'employee';
        $empName = mysqli_real_escape_string($conn, $inputData['name']);
        $empPhone = mysqli_real_escape_string($conn, $inputData['phone']);
        $empMail = mysqli_real_escape_string($conn, $inputData['email']);
        $empRole = mysqli_real_escape_string($conn, $inputData['roleName']);
        $password = mysqli_real_escape_string($conn, $inputData['password']);
        $confirmPassword = mysqli_real_escape_string($conn, $inputData['confirmPassword']);
        $status = 0;

        if($password == $confirmPassword) {
            $hashPass = password_hash($password,PASSWORD_DEFAULT);
            $sql = "INSERT INTO `users`(`name`, `email`, `phone`, `password`, `status`, `user_type`, `user_role`) VALUES ('$empName','$empMail','$empPhone','$hashPass','$status','$userType','$empRole')";
            $result = mysqli_query($conn, $sql);

            if($result) {
                $data = [
                    'status' => 200,
                    'message' => 'Employee created successfully.'
                ];
                header("HTTP/1.0 200 OK");
                echo json_encode($data);
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else {
            $data = [
                'status' => 400,
                'message' => 'Password mismatch'
            ];
            header("HTTP/1.0 400 Validation error");
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

?>