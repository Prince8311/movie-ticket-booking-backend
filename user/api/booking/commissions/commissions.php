<?php

require "../../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../../_db-connect.php";
    global $conn;

    if (isset($_GET['theaterName'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName']);

        $sql = "SELECT `commission_type`, `commission`, `admin_commissions` FROM `registered_theaters` WHERE `name` = '$theaterName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $commissionData = mysqli_fetch_assoc($result);
            $theaterCommissionType = $commissionData['commission_type'];
            $theaterCommissions = $commissionData['commission'];
            $adminCommissions = $commissionData['admin_commissions'];

            $data = [
                'status' => 200,
                'message' => 'Commissions fetched.',
                'theaterCommissionType' => $theaterCommissionType,
                'theaterCommissions' => $theaterCommissions,
                'adminCommissions' => $adminCommissions
            ];
            header("HTTP/1.0 200 Commissions");
            echo json_encode($data);
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Theater name required.'
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
