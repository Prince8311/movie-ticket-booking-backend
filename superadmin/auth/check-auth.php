<?php

require "../../utils/headers.php";
require "../../utils/middleware.php";

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
    require "../../_db-connect.php";
    global $conn;

    $authToken = $authResult['token'];

    // Fetch user
    $sql = "SELECT `name`, `image`, `email`, `phone`, `status`, `user_type`, `user_role` FROM `admin_users` WHERE `auth_token` = '$authToken'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {

        $user = mysqli_fetch_assoc($result);
        $userRole = $user['user_role'];
        $createdBy = 'superadmin';
        // Fixed modules list
        $allModules = [
            "users",
            "employees",
            "listed_theaters",
            "requested_theaters",
            "registered_theaters",
            "role_permission",
            "commission_slabs",
            "banners",
            "movies",
            "state_city"
        ];

        // Initialize permission structure
        $permissionsFormatted = [];

        // If SUPER ADMIN → all true
        if ($userRole === "Super Admin") {

            foreach ($allModules as $key) {
                $permissionsFormatted[$key] = [
                    'create' => true,
                    'view'   => true,
                    'edit'   => true,
                    'delete' => true,
                ];
            }

        } else {

            // Fetch role permissions from DB
            $roleSql = "SELECT `permission`, `p_create`, `p_view`, `p_edit`, `p_delete` FROM `roles_permissions` WHERE `role_name` = '$userRole' AND `created_by`='$createdBy'";
            $roleResult = mysqli_query($conn, $roleSql);

            // Fill permissions based on DB
            if ($roleResult && mysqli_num_rows($roleResult) > 0) {
                while ($row = mysqli_fetch_assoc($roleResult)) {
                    $key = $row['permission'];

                    $permissionsFormatted[$key] = [
                        'create' => $row['p_create'] == 1,
                        'view'   => $row['p_view'] == 1,
                        'edit'   => $row['p_edit'] == 1,
                        'delete' => $row['p_delete'] == 1,
                    ];
                }
            }

            // Add missing modules as false
            foreach ($allModules as $key) {
                if (!isset($permissionsFormatted[$key])) {
                    $permissionsFormatted[$key] = [
                        'create' => false,
                        'view'   => false,
                        'edit'   => false,
                        'delete' => false,
                    ];
                }
            }
        }

        // Final response
        $data = [
            'status' => 200,
            'message' => 'Authenticated',
            'user' => $user,
            'permissions' => $permissionsFormatted
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
