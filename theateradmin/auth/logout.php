<?php 

require "../../utils/headers.php";
require "../../utils/middleware.php";

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
    require "../../_db-connect.php";
    global $conn;

    $token = $authResult['token'];
    $sql = "UPDATE `theater_users` SET `token`= NULL WHERE `token`='$token'";
    $result = mysqli_query($conn, $sql);
    session_destroy();
    setcookie(
        "authToken",
        "",
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '.ticketbay.in',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]
    );
    $data = [
        'status' => 200,
        'message' => 'Logged out successfylly.',
    ];
    header("HTTP/1.0 200 Logged Out");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>