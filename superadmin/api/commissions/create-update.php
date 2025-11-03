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
        $range = mysqli_real_escape_string($conn, $inputData['range']);
        $amount = mysqli_real_escape_string($conn, $inputData['amount']);
        $commissionTo = mysqli_real_escape_string($conn, $inputData['commissionTo']);

        if ($commissionTo === 'admin') {
            $fetchSql = "SELECT `admin_commissions` FROM `registered_theaters` WHERE `name`='$theaterName'";
            $fetchResult = mysqli_query($conn, $fetchSql);
            if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
                $row = mysqli_fetch_assoc($fetchResult);
                $existingJson = $row['admin_commissions'];
                if (empty($existingJson) || is_null($existingJson)) {
                    $commissions = [];
                } else {
                    $commissions = json_decode($existingJson, true);
                    if (!is_array($commissions)) {
                        $commissions = [];
                    }
                }
                $found = false;
                foreach ($commissions as &$commission) {
                    if (isset($commission['range']) && $commission['range'] === $range) {
                        $commission['amount'] = $amount;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $commissions[] = [
                        'range' => $range,
                        'amount' => $amount
                    ];
                }
                $updatedJson = json_encode($commissions, JSON_UNESCAPED_UNICODE);
                $updateSql = "UPDATE `registered_theaters` SET `admin_commissions` = '$updatedJson' WHERE `name` = '$theaterName'";
                $updateResult = mysqli_query($conn, $updateSql);

                if ($updateResult) {
                    $data = [
                        'status' => 200,
                        'message' => $found ? 'Commission updated' : 'Commission added'
                    ];
                    header("HTTP/1.0 200 OK");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Database update failed: ' . mysqli_error($conn)
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
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
            $commissionType = mysqli_real_escape_string($conn, $inputData['commissionType']);

            if ($commissionType === 'Single Commission') {
                $fetchSql = "SELECT `commission` FROM `registered_theaters` WHERE `name`='$theaterName'";
                $fetchResult = mysqli_query($conn, $fetchSql);

                if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
                    $row = mysqli_fetch_assoc($fetchResult);
                    $existingValue = $row['commission'];

                    $isNew = (empty($existingValue) || is_null($existingValue));

                    $updateSql = "UPDATE `registered_theaters` SET `commission_type`='$commissionType', `commission` = '$amount' WHERE `name` = '$theaterName'";
                    $updateResult = mysqli_query($conn, $updateSql);

                    if ($updateResult) {
                        $data = [
                            'status' => 200,
                            'message' => $isNew
                                ? 'Single commission added successfully'
                                : 'Single commission updated successfully'
                        ];
                        header("HTTP/1.0 200 OK");
                        echo json_encode($data);
                    } else {
                        $data = [
                            'status' => 500,
                            'message' => 'Database update failed: ' . mysqli_error($conn)
                        ];
                        header("HTTP/1.0 500 Internal Server Error");
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
            } else if ($commissionType === 'Multiple Commissions') {
                $fetchSql = "SELECT `commission` FROM `registered_theaters` WHERE `name`='$theaterName'";
                $fetchResult = mysqli_query($conn, $fetchSql);

                if ($fetchResult && mysqli_num_rows($fetchResult) > 0) {
                    $row = mysqli_fetch_assoc($fetchResult);
                    $existingJson = $row['commission'];
                    if (empty($existingJson) || is_null($existingJson)) {
                        $commissions = [];
                    } else {
                        $commissions = json_decode($existingJson, true);
                        if (!is_array($commissions)) {
                            $commissions = [];
                        }
                    }

                    $found = false;
                    foreach ($commissions as &$commission) {
                        if (isset($commission['range']) && $commission['range'] === $range) {
                            $commission['amount'] = $amount;
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $commissions[] = [
                            'range' => $range,
                            'amount' => $amount
                        ];
                    }

                    $updatedJson = json_encode($commissions, JSON_UNESCAPED_UNICODE);
                    $updateSql = "UPDATE `registered_theaters` SET `commission_type`='$commissionType', `commission` = '$updatedJson' WHERE `name` = '$theaterName'";
                    $updateResult = mysqli_query($conn, $updateSql);

                    if ($updateResult) {
                        $data = [
                            'status' => 200,
                            'message' => $found
                                ? 'Commission updated successfully'
                                : 'Commission added successfully'
                        ];
                        header("HTTP/1.0 200 OK");
                        echo json_encode($data);
                    } else {
                        $data = [
                            'status' => 500,
                            'message' => 'Database update failed: ' . mysqli_error($conn)
                        ];
                        header("HTTP/1.0 500 Internal Server Error");
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
