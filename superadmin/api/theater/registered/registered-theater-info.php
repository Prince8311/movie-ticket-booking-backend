<?php 

require "../../../../utils/headers.php";
require "../../../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();

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
    require "../../../../_db-connect.php";
    global $conn;

    if(isset($_GET['name'])) {
        $theaterName  = mysqli_real_escape_string($conn, $_GET['name'] ?? '');
        $sql = "SELECT rt.*, tu.* FROM registered_theaters rt INNER JOIN theater_users tu ON rt.name = tu.theater_name WHERE rt.name = '$theaterName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $theaterInfo = mysqli_fetch_assoc($result);
            $data = [
                'status' => 200,
                'message' => 'Theater info fetched successfully.',
                'theaterInfo' => $theaterInfo,
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
            'message' => 'Theater name is missing'
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