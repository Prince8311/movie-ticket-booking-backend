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

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    // Date & Time 
    $currentDate = date("Y-m-d");
    $oneMonthAfterDate = date('Y-m-d', strtotime('+1 month'));

    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
        ? (int)$_GET['page']
        : 1;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $searchWhere = !empty($search) ? "AND `name` LIKE '%$search%'" : '';
    $where = "WHERE `release_date` IS NOT NULL AND TRIM(release_date) != '' AND (STR_TO_DATE(release_date, '%d %b, %Y') < '$currentDate' OR STR_TO_DATE(release_date, '%d %b, %Y') BETWEEN '$currentDate' AND '$oneMonthAfterDate') $searchWhere";
    $countSql = "SELECT COUNT(*) as total FROM `movies` $where";
    $countResult = mysqli_query($conn, $countSql);
    $countRow = mysqli_fetch_assoc($countResult);
    $totalMovies = (int)$countRow['total'];

    $sql = "SELECT * FROM `movies` $where ORDER BY `id` DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $movies = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $data = [
            'status' => 200,
            'message' => 'Movies fetched.',
            'totalCount' => $totalMovies,
            'currentPage' => $page,
            'currentDate' => $currentDate,
            'oneMonthAfterDate' => $oneMonthAfterDate,
            'movies' => $movies
        ];
        header("HTTP/1.0 200 Movie list");
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
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
