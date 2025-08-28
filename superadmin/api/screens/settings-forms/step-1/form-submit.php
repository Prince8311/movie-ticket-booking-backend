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

require "../../../../../utils/middleware.php";

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
    require "../../../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);
    
    if (!empty($inputData)) {
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $section = mysqli_real_escape_string($conn, $inputData['section']);
        $sectionName = mysqli_real_escape_string($conn, $inputData['sectionName']);
        $submitType = mysqli_real_escape_string($conn, $inputData['submitType']);

        if ($submitType === 'sectionName-submit') {
            $checkSql = "SELECT * FROM `screen_sections` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section' AND `section_name`='$sectionName'";
            $checkResult = mysqli_query($conn, $checkSql);

            if(mysqli_num_rows($checkResult) > 0) {
                $updateSql = "UPDATE `screen_sections` SET `section_name`='$sectionName' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId' AND `section`='$section'";
                $updateResult = mysqli_query($conn, $updateSql);

                if($updateResult) {
                    $data = [
                        'status' => 200,
                        'message' => 'Section name updated.'
                    ];
                    header("HTTP/1.0 200 Updated");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Database error: ' . $error
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($data);
                }
            } else {
                $insertSql = "INSERT INTO `screen_sections`(`theater_name`, `screen`, `screen_id`, `section`, `section_name`) VALUES ('$theaterName','$screen','$screenId','$section','$sectionName')";
                $insertResult = mysqli_query($conn, $insertSql);

                if($insertResult) {
                    $data = [
                        'status' => 200,
                        'message' => 'Section name created.'
                    ];
                    header("HTTP/1.0 200 Created");
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
        } else if ($submitType === 'final-submit') {
            $sectionNo = mysqli_real_escape_string($conn, $inputData['sectionNo']);

            $checkSql = "SELECT * FROM `registered_screens` WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
            $checkResult = mysqli_query($conn, $checkSql);
            $wasNull = false;
            if ($checkResult) {
                $checkData = mysqli_fetch_assoc($checkResult);
                $sections = $checkData['sections'];
            
                if ($sections === null) {
                    $wasNull = true;
                }
            }
            $updateSql = "UPDATE `registered_screens` SET `sections`='$sectionNo' WHERE `theater_name`='$theaterName' AND `screen`='$screen' AND `screen_id`='$screenId'";
            $updateResult = mysqli_query($conn, $updateSql);

            if($updateResult) {
                $data = [
                    'status' => 200,
                    'message' => $wasNull ? 'Step-1 Completed successfully.' : 'Section names updated.'
                ];
                header("HTTP/1.0 200 Updated");
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

?>