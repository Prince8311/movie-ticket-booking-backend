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

    if(isset($_POST['theater']) && isset($_POST['userName']) && isset($_POST['screen']) && isset($_POST['screen_type']) &&  isset($_FILES['image'])) {
        $theater = mysqli_real_escape_string($conn, $_POST['theater']);
        $userName = mysqli_real_escape_string($conn, $_POST['userName']);
        $screen = mysqli_real_escape_string($conn, $_POST['screen']);
        $screenType = mysqli_real_escape_string($conn, $_POST['screen_type']);

        $theaterFirstLetter = strtoupper(substr($theater, 0, 1));
        $userInitials = strtoupper(substr($userName, 0, 2));

        $randomNumber = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $firstHalf = substr($randomNumber, 0, 3);
        $secondHalf = substr($randomNumber, 3, 3);

        $screenId = $theaterFirstLetter . $firstHalf . $userInitials . $secondHalf;
        $imageData = $_FILES['image'];

        $folder = "../../../screen-layouts/";
        $imageName = $theater . '-'. $screen .'.png';
        $imageDirectory = $folder . $imageName;
        $image = getimagesize($imageData['tmp_name']);

        if($image === false) {
            $data = [
                'status' => 400,
                'message' => 'File is not an image.'
            ];
            header("HTTP/1.0 400 Bad Request");
            echo json_encode($data);
            exit;
        }

        $existSql = "SELECT * FROM `requested_screens` WHERE `theater_name`='$theater' AND `screen`='$screen'";
        $existResult = mysqli_query($conn, $existSql);

        if(mysqli_num_rows($existResult) > 0) {
            $screenData = mysqli_fetch_assoc($existResult);
            $existImageName = $screenData['layout_image'];
            $existImageDirectory = $folder.$existImageName;
            if (file_exists($existImageDirectory)) {
                unlink($existImageDirectory);
                $save = move_uploaded_file($imageData['tmp_name'], $imageDirectory);
                if($save) {
                    $updateSql = "UPDATE `requested_screens` SET `screen_type`='$screenType',`layout_image`='$imageName' WHERE `theater_name`='$theater' AND `screen`='$screen'";
                    $updateResult = mysqli_query($conn, $updateSql);

                    if($updateResult){
                        $data = [
                            'status' => 200,
                            'message' => 'Screen details updated.'
                        ];
                        header("HTTP/1.0 200 Updated");
                        echo json_encode($data);
                    } else {
                        unlink($imageDirectory);
                        $data = [
                            'status' => 500,
                            'message' => 'Internal Server Error'
                        ];
                        header("HTTP/1.0 403 Internal Server Error");
                        echo json_encode($data);
                    }
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Error in uploading the image.'
                    ];
                    header("HTTP/1.0 500 Uploading Error");
                    echo json_encode($data);
                }
            }
        } else {
            $save = move_uploaded_file($imageData['tmp_name'], $imageDirectory);
            if($save) {
                $insertSql = "INSERT INTO `requested_screens`(`theater_name`, `screen`, `screen_id`, `screen_type`, `layout_image`) VALUES ('$theater','$screen','$screenId','$screenType','$imageName')";
                $insertResult = mysqli_query($conn, $insertSql);

                if($insertResult){
                    $data = [
                        'status' => 200,
                        'message' => 'Screen details uploaded.'
                    ];
                    header("HTTP/1.0 200 Uploaded");
                    echo json_encode($data);
                } else {
                    unlink($imageDirectory);
                    $data = [
                        'status' => 500,
                        'message' => 'Internal Server Error'
                    ];
                    header("HTTP/1.0 403 Internal Server Error");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Error in uploading the image.'
                ];
                header("HTTP/1.0 500 Uploading Error");
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

?>