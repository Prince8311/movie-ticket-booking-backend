<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['name'])) {
        $movieName = mysqli_real_escape_string($conn, $_GET['name']);
        $location = mysqli_real_escape_string($conn, $_GET['location']);
        $language = mysqli_real_escape_string($conn, $_GET['language']);
        $format = mysqli_real_escape_string($conn, $_GET['format']);

        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        // Theater list
        $theaterSql = "SELECT `name` FROM `registered_theaters` WHERE `city`='$location'";
        $theaterResult = mysqli_query($conn, $theaterSql);
        $theaters = [];
        while ($row = mysqli_fetch_assoc($theaterResult)) {
            $theaters[] = $row['name'];
        }
        if (empty($theaters)) {
            echo json_encode([
                'status' => 200,
                'message' => 'No theaters found for this location.',
            ]);
            exit;
        }
        $theaterList = "'" . implode("','", $theaters) . "'";

        $sql = "SELECT DISTINCT `start_date` FROM `theater_shows` WHERE `theater_name` IN ($theaterList) AND `movie_name`='$movieName' AND `language`='$language' AND `format`='$format' AND (STR_TO_DATE(`start_date`, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(`start_date`, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(`start_time`, '%h:%i %p') > '$currentTime')) ORDER BY STR_TO_DATE(`start_date`, '%d %b, %Y') ASC";
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
