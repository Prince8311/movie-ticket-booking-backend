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

$refreshed = $authResult['refreshed'];
$newToken = $authResult['token'];

$response = [
    'status' => 200,
    'message' => $refreshed 
        ? 'Token refreshed successfully.' 
        : 'Token still valid.',
    'refreshed' => $refreshed,
    'newToken' => $newToken
];

header("HTTP/1.0 200 OK");
echo json_encode($response);

?>