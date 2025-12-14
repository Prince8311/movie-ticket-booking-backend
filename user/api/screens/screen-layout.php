<?php

require "../../../utils/headers.php";
require "../../utils/middleware.php";

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

    if (isset($_GET['screenId']) && isset($_GET['sectionName'])) {
        $screenId = mysqli_real_escape_string($conn, $_GET['screenId']);
        $sectionName = mysqli_real_escape_string($conn, $_GET['sectionName']);

        $findScreen = "SELECT * FROM `registered_screens` WHERE `screen_id` = '$screenId'";
        $findResult = mysqli_query($conn, $findScreen);
        $screenNo = mysqli_num_rows($findResult);

        if ($screenNo === 0) {
            $data = [
                'status' => 400,
                'message' => 'No screen found.'
            ];
            header("HTTP/1.0 400 Bad Request");
            echo json_encode($data);
            exit;
        }

        $findSection = "SELECT * FROM `screen_sections` WHERE `screen_id` = '$screenId' AND `section_name` = '$sectionName' AND `price` IS NOT NULL";
        $sectionResult = mysqli_query($conn, $findSection);
        $sectionNo = mysqli_num_rows($sectionResult);

        if ($sectionNo === 0) {
            $data = [
                'status' => 400,
                'message' => 'No section found.'
            ];
            header("HTTP/1.0 400 Bad Request");
            echo json_encode($data);
            exit;
        }

        $sections = mysqli_fetch_all($sectionResult, MYSQLI_ASSOC);
        $seatData = [];

        foreach ($sections as $section) {
            $sectionIndex = $section['section'];
            $seatSql = "SELECT `id`, `row`, `seats`, `starting`, `gap_seats`, `gap_amounts` FROM `screen_rows` WHERE `screen_id` = '$screenId' AND `section` = '$sectionIndex'";
            $seatResult = mysqli_query($conn, $seatSql);
            $seats = mysqli_fetch_all($seatResult, MYSQLI_ASSOC);

            $section['seat_layout'] = $seats;
            $seatData[] = $section;
        }

        $data = [
            'status' => 200,
            'message' => 'Seat layout fetched.',
            'seatData' => $seatData
        ];
        header("HTTP/1.0 200 Seat layout");
        echo json_encode($data);
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
