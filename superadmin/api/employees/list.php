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

if($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    $authHeader = getAuthorizationHeader();
    $cookieToken = $_COOKIE['authToken'] ?? '';

    $sql = "SELECT * FROM `users` WHERE `user_type`='employee'";
    $result = mysqli_query($conn, $sql);
    $totalEmployees = mysqli_num_rows($result);
    $limit = 10; 
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;
    $offset = ($page - 1) * $limit;

    $limitSql = "SELECT * FROM `users` WHERE `user_type`='employee' LIMIT $limit OFFSET $offset";
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