<?php

require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['location'])) {
        $location = mysqli_real_escape_string($conn, $_GET['location']);
        $status = 'Published';

        $sql = "SELECT `id`, `name`, `state`, `city`, `location`, `status` FROM `registered_theaters` WHERE `city`='$location' AND `status`='$status'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $theaters = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $data = [
                'status' => 200,
                'message' => 'Theaters fetched.',
                'theaters' => $theaters
            ];
            header("HTTP/1.0 200 Theaters");
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
