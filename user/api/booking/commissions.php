<?php

require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['theaterName']) && isset($_GET['price'])) {
        $theaterName = mysqli_real_escape_string($conn, $_GET['theaterName']);
        $ticketPrice = (int) $_GET['price'];

        $sql = "SELECT `commission_type`, `commission`, `admin_commissions` FROM `registered_theaters` WHERE `name` = '$theaterName'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $theaterCommission = null;
            $commissionData = mysqli_fetch_assoc($result);
            $theaterCommissionType = $commissionData['commission_type'];
            $theaterCommissions = $commissionData['commission'];
            $adminCommissions = $commissionData['admin_commissions'];
            $commissionRanges = json_decode($adminCommissions, true);

            function getCommissionByRange($jsonString, $ticketPrice)
            {
                $amount = null;
                $ranges = json_decode($jsonString, true);

                if (is_array($ranges)) {
                    foreach ($ranges as $item) {
                        if (preg_match('/(\d+)\s*to\s*(\d+)/', $item['range'], $matches)) {
                            $min = (int) $matches[1];
                            $max = (int) $matches[2];

                            if ($ticketPrice >= $min && $ticketPrice <= $max) {
                                $amount = (int) $item['amount'];
                                break;
                            }
                        }
                    }
                }
                return $amount;
            }

            $adminCommission = getCommissionByRange($adminCommissions, $ticketPrice);
            if ($theaterCommissionType === 'Multiple Commissions') {
                $theaterCommission = getCommissionByRange($theaterCommissions, $ticketPrice);
            } else {
                $theaterCommission = (int) $theaterCommissions;
            }

            $data = [
                'status' => 200,
                'message' => 'Commissions fetched.',
                'theaterCommissionType' => $theaterCommissionType,
                'theaterCommission' => $theaterCommission,
                'adminCommission' => $adminCommission
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
