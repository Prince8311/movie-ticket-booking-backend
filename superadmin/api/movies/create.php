<?php

session_start();

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

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

    if (isset($_POST['inputs']) && isset($_FILES['image'])) {
        $inputData = json_decode($_POST['inputs'], true);

        $name = mysqli_real_escape_string($conn, $inputData['name']);
        $activity = mysqli_real_escape_string($conn, $inputData['activity']);
        $formats = mysqli_real_escape_string($conn, $inputData['formats']);
        $languages = mysqli_real_escape_string($conn, $inputData['languages']);
        $releaseDate = mysqli_real_escape_string($conn, $inputData['releaseDate']);
        $time = mysqli_real_escape_string($conn, $inputData['time']);
        $ageCategory = mysqli_real_escape_string($conn, $inputData['ageCategory']);
        $genres = mysqli_real_escape_string($conn, $inputData['genres']);
        $trailer = mysqli_real_escape_string($conn, $inputData['trailer']);
        $casts = mysqli_real_escape_string($conn, $inputData['casts']);
        $crews = mysqli_real_escape_string($conn, $inputData['crews']);
        $description = mysqli_real_escape_string($conn, $inputData['description']);

        $imageData = $_FILES['image'];
        $folder = "../../../posters/";
        $timestamp = date('YmdHis');
        $imageName = $name . $timestamp . '.png';
        $imageDirectory = $folder . $imageName;
        $image = getimagesize($imageData['tmp_name']);

        if ($image !== false) {
            $save = move_uploaded_file($imageData['tmp_name'], $imageDirectory);
            if ($save) {
                $sql = "INSERT INTO `movies`(`name`, `poster_image`, `release_date`, `total_time`, `languages`, `activity`, `formats`, `age_category`, `genres`, `casts`, `crews`, `trailer`, `description`) VALUES ('$name','$imageName','$releaseDate','$time','$languages','$activity','$formats','$ageCategory','$genres','$casts','$crews','$trailer','$description')";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $data = [
                        'status' => 200,
                        'message' => 'Movie uploaded successfully.'
                    ];
                    header("HTTP/1.0 200 Uploaded");
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
                    'message' => 'Sorry, there was an error uploading your file.'
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else {
            $data = [
                'status' => 400,
                'message' => 'File is not an image.'
            ];
            header("HTTP/1.0 400 Bad Request");
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
