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

        $title = isset($inputData['title']) ? mysqli_real_escape_string($conn, $inputData['title']) : null;
        $description = isset($inputData['description']) ? mysqli_real_escape_string($conn, $inputData['description']) : null;

        $titleValue = $title === null ? NULL : "'$title'";
        $descriptionValue = $description === null ? NULL : "'$description'";

        $imageData = $_FILES['image'];
        $folder = "../../../posters/banners/";
        $timestamp = date('YmdHis');
        $imageName = 'banner' . $timestamp . '.png';
        $imageDirectory = $folder . $imageName;
        $image = getimagesize($imageData['tmp_name']);

        if ($image !== false) {
            $save = move_uploaded_file($imageData['tmp_name'], $imageDirectory);
            if ($save) {
                $sql = "INSERT INTO `banners`(`image`, `title`, `description`) VALUES ('$imageName','$titleValue','$descriptionValue')";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $data = [
                        'status' => 200,
                        'message' => 'Banner uploaded successfully.'
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
