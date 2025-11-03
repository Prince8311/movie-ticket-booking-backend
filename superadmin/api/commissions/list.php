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

    if (isset($_GET['theaterName']) && isset($_GET['commissionTo'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName'] ?? '');
        $commissionTo = mysqli_real_escape_string($conn, $_GET['commissionTo'] ?? '');

        if ($commissionTo === 'admin') {
            $sql = "SELECT `admin_commissions` FROM `registered_theaters` WHERE `name`='$theaterName'";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $commissionData = mysqli_fetch_assoc($result);
                $adminCommission = $commissionData['admin_commissions'];

                if (!empty($adminCommission) && !is_null($adminCommission)) {
                    $adminCommission = json_decode($adminCommission, true);
                } else {
                    $adminCommission = null;
                }

                $data = [
                    'status' => 200,
                    'message' => 'Admin commisions fetched',
                    'commission' => $adminCommission
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
        } else if ($commissionTo === 'theater') {
            $sql = "SELECT `commission_type`, `commission` FROM `registered_theaters` WHERE `name`='$theaterName'";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $commissionData = mysqli_fetch_assoc($result);
                $commissionType = $commissionData['commission_type'];
                $theaterCommission = $commissionData['commission'];
                if (!empty($theaterCommission) && !is_null($theaterCommission)) {
                    if ($commissionType === 'Multiple Commissions') {
                        $theaterCommission = json_decode($theaterCommission, true);
                    }
                } else {
                    $theaterCommission = null;
                }
                $data = [
                    'status' => 200,
                    'message' => 'Theater commisions fetched',
                    'commissionType' => $commissionType,
                    'commission' => $theaterCommission
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
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Parameters are missing'
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
