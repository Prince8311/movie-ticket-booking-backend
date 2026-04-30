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

    $sql = "SELECT rp.role_name, COUNT(u.id) AS user_count FROM ( SELECT DISTINCT role_name FROM roles_permissions) rp LEFT JOIN admin_users u ON rp.role_name = u.user_role GROUP BY rp.role_name";
    $result = mysqli_query($conn, $sql);
    $roles = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = $row;
        }
        $data = [
            'status' => 200,
            'message' => 'Role list fetched.',
            'roles' => $roles
        ];
        header("HTTP/1.0 200 Role list");
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
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>