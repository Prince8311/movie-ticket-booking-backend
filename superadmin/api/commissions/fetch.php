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
            $range = mysqli_real_escape_string($conn, $_GET['range'] ?? '');
            $fetchSql = "SELECT `admin_commissions` FROM `registered_theaters` WHERE `name` = '$theaterName'";
            $fetchResult = mysqli_query($conn, $fetchSql);

            if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
                $row = mysqli_fetch_assoc($fetchResult);
                $existingJson = $row['admin_commissions'];

                if (empty($existingJson) || is_null($existingJson)) {
                    $data = [
                        'status' => 404,
                        'message' => 'No commission data found for this theater'
                    ];
                    header("HTTP/1.0 404 Not Found");
                    echo json_encode($data);
                    exit;
                }

                $commissions = json_decode($existingJson, true);
                if (!is_array($commissions)) {
                    $data = [
                        'status' => 500,
                        'message' => 'Invalid commission data format'
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($data);
                    exit;
                }

                $foundAmount = null;
                foreach ($commissions as $commission) {
                    if (isset($commission['range']) && $commission['range'] === $range) {
                        $foundAmount = $commission['amount'];
                        break;
                    }
                }

                if (!is_null($foundAmount)) {
                    $data = [
                        'status' => 200,
                        'message' => 'Commission found',
                        'amount' => $foundAmount
                    ];
                    header("HTTP/1.0 200 OK");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 404,
                        'message' => 'No commission found for the given range'
                    ];
                    header("HTTP/1.0 404 Not Found");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 404,
                    'message' => 'Theater not found'
                ];
                header("HTTP/1.0 404 Not Found");
                echo json_encode($data);
            }
        } else if ($commissionTo === 'theater') {
            $commissionType = mysqli_real_escape_string($conn, $_GET['commissionType'] ?? '');
            $fetchSql = "SELECT `commission` FROM `registered_theaters` WHERE `name` = '$theaterName'";
            $fetchResult = mysqli_query($conn, $fetchSql);

            if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
                $row = mysqli_fetch_assoc($fetchResult);
                $existingJson = $row['commission'];

                if (empty($existingJson) || is_null($existingJson)) {
                    $data = [
                        'status' => 404,
                        'message' => 'No commission data found for this theater'
                    ];
                    header("HTTP/1.0 404 Not Found");
                    echo json_encode($data);
                    exit;
                }

                if ($commissionType === 'Single Commission') {
                    $data = [
                        'status' => 200,
                        'message' => 'Commission found',
                        'amount' => $existingJson
                    ];
                    header("HTTP/1.0 200 OK");
                    echo json_encode($data);
                } else if ($commissionType === 'Multiple Commissions') {
                    $range = mysqli_real_escape_string($conn, $_GET['range'] ?? '');
                    $commissions = json_decode($existingJson, true);
                    if (!is_array($commissions)) {
                        $data = [
                            'status' => 500,
                            'message' => 'Invalid commission data format'
                        ];
                        header("HTTP/1.0 500 Internal Server Error");
                        echo json_encode($data);
                        exit;
                    }

                    $foundAmount = null;
                    foreach ($commissions as $commission) {
                        if (isset($commission['range']) && $commission['range'] === $range) {
                            $foundAmount = $commission['amount'];
                            break;
                        }
                    }

                    if (!is_null($foundAmount)) {
                        $data = [
                            'status' => 200,
                            'message' => 'Commission found',
                            'amount' => $foundAmount
                        ];
                        header("HTTP/1.0 200 OK");
                        echo json_encode($data);
                    } else {
                        $data = [
                            'status' => 404,
                            'message' => 'No commission found for the given range'
                        ];
                        header("HTTP/1.0 404 Not Found");
                        echo json_encode($data);
                    }
                }
            } else {
                $data = [
                    'status' => 404,
                    'message' => 'Theater not found'
                ];
                header("HTTP/1.0 404 Not Found");
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
