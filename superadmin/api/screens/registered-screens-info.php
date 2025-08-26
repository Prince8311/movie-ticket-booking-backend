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

    if(isset($_GET['name']) && isset($_GET['screen'])) {
        $theaterName  = mysqli_real_escape_string($conn, $_GET['name'] ?? '');
        $screen  = mysqli_real_escape_string($conn, $_GET['screen'] ?? '');

        $sql = "SELECT * FROM `registered_screens` WHERE `theater_name`='$theaterName' AND `screen`='$screen'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $screenInfo = mysqli_fetch_assoc($result);
            $data = [
                'status' => 200,
                'message' => 'Screen info fetched successfully.',
                'screenInfo' => $screenInfo,
            ];
            header("HTTP/1.0 200 OK");
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
            'message' => 'Theater name and screen is missing'
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