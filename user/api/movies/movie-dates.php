<?php

require "../../../utils/headers.php";
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

    if (isset($_GET['name'])) {
        $movieName = mysqli_real_escape_string($conn, $_GET['name']);

        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $sql = "SELECT DISTINCT `start_date` FROM `theater_shows` WHERE `movie_name`='$movieName' AND (STR_TO_DATE(`start_date`, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(`start_date`, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(`start_time`, '%h:%i %p') > '$currentTime')) ORDER BY STR_TO_DATE(`start_date`, '%d %b, %Y') ASC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $dates = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $data = [
                'status' => 200,
                'message' => 'Movie dates.',
                'dates' => $dates
            ];
            header("HTTP/1.0 200 Movie dates");
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
            'message' => 'Movie name is required'
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
