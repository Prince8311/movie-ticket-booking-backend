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
        $roleName = mysqli_real_escape_string($conn, $inputData['roleName']);

        $checkUserSql = "SELECT * FROM `users` WHERE `user_role` = '$roleName'";
        $checkResult = mysqli_query($conn, $checkUserSql);

        if(mysqli_num_rows($checkResult) > 0) {
            $data = [
                'status' => 400,
                'message' => 'Employee exits with this role.'
            ];
            header("HTTP/1.0 400 Bad Request");
            echo json_encode($data);
        } else {
            $deleteSQL = "DELETE FROM `roles_permissions` WHERE `role_name` = '$roleName'";
            $deleteResult = mysqli_query($conn, $deleteSQL);
    
            if ($deleteResult) {
                $data = [
                    'status' => 200,
                    'message' => 'Role deleted successfully.'
                ];
                header("HTTP/1.0 200 Role deleted");
                echo json_encode($data);
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
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
