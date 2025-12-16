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

$newToken = $authResult['token'];

$response = [
    'status' => 200,
    'message' => 'Token refreshed successfully.',
    'refreshed' => true,
    'newToken' => $newToken
];

header("HTTP/1.0 200 OK");
echo json_encode($response);

?>