<?php 

require "../../utils/headers.php";
require "../../utils/middleware.php";

$authResult = superAdminAuthenticateRequest();
if (!$authResult['current_token']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message']
    ];
    header("HTTP/1.0 " . $authResult['status']);
    echo json_encode($data);
    exit;
}

$currentToken = $authResult['current_token'];

$response = [
    'status' => 200,
    'message' => 'Token invalid.',
    'currentToken' => $currentToken
];

header("HTTP/1.0 200 OK");
echo json_encode($response);

?>