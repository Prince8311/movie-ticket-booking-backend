<?php

require "../../../../../utils/headers.php";
require "../../../../../utils/middleware.php";

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

if ($requestMethod == 'POST') {
    require "../../../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $section = mysqli_real_escape_string($conn, $inputData['section']);
        $submitType = mysqli_real_escape_string($conn, $inputData['submitType']);

        if ($submitType === 'row-submit') {
            $row = mysqli_real_escape_string($conn, $inputData['row']);
            $seats = mysqli_real_escape_string($conn, $inputData['seats']);
            $starting = mysqli_real_escape_string($conn, $inputData['starting']);
            $gapSeats = mysqli_real_escape_string($conn, $inputData['gapSeats']);
            $gapAmounts = mysqli_real_escape_string($conn, $inputData['gapAmounts']);

            $checkSql = "SELECT * FROM `screen_rows` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section' AND `row`='$row'";
            $checkResult = mysqli_query($conn, $checkSql);
            if ($checkResult) {
                if (mysqli_num_rows($checkResult) > 0) {
                    $updateSql = "UPDATE `screen_rows` SET `seats`='$seats',`starting`='$starting',`gap_seats`='$gapSeats',`gap_amounts`='$gapAmounts' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section' AND `row`='$row'";
                    $updateResult = mysqli_query($conn, $updateSql);

                    if ($updateResult) {
                        $data = [
                            'status' => 200,
                            'message' => $row . ' updated.'
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
                    $insertSql = "INSERT INTO `screen_rows`(`theater_name`, `screen`, `screen_id`, `section`, `row`, `seats`, `starting`, `gap_seats`, `gap_amounts`) VALUES ('$theaterName','$screen','$screenId','$section','$row','$seats','$starting','$gapSeats','$gapAmounts')";
                    $insertResult = mysqli_query($conn, $insertSql);

                    if ($insertResult) {
                        $data = [
                            'status' => 200,
                            'message' => $row . ' inserted.'
                        ];
                        header("HTTP/1.0 200 Inserted");
                        echo json_encode($data);
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
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else if ($submitType === 'final-submit') {
            $screenSql = "SELECT * FROM `registered_screens` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
            $screenResult = mysqli_query($conn, $screenSql);

            $sectionSql = "SELECT * FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
            $sectionResult = mysqli_query($conn, $sectionSql);

            $rowSql = "SELECT * FROM `screen_rows` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
            $rowResult = mysqli_query($conn, $rowSql);

            $sectionRowCountSql = "SELECT `section`, SUM(`seats`) AS totalSeats FROM `screen_rows` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' GROUP BY `section`";
            $sectionRowCountResult = mysqli_query($conn, $sectionRowCountSql);

            $sectionSeatCountSql = "SELECT `section`, SUM(`seats`) AS totalSeats FROM `screen_rows` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section' GROUP BY `section`";
            $sectionRowSeatResult = mysqli_query($conn, $sectionSeatCountSql);

            $sreenSeatCountSql = "SELECT `section`, SUM(`seats`) AS grandTotalSeats FROM `screen_rows` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
            $screenSeatResult = mysqli_query($conn, $sreenSeatCountSql);

            if ($screenResult && $sectionResult && $rowResult && $sectionRowCountResult && $screenSeatResult) {
                $screenData = mysqli_fetch_assoc($screenResult);
                $noOfSections = (int)$screenData['sections'];
                $sectionData = mysqli_fetch_assoc($sectionResult);
                $noOfRows = (int)$sectionData['row'];
                $seatData = mysqli_fetch_assoc($sectionRowSeatResult);
                $noOfSeats = $seatData['totalSeats'];
                $capacityData = mysqli_fetch_assoc($screenSeatResult);
                $screenCapacity = $capacityData['grandTotalSeats'];

                if ($noOfRows === mysqli_num_rows($rowResult)) {
                    $updateSql = "UPDATE `screen_sections` SET `seats`='$noOfSeats' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
                    $updateResult = mysqli_query($conn, $updateSql);

                    $screenUpdateSql = "UPDATE `registered_screens` SET `capacity`='$screenCapacity' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
                    $screenUpdateResult = mysqli_query($conn, $screenUpdateSql);

                    if ($updateResult) {
                        $sectionWithSeatsSql = "SELECT * FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `seats` IS NOT NULL";
                        $sectionWithSeatsResult = mysqli_query($conn, $sectionWithSeatsSql);
                        $noOfSectionsWithSeats = mysqli_num_rows($sectionWithSeatsResult);
                        $data = [
                            'status' => 200,
                            'message' => $section . ' setting Completed.',
                            'noOfSectionsWithSeats' => $noOfSectionsWithSeats
                        ];
                        header("HTTP/1.0 200 Completed");
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
                        'status' => 400,
                        'message' => 'Please fill all the rows.'
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
