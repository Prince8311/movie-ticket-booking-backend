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
}

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['theaterName'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName'] ?? '');

        $sql = "SELECT * FROM `registered_theaters` WHERE `name`='$theaterName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $theaterData = mysqli_fetch_assoc($result);
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

            $data = [
                'status' => 200,
                'message' => 'Commissions are charges are settled'
            ];
            header("HTTP/1.0 200 OK");
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
