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

if ($requestMethod == 'POST') {
    require "../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);

        $checkSql = "SELECT * FROM `registered_theaters` WHERE `name`='$theaterName'";
        $checkResult = mysqli_query($conn, $checkSql);

        if ($checkResult) {
            $theaterData = mysqli_fetch_assoc($checkResult);
            $theaterCommission = $theaterData['commission'];
            $adminCommission = $theaterData['admin_commissions'];

            if (is_null($theaterCommission)) {
                $data = [
                    'status' => 201,
                    'message' => 'Not Settled',
                    'note' => 'Commission is not settled yet. Please wait or contact admin to settle your commission.'
                ];
                header("HTTP/1.0 201 Not Settled");
                echo json_encode($data);
                return;
            }

            if (is_null($adminCommission)) {
                $data = [
                    'status' => 201,
                    'message' => 'Not Settled',
                    'note' => 'Taxes and charges are not settled yet. Please wait or contact admin to settle them, so that user can visit your theater after publish.'
                ];
                header("HTTP/1.0 201 Not Settled");
                echo json_encode($data);
                return;
            }

            $screenStatus = 1;
            $theaterStatus = 'Published';

            $theaterUpdateSql = "UPDATE `registered_theaters` SET `status`='$theaterStatus' WHERE `name`='$theaterName'";
            $theaterUpdateResult = mysqli_query($conn, $theaterUpdateSql);

            $screenUpdateSql = "UPDATE `registered_screens` SET `status`='$screenStatus' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
            $screenUpdateResult = mysqli_query($conn, $screenUpdateSql);

            if ($theaterUpdateResult && $screenUpdateResult) {
                $data = [
                    'status' => 200,
                    'message' => $screen .' published successfully'
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
                'status' => 500,
                'message' => 'Database error: ' . mysqli_error($conn)
            ];
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode($data);
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
