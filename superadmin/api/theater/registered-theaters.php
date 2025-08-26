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

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    $allowedStatuses = ['Pending', 'Confirmed', 'Processing', 'Rejected'];
    $whereClause = "";
    
    if ($status && in_array($status, $allowedStatuses)) {
        $whereClause = "WHERE `status` = '" . mysqli_real_escape_string($conn, $status) . "'";
    }
    $sql = "SELECT * FROM `registered_theaters` $whereClause";
    $result = mysqli_query($conn, $sql);
    $totalTheaters = mysqli_num_rows($result);
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
        ? (int)$_GET['page']
        : 1;
    $offset = ($page - 1) * $limit;

    $limitSql = "SELECT rt.*, tu.phone FROM `registered_theaters` rt LEFT JOIN `theater_users` tu ON rt.`name` = tu.`theater_name` $whereClause LIMIT $limit OFFSET $offset";
    $limitResult = mysqli_query($conn, $limitSql);
    $theaters = mysqli_fetch_all($limitResult, MYSQLI_ASSOC);

    $data = [
        'status' => 200,
        'message' => 'Registered theaters fetched',
        'totalCount' => $totalTheaters,
        'currentPage' => $page,
        'theaters' => $theaters,
    ];
    header("HTTP/1.0 200 Theater list");
    echo json_encode($data);
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}

?>