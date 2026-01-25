<?php

date_default_timezone_set('Asia/Kolkata');
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

    if (isset($_POST['id']) && isset($_POST['inputs'])) {
        $userId = mysqli_real_escape_string($conn, $_POST['id']);
        $inputData = json_decode($_POST['inputs'], true);
        $name = mysqli_real_escape_string($conn, $inputData['name']);
        $phone = mysqli_real_escape_string($conn, $inputData['phone']);
        $email = mysqli_real_escape_string($conn, $inputData['email']);

        $updateFields = "name = '$name', phone = '$phone', email = '$email'";

        if (isset($_FILES['image'])) {
            $imageData = $_FILES['image'];
            $folder = "../../../profile-images/users/";
            $timestamp = date('YmdHis');
            $imageName = $name . $timestamp . '.png';
            $imageDirectory = $folder . $imageName;
            $image = getimagesize($imageData['tmp_name']);

            if ($image === false) {
                header("HTTP/1.0 400 Bad Request");
                echo json_encode([
                    'status' => 400,
                    'message' => 'Invalid image file'
                ]);
                exit;
            }

            if (!move_uploaded_file($imageData['tmp_name'], $imageDirectory)) {
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode([
                    'status' => 500,
                    'message' => 'Failed to upload image'
                ]);
                exit;
            }

            $updateFields .= ", image = '$imageName'";
        }

        $sql = "UPDATE users SET $updateFields WHERE id = '$userId'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $data = [
                'status' => 200,
                'message' => 'Profile updated successfully'
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
