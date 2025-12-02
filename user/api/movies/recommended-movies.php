<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";
require "../../utils/middleware.php";

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

    if (isset($_GET['location'])) {
        $location = mysqli_real_escape_string($conn, $_GET['location']);
        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $theaterSql = "SELECT `name` FROM `registered_theaters` WHERE `city`='$location'";
        $theaterResult = mysqli_query($conn, $theaterSql);
        $theaters = [];
        while ($row = mysqli_fetch_assoc($theaterResult)) {
            $theaters[] = $row['name'];
        }
        $theaterList = "'" . implode("','", $theaters) . "'";

        $sql = "SELECT ts.movie_name, m.poster_image FROM theater_shows ts JOIN movies m ON ts.movie_name = m.name WHERE ts.theater_name IN ($theaterList) AND m.release_date <= '$currentDate' AND (ts.start_date > '$currentDate' OR (ts.start_date = '$currentDate' AND ts.start_time > '$currentTime')) GROUP BY ts.movie_name";
        $result = mysqli_query($conn, $sql);

        $movies = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $movies[] = $row;
            }

            $data = [
                'status' => 200,
                'message' => 'Recommended movies fetched.',
                'movies' => $movies
            ];
            header("HTTP/1.0 200 Recommended movies");
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
            'message' => 'Location is required'
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
