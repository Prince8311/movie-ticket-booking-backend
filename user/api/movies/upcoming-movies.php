<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    // Pagination
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
        ? (int) $_GET['page']
        : 1;
    $offset = ($page - 1) * $limit;

    // Date
    $currentDate = date("Y-m-d");

    // -----------------------
    // COUNT QUERY
    // -----------------------
    $countSql = "SELECT COUNT(*) AS total FROM `movies` WHERE `release_date` IS NULL OR TRIM(release_date) = '' OR STR_TO_DATE(`release_date`, '%d %b, %Y') > '$currentDate'";
    $countResult = mysqli_query($conn, $countSql);
    $countRow = mysqli_fetch_assoc($countResult);
    $totalMovies = (int) $countRow['total'];

    // -----------------------
    // DATA QUERY (with LIMIT)
    // -----------------------
    $sql = "SELECT * FROM `movies` WHERE `release_date` IS NULL OR TRIM(release_date) = '' OR STR_TO_DATE(`release_date`, '%d %b, %Y') > '$currentDate' ORDER BY `id` DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $movies = mysqli_fetch_assoc($result);

        $data = [
            'status' => 200,
            'message' => 'Upcoming movies fetched.',
            'totalCount' => $totalMovies,
            'currentPage' => $page,
            'movies' => $movies
        ];
        header("HTTP/1.0 200 Upcoming movies");
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
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
