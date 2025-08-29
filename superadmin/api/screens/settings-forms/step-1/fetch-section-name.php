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

require "../../../../../utils/middleware.php";

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
    require "../../../../../_db-connect.php";
    global $conn;

    if (isset($_GET['theaterName']) && isset($_GET['screen']) && isset($_GET['screenId']) && isset($_GET['section'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName']);
        $screen = mysqli_real_escape_string($conn, $_GET['screen']);
        $screenId = mysqli_real_escape_string($conn, $_GET['screenId']);
        $section = mysqli_real_escape_string($conn, $_GET['section']);

        $fetchNameSql = "SELECT `section_name` FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
        $fetchNameResult = mysqli_query($conn, $fetchNameSql);

        if ($fetchNameResult) {
            $secData = mysqli_fetch_assoc($fetchNameResult);
            $sectionName = $secData['section_name'];

            $data = [
                'status' => 200,
                'message' => 'Section name fetched successfully.',
                'sectionName' => $sectionName
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
            'message' => 'Section is missing'
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