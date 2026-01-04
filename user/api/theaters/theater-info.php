<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['name']) && isset($_GET['date'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['name']);
        $date = mysqli_real_escape_string($conn, $_GET['date']);

        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $theaterSql = "SELECT `location` FROM `registered_theaters` WHERE `name` = '$theaterName'";
        $theaterResult = mysqli_query($conn, $theaterSql);

        $sql = "SELECT * FROM `theater_shows` WHERE `theater_name`='$theaterName' AND `start_date`='$date' AND (STR_TO_DATE('$date', '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE('$date', '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(`start_time`, '%d %b, %Y') > '$currentTime')) ORDER BY STR_TO_DATE(`start_time`, '%d %b, %Y') ASC";
        $result = mysqli_query($conn, $sql);

        if ($result && $theaterResult) {
            $rawMovies = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $theaterData = mysqli_fetch_assoc($theaterResult);
            $groupedMovies = [];
            $theaterLocation = $theaterData['location'];

            foreach ($rawMovies as $row) {
                $movieName = $row['movie_name'];

                if (!isset($groupedMovies[$movieName])) {
                    $groupedMovies[$movieName] = [
                        'movie_name' => $movieName,
                        'timings' => []
                    ];
                }

                $groupedMovies[$movieName]['timings'][] = [
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

            $movies = array_values($groupedMovies);
            $data = [
                'status' => 200,
                'message' => 'Theater movies.',
                'location' => $theaterLocation,
                'movies' => $movies
            ];
            header("HTTP/1.0 200 Movie movies");
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
            'message' => 'Theater name & date is required'
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
