<?php 

require "../../../../../utils/headers.php";
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

    if (isset($_GET['theaterName']) && isset($_GET['screen']) && isset($_GET['screenId']) && isset($_GET['section']) && isset($_GET['row'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName']);
        $screen = mysqli_real_escape_string($conn, $_GET['screen']);
        $screenId = mysqli_real_escape_string($conn, $_GET['screenId']);
        $section = mysqli_real_escape_string($conn, $_GET['section']);
        $row = mysqli_real_escape_string($conn, $_GET['row']);

        $sql = "SELECT * FROM `screen_rows` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section' AND `row`='$row'";
        $result = mysqli_query($conn, $sql);

        if($result) {
            $rowDetails = mysqli_fetch_assoc($result);
            $data = [
                'status' => 200,
                'message' => 'Row details fetched successfully.',
                'rowDetails' => $rowDetails,
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