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

    if (isset($_FILES['image'])) {
        $inputData = json_decode($_POST['inputs'], true);

        $title = mysqli_real_escape_string($conn, $inputData['title'] ?? '');
        $description = mysqli_real_escape_string($conn, $inputData['description'] ?? '');

        $imageData = $_FILES['image'];
        $folder = "../../../posters/banners/";
        $timestamp = date('YmdHis');
        $imageName = 'banner' . $timestamp . '.png';
        $imageDirectory = $folder . $imageName;
        $image = getimagesize($imageData['tmp_name']);
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
