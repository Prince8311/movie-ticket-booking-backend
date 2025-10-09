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

    if (isset($_POST['inputs']) && isset($_FILES['image'])) {
        $inputData = json_decode($_POST['inputs'], true);

        $name = mysqli_real_escape_string($conn, $inputData['name']);
        $languages = mysqli_real_escape_string($conn, $inputData['languages']);
        $activity = mysqli_real_escape_string($conn, $inputData['activity']);
        $genres = mysqli_real_escape_string($conn, $inputData['genres']);
        $formates = mysqli_real_escape_string($conn, $inputData['formates']);
        $date = mysqli_real_escape_string($conn, $inputData['date']);
        $time = mysqli_real_escape_string($conn, $inputData['time']);
        $trailer = mysqli_real_escape_string($conn, $inputData['trailer']);
        $casts = mysqli_real_escape_string($conn, $inputData['casts']);
        $crews = mysqli_real_escape_string($conn, $inputData['crews']);
        $description = mysqli_real_escape_string($conn, $inputData['description']);

        $imageData = $_FILES['image'];
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