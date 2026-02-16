<?php

require "../../../utils/headers.php";
require "../../../utils/middleware.php";

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

if ($requestMethod == 'POST') {
    require "../../../_db-connect.php";
    global $conn;

    $inputData = json_decode(file_get_contents("php://input"), true);
    if (!empty($inputData)) {
        $roleName = mysqli_real_escape_string($conn, $inputData['roleName']);
        $permissions = $inputData['permissions'];
        $success = true;

        $deleteSQL = "DELETE FROM `roles_permissions` WHERE `role_name` = '$roleName'";
        $deleteResult = mysqli_query($conn, $deleteSQL);

        if ($deleteResult) {
            foreach ($permissions as $permissionType => $perms) {
                $create = isset($perms['create']) && $perms['create'] ? 1 : 0;
                $view   = isset($perms['view']) && $perms['view'] ? 1 : 0;
                $edit   = isset($perms['edit']) && $perms['edit'] ? 1 : 0;
                $delete = isset($perms['delete']) && $perms['delete'] ? 1 : 0;

                $permissionTypeEscaped = mysqli_real_escape_string($conn, $permissionType);

                $sql = "INSERT INTO `roles_permissions`(`role_name`, `permission`, `p_create`, `p_view`, `p_edit`, `p_delete`) VALUES ('$roleName', '$permissionTypeEscaped', $create, $view, $edit, $delete)";

                if (!mysqli_query($conn, $sql)) {
                    $success = false;
                    $error = mysqli_error($conn);
                    break;
                }
            }

            if ($success) {
                $data = [
                    'status' => 200,
                    'message' => 'Role updated successfully.'
                ];
                header("HTTP/1.0 200 OK");
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
            $data = [
                'status' => 500,
                'message' => 'Failed to clear old permissions: ' . mysqli_error($conn)
            ];
            header("HTTP/1.0 500 Internal Server Error");
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

?>