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

    if (isset($_GET['name']) && isset($_GET['type'])) {
        $userName = mysqli_real_escape_string($conn, $_GET['name']);
        $type = mysqli_real_escape_string($conn, $_GET['type']);

        // Pagination
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0
            ? (int) $_GET['limit']
            : 24;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
            ? (int) $_GET['page']
            : 1;
        $offset = ($page - 1) * $limit;

        // Date & Time 
        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        $whereCondition = "";

        if ($type === 'upcoming') {
            $whereCondition = "AND ob.status = 'Confirmed' AND (STR_TO_DATE(ob.valid_date, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(ob.valid_date, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(ob.valid_time, '%h:%i %p') > '$currentTime'))";
        } elseif ($type === 'previous') {
            $whereCondition = "AND ((ob.status IN ('Confirmed', 'Checked') AND (STR_TO_DATE(ob.valid_date, '%d %b, %Y') < '$currentDate' OR ( STR_TO_DATE(ob.valid_date, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(ob.valid_time, '%h:%i%p') < '$currentTime'))) OR (ob.status = 'Checked' AND (STR_TO_DATE(ob.valid_date, '%d %b, %Y') > '$currentDate' OR (STR_TO_DATE(ob.valid_date, '%d %b, %Y') = '$currentDate' AND STR_TO_DATE(ob.valid_time, '%h:%i%p') > '$currentTime'))))";
        } elseif ($type === 'cancelled') {
            $whereCondition = "AND ob.status = 'Cancelled'";
        }

        // -----------------------
        // COUNT QUERY
        // -----------------------
        $countSql = "SELECT COUNT(*) AS total FROM online_bookings ob LEFT JOIN movies m ON m.name = ob.movie_name WHERE ob.username = '$userName' $whereCondition";
        $countResult = mysqli_query($conn, $countSql);
        $totalRecords = mysqli_fetch_assoc($countResult)['total'] ?? 0;

        // --------------------------------------------------
        // DATA QUERY
        // --------------------------------------------------
        $dataSql = "SELECT ob.*, m.poster_image FROM online_bookings ob LEFT JOIN movies m ON m.name = ob.movie_name WHERE ob.username = '$userName' $whereCondition ORDER BY STR_TO_DATE(ob.valid_date, '%d %b, %Y') DESC, STR_TO_DATE(ob.valid_time, '%h:%i%p') DESC LIMIT $limit OFFSET $offset";
        $dataResult = mysqli_query($conn, $dataSql);
        $list = [];

        if ($dataResult) {
            while ($row = mysqli_fetch_assoc($dataResult)) {
                $list[] = $row;
            }

            $data = [
                'status' => 200,
                'message' => 'Booking list fetched.',
                'totalCount' => (int) $totalRecords,
                'currentPage' => $page,
                'list' => $list
            ];
            header("HTTP/1.0 200 Booking list");
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
            'message' => 'Name and type required.'
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
