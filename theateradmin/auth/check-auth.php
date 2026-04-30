<?php 

require __DIR__ . "/../../utils/headers.php";
require __DIR__ . "/../../utils/middleware.php";

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
    require __DIR__ . "/../../_db-connect.php";
    global $conn;

    $authToken = $authResult['token'];

    $sql = "SELECT * FROM `theater_users` WHERE `token` = '$authToken'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0)  {
        $user = mysqli_fetch_assoc($result);

        $data = [
            'status' => 200,
            'message' => 'Authenticated',
            'user' => $user
        ];
        header("HTTP/1.0 200 Authenticated");
        echo json_encode($data);
    } else {
        $data = [
            'status' => 400,
            'message' => 'No Authentication'
        ];
        header("HTTP/1.0 400 No Authentication");
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
