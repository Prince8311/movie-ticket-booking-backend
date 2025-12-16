<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";
require "../../../utils/middleware.php";

$authResult = userAuthenticateRequest();
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

    if (isset($_GET['name']) && isset($_GET['date'])) {
        $movieName = mysqli_real_escape_string($conn, $_GET['name']);
        $date = mysqli_real_escape_string($conn, $_GET['date']);

        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $sql = "SELECT * FROM `theater_shows` WHERE `movie_name`='$movieName' AND `start_date`='$date' AND (STR_TO_DATE('$date', '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE('$date', '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(`start_time`, '%d %b, %Y') > '$currentTime')) ORDER BY STR_TO_DATE(`start_time`, '%d %b, %Y') ASC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $rawTheaters = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $groupedTheaters = [];

            foreach ($rawTheaters as $row) {
                $theaterName = $row['theater_name'];

                if (!isset($groupedTheaters[$theaterName])) {
                    $groupedTheaters[$theaterName] = [
                        'theater_name' => $theaterName,
                        'timings' => []
                    ];
                }

                $groupedTheaters[$theaterName]['timings'][] = [
                    'screen' => $row['screen'],
                    'screen_id' => $row['screen_id'],
                    'language' => $row['language'],
                    'format' => $row['format'],
                    'start_date' => $row['start_date'],
                    'start_time' => $row['start_time'],
                    'end_date' => $row['end_date'],
                    'end_time' => $row['end_time']
                ];
            }

            $theaters = array_values($groupedTheaters);
            $data = [
                'status' => 200,
                'message' => 'Theater timings.',
                'theaters' => $theaters
            ];
            header("HTTP/1.0 200 Theater timings");
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
            'message' => 'Movie name & date is required'
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
