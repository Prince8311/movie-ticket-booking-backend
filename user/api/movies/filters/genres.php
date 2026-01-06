<?php

require "../../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../../_db-connect.php";
    global $conn;

    $sql = "SELECT `name` FROM `movie_genres`";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $genres = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $data = [
            'status' => 200,
            'message' => 'Genres fetched successfully.',
            'genres' => $genres
        ];
        header("HTTP/1.0 200 Genres");
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
