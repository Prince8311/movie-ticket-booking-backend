<?php

require __DIR__ . "/../../../utils/headers.php";
require __DIR__ . "/../../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();

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
    require __DIR__ . "/../../../_db-connect.php";
    global $conn;

    if (isset($_GET['members'])) {
        $membersParam = $_GET['members'];
        $decoded = urldecode($membersParam);
        $membersArray = explode(',', $decoded);
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
            'message' => 'New members fetched.',
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
