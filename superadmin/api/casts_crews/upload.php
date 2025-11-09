<?php

require "../../../utils/headers.php";
require "../../../utils/middleware.php";

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

    if (isset($_POST['member']) && isset($_FILES['image'])) {
        $memberName = mysqli_real_escape_string($conn,  $_POST['member']);
        $imageData = $_FILES['image'];
        $folder = "../../../profile-images/casts_crews/";
        $timestamp = date('YmdHis');
        $imageName = $memberName . $timestamp . '.png';
        $imageDirectory = $folder . $imageName;
        $image = getimagesize($imageData['tmp_name']);

        if ($image !== false) {
            $save = move_uploaded_file($imageData['tmp_name'], $imageDirectory);
            if ($save) {
                $sql = "INSERT INTO `movie_casts_crews`(`name`, `profile_image`) VALUES ('$memberName','$imageName')";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $response = [
                        'status' => 200,
                        'message' => 'Image uploaded'
                    ];
                    header("HTTP/1.0 200 Uploaded");
                    echo json_encode($response);
                } else {
                    $response = [
                        'status' => 500,
                        'message' => 'Database error: ' . mysqli_error($conn)
                    ];
                    header("HTTP/1.0 500 Internal Server Errorrrrr");
                    echo json_encode($response);
                }
            } else {
                $response = [
                    'status' => 500,
                    'message' => 'Sorry, there was an error uploading your file.'
                ];
                header("HTTP/1.0 500 Internal Server Errorrrrr");
                echo json_encode($response);
            }
        } else {
            $response = [
                'status' => 400,
                'message' => 'File is not an image.'
            ];
            header("HTTP/1.0 400 Bad Request");
            echo json_encode($response);
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
