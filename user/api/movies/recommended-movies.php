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

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['location'])) {
        $location = mysqli_real_escape_string($conn, $_GET['location']);

        // Pagination
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
            ? (int) $_GET['page']
            : 1;
        $offset = ($page - 1) * $limit;

        // Date & Time 
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
                'totalCount' => 0,
                'currentPage' => $page,
                'movies' => []
            ]);
            exit;
        }
        $theaterList = "'" . implode("','", $theaters) . "'";

        // -----------------------
        // COUNT QUERY
        // -----------------------
        $countSql = "SELECT COUNT(DISTINCT ts.movie_name) AS total FROM theater_shows ts JOIN movies m ON ts.movie_name = m.name WHERE ts.theater_name IN ($theaterList) AND STR_TO_DATE(m.release_date, '%d %b, %Y') <= '$currentDate' AND (STR_TO_DATE(ts.start_date, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(ts.start_date, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(ts.start_time, '%h:%i %p') > '$currentTime'))";
        $countResult  = mysqli_query($conn, $countSql);
        $countRow = mysqli_fetch_assoc($countResult);
        $totalMovies = (int) $countRow['total'];

        // -----------------------
        // DATA QUERY (with LIMIT)
        // -----------------------
        $sql  = "SELECT ts.movie_name, m.poster_image FROM theater_shows ts JOIN movies m ON ts.movie_name = m.name WHERE ts.theater_name IN ($theaterList) AND STR_TO_DATE(m.release_date, '%d %b, %Y') <= '$currentDate' AND (STR_TO_DATE(ts.start_date, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(ts.start_date, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(ts.start_time, '%h:%i %p') > '$currentTime')) GROUP BY ts.movie_name ORDER BY ts.start_date ASC, ts.start_time ASC LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $sql);

        $movies = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $movies[] = $row;
            }

            $data = [
                'status' => 200,
                'message' => 'Recommended movies fetched.',
                'totalCount' => $totalMovies,
                'currentPage' => $page,
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
