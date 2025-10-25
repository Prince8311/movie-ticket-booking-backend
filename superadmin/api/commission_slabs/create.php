<?php

require "../../../utils/headers.php";

if ($requestMethod == 'POST') {
    require "../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);
    if (!empty($inputData)) {
        $slabFrom = mysqli_real_escape_string($conn, $inputData['from']);
        $slabTo = mysqli_real_escape_string($conn, $inputData['to']);
        $slabType = mysqli_real_escape_string($conn, $inputData['type']);

        if ($slabType == "admin") {
            $checkSql = "SELECT * FROM `admin_commission_slabs` WHERE `slab_from`='$slabFrom' OR `slab_to`='$slabTo'";
            $checkResult = mysqli_query($conn, $checkSql);
            if (mysqli_num_rows($checkResult) > 0) {
                $data = [
                    'status' => 400,
                    'message' => 'This slab already exists.'
                ];
                header("HTTP/1.0 400 Already exists");
                echo json_encode($data);
                exit;
            }

            $sql = "INSERT INTO `admin_commission_slabs`(`slab_from`, `slab_to`) VALUES ('$slabFrom','$slabTo')";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $data = [
                    'status' => 200,
                    'message' => 'Slab created successfully.'
                ];
                header("HTTP/1.0 200 OK");
                echo json_encode($data);
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else if ($slabType == "theater") {
            $checkSql = "SELECT * FROM `theater_commission_slabs` WHERE `slab_from`='$slabFrom' OR `slab_to`='$slabTo'";
            $checkResult = mysqli_query($conn, $checkSql);
            if (mysqli_num_rows($checkResult) > 0) {
                $data = [
                    'status' => 400,
                    'message' => 'This slab already exists.'
                ];
                header("HTTP/1.0 400 Already exists");
                echo json_encode($data);
                exit;
            }

            $sql = "INSERT INTO `theater_commission_slabs`(`slab_from`, `slab_to`) VALUES ('$slabFrom','$slabTo')";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $data = [
                    'status' => 200,
                    'message' => 'Slab created successfully.'
                ];
                header("HTTP/1.0 200 OK");
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
