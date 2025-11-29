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
        $normalizedDate = str_replace(',', '', $releaseDateRaw);
        $dateFormatted = date("d M, Y", strtotime($normalizedDate));
        $time = mysqli_real_escape_string($conn, $inputData['time']);

        $startDateTimeStr = $dateFormatted . " " . $time;
        $startDateTime = DateTime::createFromFormat("d M, Y h:i A", $startDateTimeStr);

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

            $data = [
                'status' => 200,
                'message' => 'Revised data',
                'end date' => $endDate,
                'end time' => $endTime
            ];
            header("HTTP/1.0 200 OK");
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
