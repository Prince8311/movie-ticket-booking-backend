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

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['members'])) {
        $membersArray = explode(',', $_GET['members']);
        $nonExistentNames = [];
        $exists = 0;

        foreach ($membersArray as $member) {
            $member = trim(mysqli_real_escape_string($conn, $member));
            $sql = "SELECT * FROM `movie_casts_crews` WHERE `name` = '$member'";
            $result = mysqli_query($conn, $sql);
            $num = mysqli_num_rows($result);
    
            if ($num == 0) {
                $nonExistentNames[] = $member;
            } else {
                $exists++;
            }
        }

        $data = [
            'status' => 200,
            'message' => 'New members fecthed.',
            'members' => $nonExistentNames,
            'exist' => $exists
        ];
        header("HTTP/1.0 200 OK");
        echo json_encode($data);

    } else {
        $data = [
            'status' => 400,
            'message' => 'Missing members parameter'
        ];
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
