<?php

date_default_timezone_set('Asia/Kolkata');
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

if ($requestMethod == 'POST') {
    require "../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $movieName = mysqli_real_escape_string($conn, $inputData['movieName']);
        $language = mysqli_real_escape_string($conn, $inputData['language']);
        $format = mysqli_real_escape_string($conn, $inputData['format']);
        $day = mysqli_real_escape_string($conn, $inputData['day']);
        $startTime = mysqli_real_escape_string($conn, $inputData['startTime']);
        $startDate = mysqli_real_escape_string($conn, $inputData['startDate']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $section = mysqli_real_escape_string($conn, $inputData['section']);
        $seats = mysqli_real_escape_string($conn, $inputData['seats']);

        $cleanUserName  = preg_replace('/\s+/', '', $userName);
        $cleanMovieName = preg_replace('/\s+/', '', $movieName);

        $userChar  = isset($cleanUserName[1])  ? strtoupper($cleanUserName[1])  : 'x';
        $movieChar = isset($cleanMovieName[1]) ? strtoupper($cleanMovieName[1]) : 'x';

        $randomThree = random_int(100, 999);
        $randomOne   = random_int(0, 9);

        $bookingId = 'TKB' . $randomThree . $userChar . $movieChar . $randomOne;

        $validTime = '';
        $validDate = '';

        $movieSql = "SELECT * FROM `theater_shows` WHERE `theater_name` = '$theaterName' AND `screen` = '$screen' AND `screen_id` = '$screenId' AND `movie_name` = '$movieName' AND `start_date` = '$startDate' AND `start_time` = '$startTime'";
        $movieResult = mysqli_query($conn, $movieSql);

        if ($movieResult && mysqli_num_rows($movieResult) === 1) {
            $movieData = mysqli_fetch_assoc($movieResult);
            $validDate = $movieData['end_date'];
            $validTime = $movieData['end_time'];
        } else {
            $validTime = '';
            $validDate = '';
            $data = [
                'status' => 500,
                'message' => 'Database error: ' . $error
            ];
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode($data);
            exit;
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
