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
        $sectionName = mysqli_real_escape_string($conn, $inputData['sectionName']);
        $amount = mysqli_real_escape_string($conn, $inputData['amount']);

        $checkSql = "SELECT `price` FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section_name`='$sectionName'";
        $checkResult = mysqli_query($conn, $checkSql);

        if ($checkResult) {
            $secData = mysqli_fetch_assoc($checkResult);
            $secPrice = $secData['price'];

            $updateSql = "UPDATE `screen_sections` SET `price`='$amount' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section_name`='$sectionName'";
            $updateResult = mysqli_query($conn, $updateSql);

            if ($updateResult) {
                $message = ($secPrice === null)
                    ? "Price created successfully."
                    : "Price updated successfully.";
                $data = [
                    'status' => 200,
                    'message' => $message,
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
