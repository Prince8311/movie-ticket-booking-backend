<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['name']) && isset($_GET['date']) && isset($_GET['language']) && isset($_GET['format'])) {
        $movieName = mysqli_real_escape_string($conn, $_GET['name']);
        $date = mysqli_real_escape_string($conn, $_GET['date']);
        $language = mysqli_real_escape_string($conn, $_GET['language']);
        $format = mysqli_real_escape_string($conn, $_GET['format']);

        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $movieSql = "SELECT `total_time` FROM `movies` WHERE `name` = '$movieName'";
        $movieResult = mysqli_query($conn, $movieSql);

        $sql = "SELECT ts.*, rt.location FROM `theater_shows` ts LEFT JOIN `registered_theaters` rt ON rt.name = ts.theater_name WHERE ts.movie_name='$movieName' AND ts.language='$language' AND ts.format='$format' AND ts.start_date='$date' AND (STR_TO_DATE('$date', '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE('$date', '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(ts.start_time, '%h:%i %p') > '$currentTime')) ORDER BY STR_TO_DATE(ts.start_time, '%h:%i %p') ASC";
        $result = mysqli_query($conn, $sql);

        if ($result && $movieResult) {
            $rawTheaters = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $movieData = mysqli_fetch_assoc($movieResult);
            $groupedTheaters = [];
            $totalTime = $movieData['total_time'];

            foreach ($rawTheaters as $row) {
                $theaterName = $row['theater_name'];

                if (!isset($groupedTheaters[$theaterName])) {
                    $groupedTheaters[$theaterName] = [
                        'theater_name' => $theaterName,
                        'location' => $row['location'],
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
                'totalTime' => $totalTime,
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
