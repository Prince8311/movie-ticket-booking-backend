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

    if (isset($_GET['theaterName'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName'] ?? '');

        $sql = "SELECT * FROM `registered_screens` WHERE `theater_name`='$theaterName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $screenData = mysqli_fetch_all($result, MYSQLI_ASSOC);

            foreach ($screenData as &$screen) {
                $screen['status'] = $screen['status'] == 1 ? true : false;
                $screenId = $screen['screen_id'];
                $sectionQuery = "SELECT * FROM `screen_sections` WHERE `screen_id`='$screenId'";
                $sectionResult = mysqli_query($conn, $sectionQuery);

                if ($sectionResult) {
                    $sections = mysqli_fetch_all($sectionResult, MYSQLI_ASSOC);
                    $screen['sections'] = $sections;
                    $allNullPrices = true;
                    $allHavePrice = true;
                    foreach ($sections as $section) {
                        if (!is_null($section['price']) && $section['price'] !== '') {
                            $allNullPrices = false;
                            break;
                        }
                    }
                    foreach ($sections as $section) {
                        if (is_null($section['price']) || $section['price'] === '') {
                            $allHavePrice = false;
                            break;
                        }
                    }
                    $screen['price'] = $allNullPrices ? false : true;
                    $screen['publish_available'] = $allHavePrice;
                } else {
                    $screen['sections'] = [];
                }
            }

            $data = [
                'status' => 200,
                'message' => 'Screen details fetched',
                'screenDetails' => $screenData,
            ];
            header("HTTP/1.0 200 OK");
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
            'message' => 'Parameter is missing'
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
