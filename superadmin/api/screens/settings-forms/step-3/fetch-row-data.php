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

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['theaterName']) && isset($_GET['screen']) && isset($_GET['screenId']) && isset($_GET['section']) && isset($_GET['row'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName']);
        $screen = mysqli_real_escape_string($conn, $_GET['screen']);
        $screenId = mysqli_real_escape_string($conn, $_GET['screenId']);
        $section = mysqli_real_escape_string($conn, $_GET['section']);
        $row = mysqli_real_escape_string($conn, $_GET['row']);
    } else {
        $data = [
            'status' => 400,
            'message' => 'Parameters are missing.'
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