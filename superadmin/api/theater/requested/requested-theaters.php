<?php

require "../../../../../utils/headers.php";
require "../../../../utils/middleware.php";

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
    require "../../../../_db-connect.php";
    global $conn;

    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
        ? (int)$_GET['page']
        : 1;
    $offset = ($page - 1) * $limit;
    $sql = "SELECT `name`, `theater_name`, `email`, `phone`, `city` FROM `requested_theaters`";
    $result = mysqli_query($conn, $sql);
    $totalTheaters = mysqli_num_rows($result);
    $limitSql = "SELECT `name`, `theater_name`, `email`, `phone`, `city` FROM `requested_theaters` LIMIT $limit OFFSET $offset";
    $limitResult = mysqli_query($conn, $limitSql);

    if($result) {
        $theaters = mysqli_fetch_all($limitResult, MYSQLI_ASSOC);
        $data = [
            'status' => 200,
            'message' => 'Requested theaters fetched.',
            'totalCount' => $totalTheaters,
            'currentPage' => $page,
            'theaters' => $theaters
        ];
        header("HTTP/1.0 200 Theater list");
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
