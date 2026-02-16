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

if($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    $sql = "SELECT * FROM `admin_users` WHERE `user_type`='employee' ORDER BY `id` DESC";
    $result = mysqli_query($conn, $sql);
    $totalEmployees = mysqli_num_rows($result);
    $limit = 10; 
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;
    $offset = ($page - 1) * $limit;

    $limitSql = "SELECT * FROM `admin_users` WHERE `user_type`='employee' ORDER BY `id` DESC LIMIT $limit OFFSET $offset";
    $limitResult = mysqli_query($conn, $limitSql);
    $employees = mysqli_fetch_all($limitResult, MYSQLI_ASSOC);

    $data = [
        'status' => 200,
        'message' => 'Employee list fetched',
        'totalCount' => $totalEmployees,
        'currentPage' => $page,
        'employees' => $employees,
    ];
    header("HTTP/1.0 200 Employee list");
    echo json_encode($data);

} else{
    $data = [
        'status' => 405,
        'message' => $requestMethod. ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>