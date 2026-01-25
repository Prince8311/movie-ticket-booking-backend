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

    if (isset($_GET['id'])) {
        $screenId = mysqli_real_escape_string($conn, $_GET['id']);

        $findScreen = "SELECT * FROM `registered_screens` WHERE `screen_id` = '$screenId'";
        $find = mysqli_query($conn, $findScreen);
        $num = mysqli_num_rows($find);

        if ($num == 1) {
            $row = mysqli_fetch_assoc($find);
            $noOfSections = $row["sections"];

            $findSection = "SELECT `section`, `section_name` FROM `screen_sections` WHERE `screen_id` = '$screenId'";
            $sectionResult = mysqli_query($conn, $findSection);
            $secNum = mysqli_num_rows($sectionResult);

            if ($secNum > 0) {
                $sectionData = mysqli_fetch_all($sectionResult, MYSQLI_ASSOC);
                $seatData = [];

                foreach ($sectionData as $section) {
                    $sectionNo = $section['section'];
                    $rowSql = "SELECT * FROM `screen_rows` WHERE `section` = '$sectionNo' AND `screen_id` = '$screenId' AND `seats` IS NOT NULL";
                    $rowResult = mysqli_query($conn, $rowSql);
                    $rows = mysqli_fetch_all($rowResult, MYSQLI_ASSOC);
                    
                    $section['rows'] = $rows;
                    $seatData[] = $section;
                }

                $data = [
                    'status' => 200,
                    'message' => 'Seat layout fetched',
                    'noOfSections' => $noOfSections,
                    'seatLayout' => $seatData
                ];
                header("HTTP/1.0 200 Seat layout");
                echo json_encode($data);
            } 
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Screen id is missing'
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
