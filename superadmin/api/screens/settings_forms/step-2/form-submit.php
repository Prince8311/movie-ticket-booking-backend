<?php

require "../../../../../utils/headers.php";
require "../../../../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();

if (!$authResult['authenticated']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message']
    ];
    header("HTTP/1.0 " . $authResult['status']);
    echo json_encode($data);
    exit;
}

if ($requestMethod == 'POST') {
    require "../../../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $submitType = mysqli_real_escape_string($conn, $inputData['submitType']);

        if ($submitType === 'rowNo-submit') {
            $section = mysqli_real_escape_string($conn, $inputData['section']);
            $rowNo = mysqli_real_escape_string($conn, $inputData['rowNo']);

            $checkSql = "SELECT * FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
            $checkResult = mysqli_query($conn, $checkSql);
            if ($checkResult) {
                $screenData = mysqli_fetch_assoc($checkResult);
                $noOfRows = $screenData['row'];
                $wasNull = false;

                if ($noOfRows === null) {
                    $wasNull = true;
                }

                $updateSql = "UPDATE `screen_sections` SET `row`='$rowNo' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
                $updateResult = mysqli_query($conn, $updateSql);

                if ($updateResult) {
                    $data = [
                        'status' => 200,
                        'message' => $wasNull ? 'No. of rows added to ' . $section : 'Rows number of ' . $section . ' updated'
                    ];
                    header("HTTP/1.0 200 Updated");
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
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else if ($submitType === 'final-submit') {
            $screenSql = "SELECT * FROM `registered_screens` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
            $screenResult = mysqli_query($conn, $screenSql);

            $sectionSql = "SELECT `id`, `section`, `row` FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `row` IS NOT NULL";
            $sectionResult = mysqli_query($conn, $sectionSql);

            if ($screenResult && $sectionResult) {
                $screenData = mysqli_fetch_assoc($screenResult);
                $noOfSections = (int)$screenData['sections'];

                if($noOfSections === mysqli_num_rows($sectionResult)) {
                    $data = [
                        'status' => 200,
                        'message' => 'Step-2 Completed.'
                    ];
                    header("HTTP/1.0 200 Completed");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 400,
                        'message' => 'Please fill all the sections.'
                    ];
                    header("HTTP/1.0 400 Bad request");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Empty request data'
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
