<?php

require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['screen']) && isset($_GET['screenId']) && isset($_GET['theaterName'])) {
        $screen = mysqli_real_escape_string($conn, $_GET['screen']);
        $screenId = mysqli_real_escape_string($conn, $_GET['screenId']);
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName']);

        $sql = "SELECT `section`, `section_name`, `seats`, `price` FROM `screen_sections` WHERE `screen`='$screen' AND `screen_id`='$screenId' AND `theater_name`='$theaterName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $sections = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $data = [
                'status' => 200,
                'message' => 'Screen sections fetched.',
                'sections' => $sections
            ];
            header("HTTP/1.0 Screen sections");
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
            'message' => 'Parameters are missing.'
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
