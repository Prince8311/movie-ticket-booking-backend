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
        $stateName = mysqli_real_escape_string($conn, $inputData['stateName'] ?? '');
        $currentCityName = mysqli_real_escape_string($conn, $inputData['currentCityName'] ?? '');
        $newCityName = mysqli_real_escape_string($conn, $inputData['newCityName'] ?? '');

        $updateSql = "UPDATE `state_cities` SET `city`='$newCityName' WHERE `state`='$stateName' AND `city`='$currentCityName'";
        $updateResult = mysqli_query($conn, $updateSql);

        if($updateResult) {
            $data = [
                'status' => 200,
                'message' => 'City name updated.'
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