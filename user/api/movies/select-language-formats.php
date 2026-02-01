<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['name'])) {
        $movieName = mysqli_real_escape_string($conn, $_GET['name']);

        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $sql = "SELECT DISTINCT `language`, `format` FROM `theater_shows` WHERE `movie_name`='$movieName' AND (STR_TO_DATE(`start_date`, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(`start_date`, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(`start_time`, '%h:%i %p') = '$currentTime'))";
        $result = mysqli_query($conn, $sql);
        $grouped = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $language = $row['language'];
                $format   = $row['format'];

                if (!isset($grouped[$language])) {
                    $grouped[$language] = [];
                }

                if (!in_array($format, $grouped[$language])) {
                    $grouped[$language][] = $format;
                }
            }
            $response = [];
            foreach ($grouped as $language => $formats) {
                $response[] = [
                    'language' => $language,
                    'formats'  => $formats
                ];
            }
            $data = [
                'status' => 200,
                'message' => 'Languages & formats fetched',
                'data' => $response
            ];
            header("HTTP/1.0 200 OK");
            echo json_encode($data);
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Name is required'
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
