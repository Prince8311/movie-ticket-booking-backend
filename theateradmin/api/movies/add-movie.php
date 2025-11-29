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

if ($requestMethod == 'POST') {
    require "../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);
    if (!empty($inputData)) {
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $movieName = mysqli_real_escape_string($conn, $inputData['movieName']);
        $language = mysqli_real_escape_string($conn, $inputData['language']);
        $format = mysqli_real_escape_string($conn, $inputData['format']);

        $dateRaw = $inputData['date'];
        $normalizedDate = str_replace(',', '', $dateRaw);
        $dateFormatted = date("d M, Y", strtotime($normalizedDate));

        $time = mysqli_real_escape_string($conn, $inputData['time']);

        $startDateTimeStr = $dateFormatted . " " . $time;
        $startDateTime = DateTime::createFromFormat("d M, Y h:i A", $startDateTimeStr);
        $newStart = $startDateTime->format("Y-m-d H:i:s");

        $movieSql = "SELECT * FROM `movies` WHERE `name`='$movieName'";
        $movieResult = mysqli_query($conn, $movieSql);

        if ($movieResult) {
            $movieData = mysqli_fetch_assoc($movieResult);
            $movieTime = $movieData['total_time'];

            preg_match('/(\d+)hr\s*(\d+)min/', $movieTime, $matches);
            $durationHours = (int)$matches[1];
            $durationMinutes = (int)$matches[2];

            $startDateTime->modify("+{$durationHours} hours");
            $startDateTime->modify("+{$durationMinutes} minutes");

            $endDate = $startDateTime->format("d M, Y");
            $endTime = $startDateTime->format("h:i A");

            $newEnd = $startDateTime->format("Y-m-d H:i:s");

            $overlapSql = "SELECT * FROM theater_shows WHERE theater_name = '$theaterName' AND screen_id = '$screenId' AND (STR_TO_DATE(CONCAT(start_date, ' ', start_time), '%d %M, %Y %h:%i %p') < '$newEnd' AND STR_TO_DATE(CONCAT(end_date, ' ', end_time), '%d %M, %Y %h:%i %p') > '$newStart')";

            $overlapResult = mysqli_query($conn, $overlapSql);

            if (mysqli_num_rows($overlapResult) > 0) {
                $data = [
                    'status' => 201,
                    'message' => 'A show is already running at this time.'
                ];
                header("HTTP/1.0 201 Conflict");
                echo json_encode($data);
                exit;
            }

            $sql = "INSERT INTO `theater_shows`(`theater_name`, `screen`, `screen_id`, `movie_name`, `language`, `format`, `start_date`, `start_time`, `end_date`, `end_time`) VALUES ('$theaterName','$screen','$screenId','$movieName','$language','$format','$dateFormatted','$time','$endDate','$endTime')";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $data = [
                    'status' => 200,
                    'message' => 'Show added successfully.'
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
                'status' => 500,
                'message' => 'Database error: ' . $error
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
