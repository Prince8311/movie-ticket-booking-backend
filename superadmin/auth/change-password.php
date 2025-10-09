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

if ($requestMethod == 'POST') {
    require "../../_db-connect.php";
    global $conn;

    $userId = $_SESSION['userId'] ?? '';
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $password = mysqli_real_escape_string($conn, $inputData['password']);
        $confirmPassword = mysqli_real_escape_string($conn, $inputData['confirmPassword']);

        if($password == $confirmPassword) {
            $hashPass = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = "UPDATE `admin_users` SET `password`='$hashPass' WHERE `id` = '$userId'";
            $updateResult = mysqli_query($conn, $updateSql);

            if ($updateResult) {
                $data = [
                    'status' => 200,
                    'message' => 'Password changed successfully.',
                ];
                header("HTTP/1.0 200 Password changed");
                echo json_encode($data);
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Internal Server Error',
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else {
            $data = [
                'status' => 400,
                'message' => 'Password must match'
            ];
            header("HTTP/1.0 400 Bad Request");
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